<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $email): User
    {
        $user = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_private_upload_download_and_delete_lifecycle(): void
    {
        Storage::fake('local');
        $admin = $this->account('admin', 'admin@edumia.test');

        $this->actingAs($admin)->get('/admin/media')->assertOk()->assertSee('Media library');
        $pdf = UploadedFile::fake()->createWithContent(
            'course-guide.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"
        );
        $this->post('/admin/media', [
            'file' => $pdf,
            'alt_text' => 'A course guide',
        ])->assertRedirect(route('admin.media.index'));

        $media = MediaFile::firstOrFail();
        $this->assertSame('local', $media->disk);
        $this->assertSame('private', $media->visibility);
        Storage::disk('local')->assertExists($media->path);
        $this->get('/admin/media/'.$media->id.'/download')->assertOk();
        $this->assertDatabaseHas('audit_events', ['action' => 'media.uploaded']);

        $this->delete('/admin/media/'.$media->id)
            ->assertRedirect(route('admin.media.index'));
        Storage::disk('local')->assertMissing($media->path);
        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_student_cannot_access_media_routes(): void
    {
        $student = $this->account('student', 'student@edumia.test');
        $media = MediaFile::create([
            'disk' => 'local',
            'path' => 'edumia/media/existing.pdf',
            'original_name' => 'existing.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 42,
            'visibility' => 'private',
        ]);

        $this->actingAs($student)->get('/admin/media')->assertForbidden();
        $this->get('/admin/media/'.$media->id.'/download')->assertForbidden();
        $this->post('/admin/media', [])->assertForbidden();
    }

    public function test_media_workspace_supports_private_asset_search_and_type_filters(): void
    {
        $admin = $this->account('admin', 'owner@edumia.test');
        MediaFile::query()->create([
            'user_id' => $admin->id, 'disk' => 'local', 'path' => 'edumia/media/logo.png',
            'original_name' => 'school-logo.png', 'mime_type' => 'image/png',
            'size_bytes' => 2048, 'alt_text' => 'School badge', 'visibility' => 'private',
        ]);
        MediaFile::query()->create([
            'user_id' => $admin->id, 'disk' => 'local', 'path' => 'edumia/media/guide.pdf',
            'original_name' => 'student-guide.pdf', 'mime_type' => 'application/pdf',
            'size_bytes' => 4096, 'visibility' => 'private',
        ]);
        $this->actingAs($admin)->get('/admin/media')->assertOk()
            ->assertSee('data-admin-shell', false)->assertSee('Stored file size')
            ->assertSee('school-logo.png')->assertSee('student-guide.pdf')
            ->assertSee('Private storage only');
        $this->get('/admin/media?type=image')->assertOk()
            ->assertSee('school-logo.png')->assertDontSee('student-guide.pdf');
        $this->get('/admin/media?type=document')->assertOk()
            ->assertSee('student-guide.pdf')->assertDontSee('school-logo.png');
        $this->get('/admin/media?q=badge')->assertOk()
            ->assertSee('School badge')->assertDontSee('student-guide.pdf');
        $this->get('/admin/media?type[]=image&q[]=bad')->assertOk();
    }

}

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
}

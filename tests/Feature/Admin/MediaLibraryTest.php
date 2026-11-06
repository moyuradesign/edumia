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
        $this->get('/admin/media')->assertOk()->assertSee('Media audit history')
            ->assertSee('course-guide.pdf')->assertSee('Deleted');
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

    public function test_media_description_preview_and_missing_description_filter_are_private(): void
    {
        Storage::fake('local');
        $admin = $this->account('admin', 'alt-admin@edumia.test');
        $student = $this->account('student', 'alt-student@edumia.test');
        $path = 'edumia/media/private.png';
        Storage::disk('local')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8HCnQAAAAASUVORK5CYII='
        ));
        $image = MediaFile::query()->create([
            'user_id' => $admin->id, 'disk' => 'local', 'path' => $path,
            'original_name' => 'private.png', 'mime_type' => 'image/png',
            'size_bytes' => 68, 'visibility' => 'private',
        ]);
        $this->actingAs($admin)->get('/admin/media?description=missing')->assertOk()
            ->assertSee('private.png')->assertSee('admin-media-description');
        $this->get('/admin/media/'.$image->id.'/preview')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->put('/admin/media/'.$image->id, ['alt_text' => '  Private product cover  '])
            ->assertRedirect(route('admin.media.index'));
        $this->assertSame('Private product cover', $image->fresh()->alt_text);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id, 'subject_id' => $image->id,
            'action' => 'media.description_updated',
        ]);
        $this->get('/admin/media')->assertOk()
            ->assertSee('Last activity:')
            ->assertSee('Private product cover')
            ->assertSee('Preview image')
            ->assertSee('Uploaded');
        $this->get('/admin/media?description=missing')->assertOk()->assertDontSee('private.png');
        $this->put('/admin/media/'.$image->id, ['alt_text' => str_repeat('a', 256)])
            ->assertSessionHasErrors('alt_text');

        $this->actingAs($student)->get('/admin/media/'.$image->id.'/preview')->assertForbidden();
        $this->put('/admin/media/'.$image->id, ['alt_text' => 'Unauthorized'])->assertForbidden();
        $this->assertSame('Private product cover', $image->fresh()->alt_text);
    }

    public function test_media_preview_rejects_documents_and_missing_files(): void
    {
        $admin = $this->account('admin', 'preview-admin@edumia.test');
        Storage::fake('local');
        $pdf = MediaFile::query()->create([
            'user_id' => $admin->id, 'disk' => 'local',
            'path' => 'edumia/media/guide.pdf', 'original_name' => 'guide.pdf',
            'mime_type' => 'application/pdf', 'size_bytes' => 100, 'visibility' => 'private',
        ]);
        $this->actingAs($admin)->get('/admin/media/'.$pdf->id.'/preview')->assertNotFound();
        $missing = MediaFile::query()->create([
            'user_id' => $admin->id, 'disk' => 'local',
            'path' => 'edumia/media/gone.png', 'original_name' => 'gone.png',
            'mime_type' => 'image/png', 'size_bytes' => 100, 'visibility' => 'private',
        ]);
        $this->get('/admin/media/'.$missing->id.'/preview')->assertNotFound();
    }

    public function test_private_media_preview_rejects_non_private_records_and_unsafe_paths(): void
    {
        Storage::fake('local');
        $admin = $this->account('admin', 'path-admin@edumia.test');

        Storage::disk('local')->put('edumia/media/valid.png', 'PNG TEST CONTENT');
        Storage::disk('local')->put('unrelated/private.png', 'UNRELATED');
        $image = MediaFile::query()->create([
            'user_id' => $admin->id, 'disk' => 'local',
            'path' => 'edumia/media/valid.png', 'original_name' => 'valid.png',
            'mime_type' => 'image/png', 'size_bytes' => 16, 'visibility' => 'private',
        ]);
        $this->actingAs($admin)->get(route('admin.media.preview', $image))->assertOk();
        $this->get(route('admin.media.download', $image))->assertOk();

        $image->forceFill(['path' => 'unrelated/private.png'])->save();
        $this->get(route('admin.media.preview', $image))->assertNotFound();
        $this->get(route('admin.media.download', $image))->assertNotFound();

        $image->forceFill(['path' => 'edumia/media/valid.png', 'visibility' => 'public'])->save();
        $this->get(route('admin.media.preview', $image))->assertNotFound();
        $this->get(route('admin.media.download', $image))->assertNotFound();
    }

}

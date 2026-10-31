<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ebook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class EbookLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function input(string $slug = 'leadership-guide', int $price = 0): array
    {
        return [
            'title' => 'Leadership Guide', 'slug' => $slug,
            'description' => 'An original and practical learning resource.',
            'price' => $price,
            'file' => UploadedFile::fake()->createWithContent(
                'leadership.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"
            ),
        ];
    }

    public function test_author_admin_and_learner_can_complete_free_pdf_library_workflow(): void
    {
        Storage::fake('local');
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $admin = $this->member('admin', 'admin@edumia.test');
        $learner = $this->member('student', 'learner@edumia.test');

        $this->actingAs($teacher)->get('/instructor/ebooks/create')->assertOk();
        $this->post('/instructor/ebooks', $this->input())
            ->assertRedirect(route('instructor.ebooks.index'));

        $ebook = Ebook::firstOrFail();
        Storage::disk('local')->assertExists($ebook->file_path);
        $this->assertSame('draft', $ebook->status);
        $this->get('/instructor/ebooks')->assertOk()->assertSee('Leadership Guide');

        auth()->logout();
        $this->get('/ebooks')->assertOk()->assertDontSee('Leadership Guide');
        $this->get('/ebooks/'.$ebook->slug)->assertNotFound();

        $this->actingAs($admin)->get('/admin/ebooks')->assertOk()->assertSee('Leadership Guide');
        $this->patch('/admin/ebooks/'.$ebook->id.'/publication', ['published' => 1])
            ->assertRedirect(route('admin.ebooks.index'));
        $this->assertSame('published', $ebook->fresh()->status);
        $this->assertDatabaseHas('audit_events', ['action' => 'ebooks.published']);

        auth()->logout();
        $this->get('/ebooks')->assertOk()->assertSee('Leadership Guide');
        $this->get('/ebooks/'.$ebook->slug)->assertOk();

        $this->actingAs($learner)->get('/my-ebooks/'.$ebook->id.'/download')->assertForbidden();
        $this->post('/my-ebooks/'.$ebook->id)->assertRedirect(route('ebooks.library'));
        $this->post('/my-ebooks/'.$ebook->id)->assertRedirect(route('ebooks.library'));
        $this->assertDatabaseCount('ebook_purchases', 1);
        $this->assertDatabaseHas('ebook_purchases', [
            'ebook_id' => $ebook->id, 'user_id' => $learner->id, 'status' => 'completed',
        ]);
        $this->get('/my-ebooks')->assertOk()->assertSee('Leadership Guide');
        $this->get('/my-ebooks/'.$ebook->id.'/download')->assertOk();
    }

    public function test_paid_ebook_cannot_be_published_or_claimed_without_payment(): void
    {
        Storage::fake('local');
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $admin = $this->member('admin', 'admin@edumia.test');
        $learner = $this->member('student', 'learner@edumia.test');

        $this->actingAs($teacher)->post('/instructor/ebooks', $this->input('premium-guide', 25))
            ->assertRedirect();
        $ebook = Ebook::firstOrFail();

        $this->actingAs($admin)
            ->patch('/admin/ebooks/'.$ebook->id.'/publication', ['published' => 1])
            ->assertSessionHasErrors('ebook');

        $this->actingAs($learner)
            ->post('/my-ebooks/'.$ebook->id)->assertNotFound();
        $this->assertDatabaseCount('ebook_purchases', 0);
    }

    public function test_other_instructor_cannot_edit_private_ebook_and_students_cannot_upload(): void
    {
        Storage::fake('local');
        $owner = $this->member('instructor', 'owner@edumia.test');
        $other = $this->member('instructor', 'other@edumia.test');
        $student = $this->member('student', 'student@edumia.test');

        $this->actingAs($owner)->post('/instructor/ebooks', $this->input())
            ->assertRedirect();
        $ebook = Ebook::firstOrFail();

        $this->actingAs($other)->get('/instructor/ebooks/'.$ebook->id.'/edit')->assertForbidden();
        $this->put('/instructor/ebooks/'.$ebook->id, [
            'title' => 'Unauthorized', 'slug' => 'unauthorized', 'price' => 0,
        ])->assertForbidden();
        $this->actingAs($student)->get('/instructor/ebooks/create')->assertForbidden();
        $this->post('/instructor/ebooks', $this->input('student-guide'))->assertForbidden();

        $this->assertDatabaseCount('ebooks', 1);
    }

    public function test_modification_returns_published_book_to_review_before_publication(): void
    {
        Storage::fake('local');
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $admin = $this->member('admin', 'admin@edumia.test');

        $this->actingAs($teacher)->post('/instructor/ebooks', $this->input())->assertRedirect();
        $ebook = Ebook::firstOrFail();
        $this->actingAs($admin)->patch('/admin/ebooks/'.$ebook->id.'/publication', ['published' => 1])
            ->assertRedirect();
        $this->actingAs($teacher)->put('/instructor/ebooks/'.$ebook->id, [
            'title' => 'Updated Guide', 'slug' => 'updated-guide', 'price' => 0,
            'description' => 'A safer updated edition.',
        ])->assertRedirect();

        $this->assertSame('draft', $ebook->fresh()->status);
        $this->get('/ebooks/updated-guide')->assertNotFound();
    }
}

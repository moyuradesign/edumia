<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BlogEditorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $member = User::query()->create(['name' => ucfirst($role),
            'email' => $email, 'password' => 'Password#12345']);
        $member->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $member;
    }

    private function media(User $owner): MediaFile
    {
        Storage::disk('local')->put('edumia/media/editor-cover.png', 'TEST_IMAGE_CONTENT');

        return MediaFile::query()->create([
            'user_id' => $owner->getKey(), 'disk' => 'local',
            'path' => 'edumia/media/editor-cover.png', 'original_name' => 'editor-cover.png',
            'mime_type' => 'image/png', 'visibility' => 'private', 'size_bytes' => 18,
            'alt_text' => 'Students reading at a desk',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Building Better Learning Habits',
            'slug' => '',
            'excerpt' => 'A practical guide to better learning.',
            'body' => '## Start here'."\n\n".'**Plan** a weekly routine.',
            'status' => 'draft',
            'category_id' => '',
            'featured_media_id' => '',
        ], $overrides);
    }

    public function test_editor_shows_toolbar_public_url_category_publish_and_image_actions(): void
    {
        Storage::fake('local');
        $admin = $this->member('admin', 'owner@edumia.test');
        $this->media($admin);
        $this->actingAs($admin)->get(route('admin.blogs.create'))->assertOk()
            ->assertSee('data-blog-editor', false)
            ->assertSee('data-blog-format="bold"', false)
            ->assertSee('data-blog-format="link"', false)
            ->assertSee('data-blog-copy-url', false)
            ->assertSee('aria-label="Copy public article URL"', false)
            ->assertSee('Featured Image')
            ->assertSee('Create category')
            ->assertSee('Choose image')
            ->assertSee('Publish')
            ->assertSee('data-blog-preview-button', false)
            ->assertSee('data-blog-image-select', false)
            ->assertSee('data-blog-image-cta', false)
            ->assertDontSee('data-blog-image-action=', false);
        $this->get(route('admin.blogs.media.search'))->assertOk()
            ->assertJsonPath('next_page', null);
    }

    public function test_category_autoslug_featured_image_and_safe_markdown_publish_work_end_to_end(): void
    {
        Storage::fake('local');
        $admin = $this->member('admin', 'admin@edumia.test');
        $image = $this->media($admin);
        $this->actingAs($admin);

        $categoryResponse = $this->postJson(route('admin.blogs.categories.store'), ['name' => 'Study Advice']);
        $categoryResponse->assertCreated()->assertJsonPath('name', 'Study Advice');
        $category = BlogCategory::query()->firstOrFail();

        $markdown = '## Learning strategies'."\n\n".
            '**Focus** for 20 minutes.'."\n\n".
            '![Study session](edumia-media:'.$image->id.')'."\n\n".
            '<script>alert("xss")</script>'."\n\n".
            '[Unsafe link](javascript:alert(1))';

        $this->post(route('admin.blogs.store'), $this->payload([
            'body' => $markdown, 'category_id' => $category->id,
            'featured_media_id' => $image->id,
        ]))->assertRedirect(route('admin.blogs.index'));

        $post = Blog::query()->firstOrFail();
        $this->assertSame('building-better-learning-habits', $post->slug);
        $this->assertSame($category->id, $post->category_id);
        $this->assertSame($image->id, $post->featured_media_id);
        $this->assertSame('draft', $post->status);
        $this->get(route('admin.blogs.edit', $post))->assertOk()
            ->assertSee('Study Advice')
            ->assertSee('editor-cover.png')
            ->assertSee('Learning strategies');
        $this->get(route('admin.blogs.media.preview', $image))->assertOk()
            ->assertHeader('content-type', 'image/png');
        $this->get(route('blog.image', ['slug' => $post->slug, 'media' => $image]))
            ->assertNotFound();

        $preview = $this->postJson(route('admin.blogs.preview'), ['body' => $markdown]);
        $preview->assertOk()->assertJsonStructure(['html']);
        $html = (string) $preview->json('html');
        $this->assertStringContainsString('<h2>Learning strategies</h2>', $html);
        $this->assertStringContainsString('<strong>Focus</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);

        $this->put(route('admin.blogs.update', $post), $this->payload([
            'slug' => $post->slug,
            'body' => $markdown, 'status' => 'published',
            'category_id' => $category->id, 'featured_media_id' => $image->id,
        ]))->assertRedirect(route('admin.blogs.index'));

        auth()->logout();
        $this->get(route('blog.show', $post->slug))->assertOk()
            ->assertSee('Study Advice')
            ->assertSee('Students reading at a desk')
            ->assertSee('<strong>Focus</strong>', false)
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;alert', false);
        $this->get(route('blog.index'))->assertOk()
            ->assertSee('Study Advice')
            ->assertSee(route('blog.image', ['slug' => $post->slug, 'media' => $image]), false);
        $this->get(route('blog.image', ['slug' => $post->slug, 'media' => $image]))
            ->assertOk()->assertHeader('content-type', 'image/png');

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $image))->assertStatus(422);

        $this->put(route('admin.blogs.update', $post), $this->payload([
            'slug' => $post->slug, 'status' => 'draft',
            'featured_media_id' => '', 'category_id' => $category->id,
        ]))->assertRedirect();

        $this->get(route('blog.image', ['slug' => $post->slug, 'media' => $image]))
            ->assertNotFound();
        $this->delete(route('admin.media.destroy', $image))
            ->assertRedirect(route('admin.media.index'));
        Storage::disk('local')->assertMissing($image->path);
    }

    public function test_image_picker_upload_and_media_search_are_functional_and_private(): void
    {
        Storage::fake('local');
        $admin = $this->member('admin', 'admin@edumia.test');
        $student = $this->member('student', 'learner@edumia.test');

        $this->actingAs($admin)->postJson(route('admin.blogs.media.upload'), [
            'file' => UploadedFile::fake()->image('classroom.png'),
            'alt_text' => 'Classroom study scene',
        ])->assertCreated()->assertJsonPath('name', 'classroom.png');

        $image = MediaFile::query()->firstOrFail();
        $this->assertSame('private', $image->visibility);
        $this->assertSame('local', $image->disk);
        Storage::disk('local')->assertExists($image->path);

        $this->getJson(route('admin.blogs.media.search', ['q' => 'Classroom']))->assertOk()
            ->assertJsonCount(1, 'images')
            ->assertJsonPath('images.0.id', $image->id);

        $this->actingAs($student)
            ->get(route('admin.blogs.media.preview', $image))->assertForbidden();
        $this->postJson(route('admin.blogs.categories.store'), ['name' => 'Private'])
            ->assertForbidden();
        $this->postJson(route('admin.blogs.preview'), ['body' => 'Test'])
            ->assertForbidden();
        $this->getJson(route('admin.blogs.media.search'))->assertForbidden();
        $this->postJson(route('admin.blogs.media.upload'))->assertForbidden();
        $this->assertDatabaseCount('blog_categories', 0);
    }

    public function test_invalid_foreign_image_or_category_is_rejected(): void
    {
        Storage::fake('local');
        $admin = $this->member('admin', 'admin@edumia.test');

        $this->actingAs($admin)->post(route('admin.blogs.store'), $this->payload([
            'featured_media_id' => 99999,
        ]))->assertSessionHasErrors('featured_media_id');

        $this->post(route('admin.blogs.store'), $this->payload([
            'body' => '![Unsupported](edumia-media:99999)',
        ]))->assertSessionHasErrors('featured_media_id');

        $this->post(route('admin.blogs.store'), $this->payload([
            'category_id' => 99999,
        ]))->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('blogs', 0);
    }
    public function test_failed_article_validation_restores_the_new_featured_image_preview(): void
    {
        Storage::fake('local');
        $admin = $this->member('admin', 'featured-retry@edumia.test');
        $image = $this->media($admin);
        $this->actingAs($admin)->post(route('admin.blogs.store'), $this->payload([
            'title' => '',
            'featured_media_id' => $image->id,
        ]))->assertSessionHasErrors('title');
        $this->get(route('admin.blogs.create'))->assertOk()
            ->assertSee('value="'.$image->id.'"', false)
            ->assertSee('data-blog-featured-preview', false)
            ->assertSee('editor-cover.png')
            ->assertSee('Featured image selected. It will appear with this article after saving.');

        $this->post(route('admin.blogs.store'), $this->payload([
            'title' => '',
            'featured_media_id' => '',
        ]))->assertSessionHasErrors('title');
        $this->get(route('admin.blogs.create'))->assertOk()
            ->assertSee('Choose an image to set the article cover.')
            ->assertSee('data-blog-featured-empty', false);
    }

}

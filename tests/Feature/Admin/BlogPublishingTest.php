<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BlogPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $role.'@edumia.test',
            'password' => 'Password#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function payload(string $status = 'draft', string $body = 'An original learning article.'): array
    {
        return [
            'title' => 'How to Learn',
            'slug' => 'how-to-learn',
            'excerpt' => 'Strategies for learning.',
            'body' => $body,
            'status' => $status,
        ];
    }

    public function test_draft_is_hidden_until_an_administrator_publishes_it(): void
    {
        $admin = $this->account('admin');
        $this->get('/blog')->assertOk()->assertSee('No articles yet');

        $this->actingAs($admin)->get('/admin/blogs')->assertOk()->assertSee('Blog manager');
        $this->post('/admin/blogs', $this->payload())
            ->assertRedirect(route('admin.blogs.index'));

        $post = Blog::firstOrFail();
        $this->assertSame($admin->id, $post->author_id);
        $this->assertNull($post->published_at);

        auth()->logout();
        $this->get('/blog/'.$post->slug)->assertNotFound();

        $this->actingAs($admin)->put('/admin/blogs/'.$post->id, $this->payload('published'))
            ->assertRedirect(route('admin.blogs.index'));

        $this->assertNotNull($post->fresh()->published_at);
        auth()->logout();
        $this->get('/blog')->assertOk()->assertSee('How to Learn');
        $this->get('/blog/'.$post->slug)->assertOk()->assertSee('Strategies for learning.');
        $this->get('/km/blog/'.$post->slug)->assertOk()->assertSee('How to Learn');
        $this->assertDatabaseHas('audit_events', ['subject_id' => $post->id, 'action' => 'blogs.updated']);
    }

    public function test_article_body_is_escaped_and_unpublishing_hides_it(): void
    {
        $admin = $this->account('admin');

        $this->actingAs($admin)->post('/admin/blogs', $this->payload('published', '<script>alert("bad")</script>'))
            ->assertRedirect();

        $post = Blog::firstOrFail();
        auth()->logout();
        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertDontSee('<script>alert("bad")</script>', false)
            ->assertSee('&lt;script&gt;alert', false);

        $this->actingAs($admin)->put('/admin/blogs/'.$post->id, $this->payload('draft'))
            ->assertRedirect();
        auth()->logout();
        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_non_administrator_cannot_create_articles(): void
    {
        $student = $this->account('student');
        $this->actingAs($student)->get('/admin/blogs')->assertForbidden();
        $this->post('/admin/blogs', $this->payload('published'))->assertForbidden();
        $this->assertDatabaseCount('blogs', 0);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeBaseTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KnowledgeBaseWorkflowTest extends TestCase
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

    public function test_admin_can_publish_searchable_help_center_with_topics(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->get('/admin/knowledge-base')->assertOk();

        $this->post('/admin/knowledge-base', [
            'title' => 'Learner Questions',
            'slug' => 'learner-questions',
            'category' => 'Accounts',
            'content' => 'Guides for new students.',
        ])->assertRedirect();

        $base = KnowledgeBase::firstOrFail();
        $this->assertFalse($base->published);
        auth()->logout();
        $this->get('/help')->assertOk()->assertDontSee('Learner Questions');
        $this->get('/help/learner-questions')->assertNotFound();

        $this->actingAs($admin)->post('/admin/knowledge-base/'.$base->id.'/topics', [
            'title' => 'Reset your password',
            'slug' => 'reset-password',
            'body' => 'Use the password recovery link on the sign-in page.',
            'sort_order' => 0,
        ])->assertRedirect();

        $topic = KnowledgeBaseTopic::firstOrFail();
        $this->put('/admin/knowledge-base/'.$base->id, [
            'title' => 'Learner Questions', 'slug' => 'learner-questions',
            'category' => 'Accounts', 'content' => 'Guides for new students.',
            'published' => 1,
        ])->assertRedirect();

        auth()->logout();
        $this->get('/help?q=password')->assertOk()->assertSee('Learner Questions');
        $this->get('/help/learner-questions')->assertOk()->assertSee('Reset your password');
        $this->get('/help/learner-questions/reset-password')
            ->assertOk()->assertSee('Use the password recovery link');
        $this->assertDatabaseHas('audit_events', ['action' => 'knowledge_topic.created']);

        $this->actingAs($admin)->put('/admin/knowledge-base/'.$base->id.'/topics/'.$topic->id, [
            'title' => 'Sign-in help', 'slug' => 'sign-in-help',
            'body' => 'Instructions for signing in to Edumia.',
            'sort_order' => 1,
        ])->assertRedirect();
        auth()->logout();
        $this->get('/help/learner-questions/sign-in-help')->assertOk();
        $this->get('/help/learner-questions/reset-password')->assertNotFound();
    }

    public function test_help_articles_cannot_expose_html_scripts(): void
    {
        KnowledgeBase::create([
            'title' => '<script>alert(1)</script>', 'slug' => 'safe-topic',
            'content' => 'A safe guide.', 'published' => true,
        ]);
        $this->get('/help')->assertOk()->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_non_admin_cannot_change_articles_and_topic_binding_is_scoped(): void
    {
        $admin = $this->account('admin');
        $student = $this->account('student');
        $base = KnowledgeBase::create(['title' => 'Main', 'slug' => 'main', 'published' => true]);
        $other = KnowledgeBase::create(['title' => 'Other', 'slug' => 'other', 'published' => false]);
        $topic = $other->topics()->create([
            'title' => 'Private instructions',
            'slug' => 'private-instructions',
            'body' => 'This topic must stay private.',
            'sort_order' => 0,
        ]);

        $this->actingAs($student)->get('/admin/knowledge-base')->assertForbidden();
        $this->post('/admin/knowledge-base', ['title' => 'Unauthorized'])->assertForbidden();
        $this->get('/help/other')->assertNotFound();
        $this->get('/help/main/private-instructions')->assertNotFound();
        $this->actingAs($admin)->put('/admin/knowledge-base/'.$base->id.'/topics/'.$topic->id, [
            'title' => 'Wrong update', 'slug' => 'wrong-update',
            'body' => 'A cross-container modification.',
            'sort_order' => 0,
        ])->assertNotFound();
        $this->assertSame('Private instructions', $topic->fresh()->title);
    }

    public function test_topic_slug_must_be_unique_within_help_center(): void
    {
        $admin = $this->account('admin');
        $base = KnowledgeBase::create(['title' => 'Guides', 'slug' => 'guides']);
        $base->topics()->create([
            'title' => 'First', 'slug' => 'same', 'body' => 'This is the first topic.', 'sort_order' => 0,
        ]);
        $this->actingAs($admin)->post('/admin/knowledge-base/'.$base->id.'/topics', [
            'title' => 'Second', 'slug' => 'same',
            'body' => 'This is the second topic.', 'sort_order' => 1,
        ])->assertSessionHasErrors('slug');
    }
}

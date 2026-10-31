<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $email): User
    {
        $user = User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function payload(string $name, string $slug, ?int $parent = null): array
    {
        return ['name' => $name, 'slug' => $slug, 'parent_id' => $parent, 'description' => '',
            'status' => 'active', 'sort_order' => 0];
    }

    public function test_admin_can_manage_categories_and_actions_are_audited(): void
    {
        $admin = $this->account('admin', 'admin@edumia.test');
        $this->actingAs($admin)->get('/admin/categories')->assertOk()->assertSee('Categories');
        $this->post('/admin/categories', $this->payload('Development', 'development'))
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'development')->firstOrFail();
        $this->get('/admin/categories/'.$category->id.'/edit')->assertOk();
        $this->put('/admin/categories/'.$category->id, $this->payload('Engineering', 'engineering'))
            ->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'engineering']);
        $this->assertDatabaseHas('audit_events', ['subject_id' => $category->id, 'action' => 'categories.updated']);

        $this->delete('/admin/categories/'.$category->id)->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_parent_cycle_and_duplicate_slug_are_rejected(): void
    {
        $admin = $this->account('admin', 'admin@edumia.test');
        $parent = Category::create($this->payload('Parent', 'parent'));
        $child = Category::create($this->payload('Child', 'child', $parent->id));

        $this->actingAs($admin)->put('/admin/categories/'.$parent->id, $this->payload('Parent', 'parent', $child->id))
            ->assertSessionHasErrors('parent_id');
        $this->put('/admin/categories/'.$child->id, $this->payload('Child', 'parent'))
            ->assertSessionHasErrors('slug');
        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_category_with_courses_or_children_cannot_be_deleted(): void
    {
        $admin = $this->account('admin', 'admin@edumia.test');
        $parent = Category::create($this->payload('Parent', 'parent'));
        Category::create($this->payload('Child', 'child', $parent->id));

        $this->actingAs($admin)->delete('/admin/categories/'.$parent->id)
            ->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_student_cannot_view_or_change_categories(): void
    {
        $student = $this->account('student', 'student@edumia.test');
        $this->actingAs($student)->get('/admin/categories')->assertForbidden();
        $this->post('/admin/categories', $this->payload('Hacked', 'hacked'))->assertForbidden();
        $this->assertDatabaseMissing('categories', ['slug' => 'hacked']);
    }
}

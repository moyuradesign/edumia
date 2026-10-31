<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\AuditEvent;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CategoryManagementService
{
    public function save(User $actor, array $attributes, ?Category $category = null): Category
    {
        abort_unless($actor->isAdmin(), 403);

        return DB::transaction(function () use ($actor, $attributes, $category): Category {
            $category = $category
                ? Category::query()->lockForUpdate()->findOrFail($category->getKey())
                : new Category;

            if ($category->exists && (int) ($attributes['parent_id'] ?? 0) === $category->getKey()) {
                throw ValidationException::withMessages(['parent_id' => 'A category cannot contain itself.']);
            }

            $ancestorId = $attributes['parent_id'] ?? null;
            $visited = [];
            while ($ancestorId !== null) {
                if (in_array((int) $ancestorId, $visited, true) || ($category->exists && (int) $ancestorId === $category->getKey())) {
                    throw ValidationException::withMessages(['parent_id' => 'Circular category parent is not allowed.']);
                }
                $visited[] = (int) $ancestorId;
                $ancestorId = Category::query()->findOrFail($ancestorId)->parent_id;
            }

            $before = $category->exists ? $category->only(['name', 'slug', 'parent_id', 'status', 'sort_order']) : null;
            $category->fill($attributes);
            $category->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Category::class,
                'subject_id' => $category->getKey(),
                'action' => $before ? 'categories.updated' : 'categories.created',
                'previous_values' => $before,
                'new_values' => $category->only(['name', 'slug', 'parent_id', 'status', 'sort_order']),
            ]);

            return $category;
        });
    }

    public function delete(User $actor, Category $category): void
    {
        abort_unless($actor->isAdmin(), 403);

        DB::transaction(function () use ($actor, $category): void {
            $category = Category::query()->lockForUpdate()->findOrFail($category->getKey());
            if ($category->children()->exists() || $category->courses()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Move child categories and courses before deleting this category.',
                ]);
            }
            $before = $category->only(['name', 'slug', 'parent_id', 'status']);
            $id = $category->getKey();
            $category->delete();
            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Category::class,
                'subject_id' => $id,
                'action' => 'categories.deleted',
                'previous_values' => $before,
            ]);
        });
    }
}

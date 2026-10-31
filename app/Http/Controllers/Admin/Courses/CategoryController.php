<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Courses;

use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\Learning\CategoryManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.courses.categories.index', [
            'categories' => Category::query()->with('parent')->withCount(['children', 'courses'])
                ->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.courses.categories.form', [
            'category' => new Category,
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CategoryRequest $request, CategoryManagementService $categories): RedirectResponse
    {
        $categories->save($request->user(), $request->validated());

        return to_route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.courses.categories.form', [
            'category' => $category,
            'parents' => Category::query()->whereKeyNot($category->getKey())->orderBy('name')->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category, CategoryManagementService $categories): RedirectResponse
    {
        $categories->save($request->user(), $request->validated(), $category);

        return to_route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category, CategoryManagementService $categories): RedirectResponse
    {
        $categories->delete($request->user(), $category);

        return to_route('admin.categories.index')->with('success', 'Category deleted.');
    }
}

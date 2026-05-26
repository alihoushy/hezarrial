<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::forUser(auth()->user())->orderBy('type')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Category());
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')->with('status', 'دسته‌بندی ساخته شد.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return $this->form($category);
    }

    public function update(CategoryRequest $request, Category $category, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('update', $category);
        $old = $category->toArray();
        $category->update($request->validated() + ['is_active' => $request->boolean('is_active', true)]);
        $audit->record('category.updated', $category, $old, $category->fresh()->toArray());

        return redirect()->route('categories.index')->with('status', 'دسته‌بندی به‌روز شد.');
    }

    public function destroy(Category $category, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('delete', $category);
        $old = $category->toArray();
        $category->update(['is_active' => false]);
        $category->delete();
        $audit->record('category.archived', $category, $old);

        return redirect()->route('categories.index')->with('status', 'دسته‌بندی بایگانی شد.');
    }

    private function form(Category $category): View
    {
        return view('categories.form', [
            'category' => $category,
            'parents' => Category::forUser(auth()->user())
                ->when($category->exists, fn ($query) => $query->whereKeyNot($category->id))
                ->orderBy('name')
                ->get(),
        ]);
    }
}

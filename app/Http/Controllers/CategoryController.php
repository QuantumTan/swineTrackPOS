<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('category_name')
            ->paginate(10)
            ->withQueryString();

        return view('pos.categories', [
            'categories' => $categories,
            'categoryStats' => [
                'total' => Category::query()->count(),
                'with_products' => Category::query()->has('products')->count(),
                'empty' => Category::query()->doesntHave('products')->count(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category added successfully.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('categories.index')
                ->withErrors([
                    'category_delete' => 'This category cannot be deleted while products are still assigned to it.',
                ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category deleted successfully.');
    }
}

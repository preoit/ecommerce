<?php

namespace App\Modules\Inventories\Categories\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventories\Categories\Http\Requests\StoreCategoryRequest;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Categories\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(Request $request): Response
    {
        return $this->categories->page($request);
    }

    public function create(): Response
    {
        return $this->categories->form();
    }

    public function edit(Category $category): Response
    {
        return $this->categories->form($category);
    }
    public function show(Category $category): Response
    {
        return $this->categories->publicPage($category);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request);

        return to_route('inventories.categories.index')->with('success', 'Category created successfully.');
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($request, $category);

        return to_route('inventories.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        return to_route('inventories.categories.index')->with('success', 'Category deleted successfully.');
    }
}

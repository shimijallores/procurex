<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMasterListCategoryRequest;
use App\Http\Requests\UpdateMasterListCategoryRequest;
use App\Models\MasterListCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterListCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = MasterListCategory::withCount('masterListItems')
            ->when($request->search, function ($query, string $search): void {
                $query->where('name', 'like', sprintf('%%%s%%', $search));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterListCategories/Index', [
            'categories' => $categories,
            'filters' => ['search' => $request->search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MasterListCategories/Create');
    }

    public function store(StoreMasterListCategoryRequest $storeMasterListCategoryRequest): RedirectResponse
    {
        MasterListCategory::create($storeMasterListCategoryRequest->validated());

        return redirect()->route('master-list-categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(MasterListCategory $masterListCategory): Response
    {
        return Inertia::render('MasterListCategories/Edit', [
            'category' => $masterListCategory,
        ]);
    }

    public function update(UpdateMasterListCategoryRequest $updateMasterListCategoryRequest, MasterListCategory $masterListCategory): RedirectResponse
    {
        $masterListCategory->update($updateMasterListCategoryRequest->validated());

        return redirect()->route('master-list-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(MasterListCategory $masterListCategory): RedirectResponse
    {
        $masterListCategory->delete();

        return redirect()->route('master-list-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}

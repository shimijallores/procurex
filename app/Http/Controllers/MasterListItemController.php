<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMasterListItemRequest;
use App\Http\Requests\UpdateMasterListItemRequest;
use App\Models\MasterListCategory;
use App\Models\MasterListItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterListItemController extends Controller
{
    public function index(Request $request): Response
    {
        $items = MasterListItem::with(['masterListCategory', 'supplier'])
            ->when($request->search, function ($query, string $search): void {
                $query->where('item_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('search_key', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('masterListCategory', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('supplier', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->category_id, function ($query, string $categoryId): void {
                $query->where('master_list_category_id', $categoryId);
            })
            ->when($request->phased_out !== null, function ($query) use ($request): void {
                $query->where('is_phased_out', filter_var($request->phased_out, FILTER_VALIDATE_BOOLEAN));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterListItems/Index', [
            'items' => $items,
            'categories' => MasterListCategory::where('is_active', true)->get(['id', 'name']),
            'filters' => [
                'search' => $request->search,
                'category_id' => $request->category_id,
                'phased_out' => $request->phased_out,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MasterListItems/Create', [
            'categories' => MasterListCategory::where('is_active', true)->get(['id', 'name']),
            'suppliers' => Supplier::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(StoreMasterListItemRequest $request): RedirectResponse
    {
        MasterListItem::create($request->validated());

        return redirect()->route('master-list-items.index')
            ->with('success', 'Item added to master list successfully.');
    }

    public function edit(MasterListItem $masterListItem): Response
    {
        return Inertia::render('MasterListItems/Edit', [
            'item' => $masterListItem->load(['masterListCategory', 'supplier']),
            'categories' => MasterListCategory::where('is_active', true)->get(['id', 'name']),
            'suppliers' => Supplier::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(UpdateMasterListItemRequest $request, MasterListItem $masterListItem): RedirectResponse
    {
        $masterListItem->update($request->validated());

        return redirect()->route('master-list-items.index')
            ->with('success', 'Master list item updated successfully.');
    }

    public function destroy(MasterListItem $masterListItem): RedirectResponse
    {
        $masterListItem->delete();

        return redirect()->route('master-list-items.index')
            ->with('success', 'Master list item deleted successfully.');
    }

    public function togglePhaseOut(MasterListItem $masterListItem, Request $request): RedirectResponse
    {
        $request->validate([
            'phased_out_reason' => ['nullable', 'string'],
        ]);

        $masterListItem->update([
            'is_phased_out' => !$masterListItem->is_phased_out,
            'phased_out_reason' => !$masterListItem->is_phased_out ? $request->phased_out_reason : null,
        ]);

        $message = $masterListItem->is_phased_out
            ? 'Item marked as phased out.'
            : 'Item restored from phased out.';

        return redirect()->back()->with('success', $message);
    }
}

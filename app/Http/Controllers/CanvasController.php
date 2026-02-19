<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReturnCanvasRequest;
use App\Http\Requests\StoreCanvasRequest;
use App\Models\Canvas;
use App\Models\CanvasItem;
use App\Models\CanvasItemSelection;
use App\Models\Emanating;
use App\Models\MasterListCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CanvasController extends Controller
{
    public function index(Request $request): Response
    {
        $canvasses = Canvas::with(['emanating.project.fund.office', 'createdBy'])
            ->when($request->search, function ($query, string $search): void {
                $query->whereHas('emanating', function ($q) use ($search): void {
                    $q->where('pr_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('fiscal_year', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('project', function ($p) use ($search): void {
                            $p->where('name', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->when($request->status, function ($query, string $status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Canvas::count(),
            'pending' => Canvas::where('status', 'pending')->count(),
            'completed' => Canvas::where('status', 'completed')->count(),
            'returned' => Canvas::where('status', 'returned')->count(),
        ];

        return Inertia::render('Canvasses/Index', [
            'canvasses' => $canvasses,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
        ]);
    }

    public function create(): Response
    {
        // Only approved emanatings that don't already have a pending/completed canvas
        $emanatings = Emanating::with(['project.fund.office'])
            ->where('is_approved', true)
            ->whereDoesntHave('canvasses', function ($q): void {
                $q->whereIn('status', ['pending', 'completed']);
            })
            ->latest()
            ->get();

        return Inertia::render('Canvasses/Create', [
            'emanatings' => $emanatings,
        ]);
    }

    public function store(StoreCanvasRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $emanating = Emanating::with('emanatingItems')->findOrFail($validated['emanating_id']);

            $canvas = Canvas::create([
                'emanating_id' => $emanating->id,
                'created_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            // Pre-create canvas items for each emanating item
            foreach ($emanating->emanatingItems as $emanatingItem) {
                CanvasItem::create([
                    'canvas_id' => $canvas->id,
                    'emanating_item_id' => $emanatingItem->id,
                    'computed_price' => null,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Canvas creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to create canvas. Please try again.');
        }

        return redirect()->route('canvasses.show', $canvas)
            ->with('success', 'Canvas created. You can now begin pricing.');
    }

    public function show($canvas): Response
    {
        // Fetch canvas by ID from route parameter
        $canvas = Canvas::with([
            'emanating.project.fund.office',
            'emanating.emanatingItems.ppmpItem',
            'canvasItems.emanatingItem.ppmpItem',
            'canvasItems.selections.masterListItem.masterListCategory',
            'canvasItems.selections.masterListItem.supplier',
            'createdBy',
        ])->findOrFail($canvas);

        $masterListCategories = MasterListCategory::with([
            'masterListItems' => function ($q): void {
                $q->with('supplier')->orderBy('item_name');
            },
        ])
            ->where('is_active', true)
            ->get();

        return Inertia::render('Canvasses/Show', [
            'canvas' => $canvas,
            'masterListCategories' => $masterListCategories,
            'returnReasons' => [
                'Incomplete specification indicated',
                'Requested item has been phased out',
            ],
        ]);
    }

    /**
     * Save selections for a single canvas item row and recalculate its price.
     */
    public function saveItemSelections(Request $request, Canvas $canvas, CanvasItem $canvasItem): RedirectResponse
    {
        if ($canvas->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This canvas is not in a pending state.');
        }

        $request->validate([
            'selections' => ['required', 'array'],
            'selections.*.master_list_item_id' => ['required', 'exists:master_list_items,id'],
            'selections.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'selections.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            // Remove old selections for this item
            $canvasItem->selections()->delete();

            $computedPrice = 0;

            foreach ($request->selections as $sel) {
                $subtotal = (float) $sel['quantity'] * (float) $sel['unit_price'];
                $computedPrice += $subtotal;

                CanvasItemSelection::create([
                    'canvas_item_id' => $canvasItem->id,
                    'master_list_item_id' => $sel['master_list_item_id'],
                    'quantity' => $sel['quantity'],
                    'unit_price' => $sel['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $canvasItem->update(['computed_price' => $computedPrice]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Canvas item selection save failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to save selections.');
        }

        return redirect()->back()->with('success', 'Selections saved.');
    }

    /**
     * Complete the canvas: write computed prices back to emanating items.
     */
    public function complete(Request $request, Canvas $canvas): RedirectResponse
    {
        if ($canvas->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending canvasses can be completed.');
        }

        DB::beginTransaction();
        try {
            $canvas->load('canvasItems.emanatingItem');

            $totalAmount = 0;

            foreach ($canvas->canvasItems as $canvasItem) {
                $price = (float) ($canvasItem->computed_price ?? 0);
                $totalAmount += $price;

                // Write back to emanating_item
                $canvasItem->emanatingItem->update(['total_price' => $price]);
            }

            // Flag reimbursement if total < 10,000
            $emanating = $canvas->emanating;
            $emanating->update(['reimbursement' => $totalAmount < 10000]);

            $canvas->update([
                'status' => 'completed',
                'total_amount' => $totalAmount,
                'completed_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Canvas completion failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to complete canvas.');
        }

        return redirect()->route('canvasses.show', $canvas)
            ->with('success', 'Canvas completed and prices saved to emanating.');
    }

    /**
     * Return the canvas with a reason.
     */
    public function return(ReturnCanvasRequest $request, Canvas $canvas): RedirectResponse
    {
        if ($canvas->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending canvasses can be returned.');
        }

        DB::beginTransaction();
        try {
            $canvas->update([
                'status' => 'returned',
                'return_reason' => $request->validated()['return_reason'],
            ]);

            // Reset the associated emanating's is_approved status
            $canvas->emanating->update(['is_approved' => false]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Canvas return failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to return canvas.');
        }

        return redirect()->route('canvasses.index')
            ->with('success', 'Canvas returned successfully.');
    }

    public function destroy(Canvas $canvas): RedirectResponse
    {
        $canvas->delete();

        return redirect()->route('canvasses.index')
            ->with('success', 'Canvas deleted.');
    }
}

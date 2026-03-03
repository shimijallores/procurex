<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCanvasRequest;
use App\Models\Canvas;
use App\Models\CanvasItem;
use App\Models\CanvasItemSelection;
use App\Models\Emanating;
use App\Models\EmanatingItem;
use App\Models\MasterListCategory;
use App\Models\MasterListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CanvasController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = Canvas::with([
            'emanating.project.fund.office',
            'emanating.fund.office',
            'emanating.fund.projectCode',
            'createdBy',
        ])
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
        ];

        return Inertia::render('Canvasses/Index', [
            'canvasses' => $lengthAwarePaginator,
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
        $emanatings = Emanating::with([
            'project.fund.office',
            'fund.office',
            'fund.projectCode',
        ])
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

    public function store(StoreCanvasRequest $storeCanvasRequest): RedirectResponse
    {
        $validated = $storeCanvasRequest->validated();

        DB::beginTransaction();
        try {
            $emanating = Emanating::with('emanatingItems.ppmpItem')->findOrFail($validated['emanating_id']);
            $masterListItems = MasterListItem::query()
                ->where('is_phased_out', false)
                ->get();

            $canvas = Canvas::create([
                'emanating_id' => $emanating->id,
                'created_by' => $storeCanvasRequest->user()->id,
                'status' => 'pending',
            ]);

            $allItemsAutoPriced = true;
            $missingItems = [];
            $itemsWithoutDefaultPrice = [];

            // Pre-create canvas items for each emanating item
            foreach ($emanating->emanatingItems as $emanatingItem) {
                $canvasItem = CanvasItem::create([
                    'canvas_id' => $canvas->id,
                    'emanating_item_id' => $emanatingItem->id,
                    'computed_price' => null,
                ]);

                $matchedMasterListItem = $this->findBestMasterListItemMatch($emanatingItem, $masterListItems);

                if (! $matchedMasterListItem) {
                    $allItemsAutoPriced = false;
                    $missingItems[] = (string) ($emanatingItem->name ?: $emanatingItem->ppmpItem?->name ?: ('Item #' . $emanatingItem->id));

                    continue;
                }

                if ($matchedMasterListItem->default_unit_price === null) {
                    $allItemsAutoPriced = false;
                    $itemsWithoutDefaultPrice[] = (string) ($emanatingItem->name ?: $emanatingItem->ppmpItem?->name ?: ('Item #' . $emanatingItem->id));

                    continue;
                }

                $quantity = (float) ($emanatingItem->quantity ?? 0);
                $unitPrice = (float) $matchedMasterListItem->default_unit_price;
                $subtotal = round($quantity * $unitPrice, 2);

                CanvasItemSelection::create([
                    'canvas_item_id' => $canvasItem->id,
                    'master_list_item_id' => $matchedMasterListItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $canvasItem->update([
                    'computed_price' => $subtotal,
                ]);
            }

            if ($allItemsAutoPriced && $canvas->canvasItems()->exists()) {
                $this->finalizeCanvas($canvas);

                DB::commit();

                return redirect()->route('canvasses.show', $canvas)
                    ->with('success', 'Canvas created and auto-completed using master list prices. You may proceed to Purchase Request.');
            }

            DB::commit();

            $missingCount = count($missingItems);
            $missingPriceCount = count($itemsWithoutDefaultPrice);

            if ($missingCount > 0 && $missingPriceCount > 0) {
                $message = sprintf(
                    'Canvas created. %d item(s) were not found in the master list and %d item(s) have no default unit price. Add/fix master list entries, then complete canvassing.',
                    $missingCount,
                    $missingPriceCount,
                );
            } elseif ($missingCount > 0) {
                $message = sprintf('Canvas created. %d item(s) were not found in the master list. Add missing items and complete canvassing.', $missingCount);
            } elseif ($missingPriceCount > 0) {
                $message = sprintf('Canvas created. %d item(s) were found in master list but have no default unit price. Update master list prices and complete canvassing.', $missingPriceCount);
            } else {
                $message = 'Canvas created. You can now begin pricing.';
            }
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to create canvas. Please try again.');
        }

        return redirect()->route('canvasses.show', $canvas)
            ->with('success', $message);
    }

    public function show($canvas): Response
    {
        // Fetch canvas by ID from route parameter
        $canvas = Canvas::with([
            'emanating.project.fund.office',
            'emanating.fund.office',
            'emanating.fund.projectCode',
            'emanating.ppmp',
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
            'selections' => ['nullable', 'array'],
            'selections.*.master_list_item_id' => ['required_with:selections', 'exists:master_list_items,id'],
            'selections.*.quantity' => ['required_with:selections', 'numeric', 'min:0.01'],
            'selections.*.unit_price' => ['required_with:selections', 'numeric', 'min:0'],
            'computed_price' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::beginTransaction();
        try {
            // Remove old selections for this item
            $canvasItem->selections()->delete();

            $selections = $request->input('selections', []);

            foreach ($selections as $sel) {
                $subtotal = (float) $sel['quantity'] * (float) $sel['unit_price'];

                CanvasItemSelection::create([
                    'canvas_item_id' => $canvasItem->id,
                    'master_list_item_id' => $sel['master_list_item_id'],
                    'quantity' => $sel['quantity'],
                    'unit_price' => $sel['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $computedPrice = (float) $request->input('computed_price');

            $canvasItem->update(['computed_price' => $computedPrice]);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

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
            $this->finalizeCanvas($canvas);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to complete canvas.');
        }

        return redirect()->route('canvasses.show', $canvas)
            ->with('success', 'Canvas completed and prices saved to emanating.');
    }

    public function destroy(Canvas $canvas): RedirectResponse
    {
        $canvas->delete();

        return redirect()->route('canvasses.index')
            ->with('success', 'Canvas deleted.');
    }

    public function delete(Canvas $canvas): RedirectResponse
    {
        if ($canvas->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending canvasses can be deleted.');
        }

        DB::beginTransaction();
        try {
            $canvas->loadMissing('canvasItems.selections');

            foreach ($canvas->canvasItems as $canvasItem) {
                $canvasItem->selections()->delete();
            }

            $canvas->canvasItems()->delete();
            $canvas->delete();

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete canvas. Please try again.');
        }

        return redirect()->route('canvasses.index')
            ->with('success', 'Canvas deleted.');
    }

    private function finalizeCanvas(Canvas $canvas): void
    {
        $canvas->loadMissing('canvasItems.emanatingItem', 'emanating');

        $totalAmount = 0;

        foreach ($canvas->canvasItems as $canvasItem) {
            $price = (float) ($canvasItem->computed_price ?? 0);
            $totalAmount += $price;

            $canvasItem->emanatingItem->update(['total_price' => $price]);
        }

        $canvas->emanating->update([
            'reimbursement' => $totalAmount < 10000,
            'is_canvassed' => true,
        ]);

        $canvas->update([
            'status' => 'completed',
            'total_amount' => $totalAmount,
            'completed_at' => now(),
        ]);
    }

    private function findBestMasterListItemMatch(EmanatingItem $emanatingItem, Collection $masterListItems): ?MasterListItem
    {
        $primaryItemName = (string) ($emanatingItem->name ?? '');
        $fallbackItemName = (string) ($emanatingItem->ppmpItem?->name ?? '');
        $itemName = $primaryItemName !== '' ? $primaryItemName : $fallbackItemName;
        $normalizedItemName = $this->normalizeItemText($itemName);
        $normalizedUnit = $this->normalizeUnitText((string) ($emanatingItem->unit ?? ''));

        if ($normalizedItemName === '') {
            return null;
        }

        $exactMatches = $masterListItems->filter(function (MasterListItem $masterListItem) use ($normalizedItemName, $normalizedUnit): bool {
            $normalizedMasterName = $this->normalizeItemText((string) $masterListItem->item_name);
            $normalizedSearchKey = $this->normalizeItemText((string) ($masterListItem->search_key ?? ''));
            $matchesName = $normalizedMasterName === $normalizedItemName || ($normalizedSearchKey !== '' && $normalizedSearchKey === $normalizedItemName);

            if (! $matchesName) {
                return false;
            }

            return $this->unitsAreCompatible($normalizedUnit, (string) ($masterListItem->unit ?? ''));
        });

        if ($exactMatches->isNotEmpty()) {
            return $exactMatches->sortBy('default_unit_price')->first();
        }

        $fuzzyMatches = $masterListItems->filter(function (MasterListItem $masterListItem) use ($normalizedItemName, $normalizedUnit): bool {
            $normalizedMasterName = $this->normalizeItemText((string) $masterListItem->item_name);
            $normalizedSearchKey = $this->normalizeItemText((string) ($masterListItem->search_key ?? ''));

            $matchesName = str_contains($normalizedMasterName, $normalizedItemName)
                || str_contains($normalizedItemName, $normalizedMasterName)
                || ($normalizedSearchKey !== '' && (str_contains($normalizedSearchKey, $normalizedItemName) || str_contains($normalizedItemName, $normalizedSearchKey)));

            if (! $matchesName) {
                return false;
            }

            return $this->unitsAreCompatible($normalizedUnit, (string) ($masterListItem->unit ?? ''));
        });

        if ($fuzzyMatches->isNotEmpty()) {
            return $fuzzyMatches->sortBy('default_unit_price')->first();
        }

        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($masterListItems as $masterListItem) {
            if (! $this->unitsAreCompatible($normalizedUnit, (string) ($masterListItem->unit ?? ''))) {
                continue;
            }

            $nameScorePrimary = $this->tokenSimilarityScore($itemName, (string) $masterListItem->item_name);
            $searchKeyScorePrimary = $this->tokenSimilarityScore($itemName, (string) ($masterListItem->search_key ?? ''));
            $score = max($nameScorePrimary, $searchKeyScorePrimary);

            if ($fallbackItemName !== '' && $fallbackItemName !== $itemName) {
                $nameScoreFallback = $this->tokenSimilarityScore($fallbackItemName, (string) $masterListItem->item_name);
                $searchKeyScoreFallback = $this->tokenSimilarityScore($fallbackItemName, (string) ($masterListItem->search_key ?? ''));
                $score = max($score, $nameScoreFallback, $searchKeyScoreFallback);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $masterListItem;

                continue;
            }

            if ($score === $bestScore && $bestMatch && (float) $masterListItem->default_unit_price < (float) $bestMatch->default_unit_price) {
                $bestMatch = $masterListItem;
            }
        }

        if ($bestScore >= 0.55) {
            return $bestMatch;
        }

        return null;
    }

    private function unitsAreCompatible(string $emanatingUnit, string $masterListUnit): bool
    {
        $normalizedMasterUnit = $this->normalizeUnitText($masterListUnit);

        if ($emanatingUnit === '' || $normalizedMasterUnit === '') {
            return true;
        }

        return $emanatingUnit === $normalizedMasterUnit;
    }

    private function normalizeUnitText(string $unit): string
    {
        $normalized = mb_strtolower(trim($unit));
        $normalized = preg_replace('/[^\pL\pN\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if ($normalized === '') {
            return '';
        }

        return match ($normalized) {
            'pc', 'pcs', 'piece', 'pieces', 'item', 'items' => 'piece',
            'pax', 'person', 'persons', 'participant', 'participants', 'head', 'heads' => 'person',
            'set', 'sets' => 'set',
            'lot', 'lots' => 'lot',
            'unit', 'units' => 'unit',
            default => $normalized,
        };
    }

    private function tokenSimilarityScore(string $left, string $right): float
    {
        $leftTokens = $this->tokenizeComparableText($left);
        $rightTokens = $this->tokenizeComparableText($right);

        if ($leftTokens === [] || $rightTokens === []) {
            return 0.0;
        }

        $leftSet = array_values(array_unique($leftTokens));
        $rightSet = array_values(array_unique($rightTokens));

        $intersection = array_intersect($leftSet, $rightSet);
        $union = array_unique(array_merge($leftSet, $rightSet));

        if ($union === []) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeComparableText(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = str_replace("\u{00A0}", ' ', $normalized);
        $normalized = str_replace('&', ' and ', $normalized);
        $normalized = preg_replace('/[^\pL\pN\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        if ($normalized === '') {
            return [];
        }

        $tokens = explode(' ', trim($normalized));
        $stopWords = ['and', 'for', 'with', 'the', 'a', 'an', 'of'];

        $filtered = [];
        foreach ($tokens as $token) {
            if ($token === '' || in_array($token, $stopWords, true)) {
                continue;
            }

            $filtered[] = $this->singularizeToken($token);
        }

        return $filtered;
    }

    private function singularizeToken(string $token): string
    {
        if (mb_strlen($token) <= 3) {
            return $token;
        }

        if (str_ends_with($token, 'ies')) {
            return substr($token, 0, -3) . 'y';
        }

        if (str_ends_with($token, 'ses')) {
            return substr($token, 0, -2);
        }

        if (str_ends_with($token, 's')) {
            return substr($token, 0, -1);
        }

        return $token;
    }

    private function normalizeItemText(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = str_replace("\u{00A0}", ' ', $normalized);
        $normalized = str_replace(['’', '“', '”', "\t", "\r", "\n"], ['\'', '"', '"', ' ', ' ', ' '], $normalized);
        $normalized = preg_replace('/[^\pL\pN\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}

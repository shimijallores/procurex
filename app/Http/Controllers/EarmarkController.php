<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEarmarkRequest;
use App\Http\Requests\UpdateEarmarkRequest;
use App\Models\Earmark;
use App\Models\Fund;
use App\Models\Office;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class EarmarkController extends Controller
{
    private const RETURN_REASONS = [
        'Incorrect Unit of Issue',
        'Insufficient Allotment',
        'Budget Exceeded',
        'Incomplete Documentation',
        'Wrong Fund Charging',
        'Mismatch in Quantity',
        'Invalid Item Description',
        'Duplicate Request',
    ];

    public function index(Request $request): Response
    {
        // Earmarks listing
        $earmarkQuery = Earmark::with(['purchaseRequest.office', 'fund'])
            ->when($request->search, function ($q, string $search): void {
                $q->where('earmark_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('purchaseRequest', fn($pr) => $pr->where('pr_no', 'like', sprintf('%%%s%%', $search)))
                    ->orWhereHas('purchaseRequest.office', fn($o) => $o->where('name', 'like', sprintf('%%%s%%', $search)));
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $year): void {
                $q->whereYear('earmark_date', $year);
            });

        $earmarks = (clone $earmarkQuery)->latest()->paginate(10)->withQueryString();

        // PRs pending budget review
        $pendingReviewQuery = PurchaseRequest::with(['office', 'fund', 'items'])
            ->where('status', 'for_budget_review')
            ->when($request->pr_search, function ($q, string $search): void {
                $q->where('pr_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('office', fn($o) => $o->where('name', 'like', sprintf('%%%s%%', $search)));
            });

        $pendingReviews = (clone $pendingReviewQuery)->latest()->paginate(10, ['*'], 'pending_page')->withQueryString();

        $stats = [
            'total_earmarks'   => Earmark::count(),
            'pending_review'   => PurchaseRequest::where('status', 'for_budget_review')->count(),
            'approved_this_month' => Earmark::whereMonth('earmark_date', now()->month)
                ->whereYear('earmark_date', now()->year)->count(),
            'total_certified'  => Earmark::sum('certified_amount'),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('Earmarks/Index', [
            'earmarks'       => $earmarks,
            'pendingReviews' => $pendingReviews,
            'stats'          => $stats,
            'offices'        => $offices,
            'fiscalYears'    => $fiscalYears,
            'returnReasons'  => self::RETURN_REASONS,
            'filters'        => [
                'search'      => $request->search,
                'office_id'   => $request->office_id,
                'fiscal_year' => $request->fiscal_year,
                'pr_search'   => $request->pr_search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        // Load the related PR if pr_id provided
        $purchaseRequest = null;
        if ($request->pr_id) {
            $purchaseRequest = PurchaseRequest::with([
                'office',
                'fund',
                'items.emanatingItem.ppmpItem.category',
                'emanating.project',
            ])->findOrFail($request->pr_id);

            // Add category_name for easier access
            $purchaseRequest->category_name = $purchaseRequest->items
                ->first()?->emanatingItem?->ppmpItem?->category?->name ?? '';
        }

        // PRs eligible for earmark creation (for_budget_review, no existing earmark)
        $eligiblePRs = PurchaseRequest::with([
            'office',
            'fund',
            'items.emanatingItem.ppmpItem.category',
        ])
            ->where('status', 'for_budget_review')
            ->whereDoesntHave('earmark')
            ->latest()
            ->get()
            ->map(function ($pr) {
                // Add category_name to PR for easier access in Vue
                $categoryName = $pr->items
                    ->first()?->emanatingItem?->ppmpItem?->category?->name ?? '';
                return array_merge($pr->toArray(), ['category_name' => $categoryName]);
            });

        $funds = Fund::orderBy('name')->get(['id', 'name', 'code', 'type']);

        return Inertia::render('Earmarks/Create', [
            'purchaseRequest' => $purchaseRequest,
            'eligiblePRs'     => $eligiblePRs,
            'funds'           => $funds,
        ]);
    }

    public function store(StoreEarmarkRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $pr = PurchaseRequest::with([
                'items.emanatingItem.ppmpItem.category',
            ])->findOrFail($validated['purchase_request_id']);

            if ($pr->status !== 'for_budget_review') {
                return redirect()->back()
                    ->with('error', 'Only PRs with status "For Budget Review" can be issued an earmark.');
            }

            if (empty($validated['expense_class'])) {
                $validated['expense_class'] = $pr->items
                    ->first()?->emanatingItem?->ppmpItem?->category?->name;
            }

            // Create earmark
            $earmark = Earmark::create($validated);

            // Approve the PR
            $pr->update(['status' => 'approved']);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Earmark creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to create Earmark. Please try again.');
        }

        return redirect()->route('earmarks.show', $earmark)
            ->with('success', 'Earmark issued successfully. PR is now approved.');
    }

    public function show(Earmark $earmark): Response
    {
        $earmark->load([
            'purchaseRequest.office',
            'purchaseRequest.fund',
            'purchaseRequest.items.emanatingItem.ppmpItem.category',
            'purchaseRequest.emanating.project',
            'fund',
        ]);

        return Inertia::render('Earmarks/Show', [
            'earmark' => $earmark,
        ]);
    }

    public function edit(Earmark $earmark): Response
    {
        $earmark->load([
            'purchaseRequest.office',
            'purchaseRequest.items.emanatingItem.ppmpItem.category',
            'fund',
        ]);
        $funds = Fund::orderBy('name')->get(['id', 'name', 'code', 'type']);

        return Inertia::render('Earmarks/Edit', [
            'earmark' => $earmark,
            'funds'   => $funds,
        ]);
    }

    public function update(UpdateEarmarkRequest $request, Earmark $earmark): RedirectResponse
    {
        $earmark->update($request->validated());

        return redirect()->route('earmarks.show', $earmark)
            ->with('success', 'Earmark updated successfully.');
    }

    public function destroy(Earmark $earmark): RedirectResponse
    {
        // Revert PR status if deleting earmark
        if ($earmark->purchaseRequest?->status === 'approved') {
            $earmark->purchaseRequest->update(['status' => 'for_budget_review']);
        }

        $earmark->delete();

        return redirect()->route('earmarks.index')
            ->with('success', 'Earmark deleted.');
    }

    /**
     * Return a PR to the requesting office for correction.
     */
    public function budgetReturn(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($purchaseRequest->status !== 'for_budget_review') {
            return redirect()->back()
                ->with('error', 'Only PRs under budget review can be returned.');
        }

        DB::beginTransaction();
        try {
            $purchaseRequest->update([
                'status'  => 'returned',
                'remarks' => $request->reason,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Budget return failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to return PR.');
        }

        return redirect()->route('earmarks.index')
            ->with('success', 'Purchase Request returned to the requesting office for correction.');
    }

    /**
     * Generate and stream the Earmark Certification as a PDF.
     */
    public function printPdf(Earmark $earmark)
    {
        $earmark->load([
            'purchaseRequest.office',
            'purchaseRequest.fund',
            'purchaseRequest.items.emanatingItem.ppmpItem.category',
            'purchaseRequest.emanating.project',
            'fund',
        ]);

        return Pdf::view('pdf.earmark-certification', [
            'earmark'        => $earmark,
            'pr'             => $earmark->purchaseRequest,
            'certifiedBy'    => 'VICTORIA B. CULIAT',
            'certifiedByDesig' => 'Provincial Budget Officer',
        ])
            ->format('a4')
            ->name('Earmark-' . $earmark->earmark_no . '.pdf')
            ->inline();
    }
}

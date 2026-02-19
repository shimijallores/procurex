<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmanatingRequest;
use App\Http\Requests\UpdateEmanatingRequest;
use App\Imports\EmanatingImport;
use App\Models\Emanating;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmanatingController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = Emanating::with(['project.fund.office', 'ppmp', 'ppmpCategory'])
            ->when($request->search, function ($query, string $search): void {
                $query->where('fiscal_year', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('pr_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('charged_to_code', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('project', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('project.fund', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($query, string $officeId): void {
                $query->whereHas('project.fund.office', function ($q) use ($officeId): void {
                    $q->where('office_id', $officeId);
                });
            })
            ->when($request->fiscal_year, function ($query, string $fiscalYear): void {
                $query->where('fiscal_year', $fiscalYear);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Calculate statistics across all emanatings (not just current page)
        $builder = Emanating::query();
        if ($request->search) {
            $builder->where(function ($query) use ($request): void {
                $query->where('fiscal_year', 'like', sprintf('%%%s%%', $request->search))
                    ->orWhere('pr_no', 'like', sprintf('%%%s%%', $request->search))
                    ->orWhere('charged_to_code', 'like', sprintf('%%%s%%', $request->search))
                    ->orWhereHas('project', function ($q) use ($request): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $request->search));
                    })
                    ->orWhereHas('project.fund', function ($q) use ($request): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $request->search));
                    });
            });
        }
        if ($request->office_id) {
            $builder->whereHas('project.fund.office', function ($q) use ($request): void {
                $q->where('office_id', $request->office_id);
            });
        }
        if ($request->fiscal_year) {
            $builder->where('fiscal_year', $request->fiscal_year);
        }

        $stats = [
            'total' => $builder->count(),
            'approved' => (clone $builder)->where('is_approved', true)->count(),
            'pending' => (clone $builder)->where('is_approved', false)->whereNull('rejection_reason')->count(),
            'rejected' => (clone $builder)->whereNotNull('rejection_reason')->count(),
        ];

        // Get unique offices and fiscal years for filters
        $offices = Emanating::with('project.fund.office')
            ->get()
            ->pluck('project.fund.office')
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn($office) => [$office->id => $office->name]);

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('Emanatings/Index', [
            'emanatings' => $lengthAwarePaginator,
            'stats' => $stats,
            'offices' => $offices,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'fiscal_year' => $request->fiscal_year,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Emanatings/Create', [
            'ppmps' => PPMP::with('office', 'project')
                ->where('is_approved', true)
                ->get(['id', 'office_id', 'project_id', 'fiscal_year', 'account_code']),
            'ppmpCategories' => PPMPCategory::with('ppmp.office', 'ppmp.project')
                ->whereHas('ppmp', function ($query): void {
                    $query->where('is_approved', true);
                })
                ->get(['id', 'ppmp_id', 'code', 'name']),
        ]);
    }

    public function store(StoreEmanatingRequest $storeEmanatingRequest): RedirectResponse
    {
        $validated = $storeEmanatingRequest->validated();

        DB::beginTransaction();
        try {
            // Get PPMP and its project
            $ppmp = PPMP::with('project')->findOrFail($validated['ppmp_id']);
            $ppmpCategory = PPMPCategory::findOrFail($validated['ppmp_category_id']);

            // CSV file is required, so we can assume it exists
            $csvPath = $storeEmanatingRequest->file('csv_file')->store('emanatings', 'public');

            // Import CSV - this creates the Emanating record and items
            $emanatingImport = new EmanatingImport($ppmp->project, $ppmp, (int) $validated['ppmp_category_id']);
            Excel::import($emanatingImport, $storeEmanatingRequest->file('csv_file'));

            // Get the created emanating record (we need to find it)
            // The import creates it but doesn't return it, so we get the latest one
            $emanating = Emanating::latest()->first();

            if ($emanating) {
                // Check if any OTHER emanating exists with same ppmp_id + charged_to_code
                $existingEmanating = Emanating::where('ppmp_id', $validated['ppmp_id'])
                    ->where('charged_to_code', $emanating->charged_to_code)
                    ->where('id', '!=', $emanating->id)
                    ->first();

                // If existing one found, automatically set as addendum
                $isAddendum = $validated['is_addendum'] ?? false;
                if ($existingEmanating) {
                    $isAddendum = true;
                    // Delete the old one
                    $existingEmanating->delete();
                }

                // Update with validated data and CSV path
                $emanating->update([
                    'ppmp_id' => $validated['ppmp_id'],
                    'project_id' => $ppmp->project_id, // Get from PPMP
                    'ppmp_category_id' => $validated['ppmp_category_id'],
                    'pr_no' => $validated['pr_no'] ?? null,
                    'is_addendum' => $isAddendum,
                    'remarks' => $validated['remarks'] ?? null,
                    'reimbursement' => $validated['reimbursement'] ?? false,
                    'csv_path' => $csvPath,
                    'items_match_ppmp' => $emanatingImport->getItemsMatchPPMP(),
                ]);
            }

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request created successfully from CSV.');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('[Emanating Store] Failed to create: ' . $exception->getMessage());

            return back()->withErrors(['error' => 'Failed to create Emanating Request: ' . $exception->getMessage()]);
        }
    }

    public function show(Emanating $emanating): Response
    {
        $emanating->load([
            'project.fund.office',
            'ppmp.categories.items',
            'ppmpCategory.items',
            'emanatingItems.ppmpItem',
            'approvedBy',
        ]);

        // Compare emanating items with PPMP items
        $comparisonData = $this->compareWithPPMP($emanating);

        return Inertia::render('Emanatings/Show', [
            'emanating' => $emanating,
            'comparison' => $comparisonData,
        ]);
    }

    public function edit(Emanating $emanating): Response
    {
        $emanating->load(['project.fund', 'ppmp', 'ppmpCategory', 'emanatingItems']);

        return Inertia::render('Emanatings/Edit', [
            'emanating' => $emanating,
            'ppmps' => PPMP::with('office', 'project')
                ->where('is_approved', true)
                ->get(['id', 'office_id', 'project_id', 'fiscal_year', 'account_code']),
            'ppmpCategories' => PPMPCategory::with('ppmp.office', 'ppmp.project')
                ->whereHas('ppmp', function ($query): void {
                    $query->where('is_approved', true);
                })
                ->get(['id', 'ppmp_id', 'code', 'name']),
        ]);
    }

    public function update(UpdateEmanatingRequest $updateEmanatingRequest, Emanating $emanating): RedirectResponse
    {
        $validated = $updateEmanatingRequest->validated();

        DB::beginTransaction();
        try {
            // Only update editable fields (CSV-imported fields are immutable)
            $emanating->update([
                'ppmp_id' => $validated['ppmp_id'] ?? $emanating->ppmp_id,
                'ppmp_category_id' => $validated['ppmp_category_id'] ?? $emanating->ppmp_category_id,
                'pr_no' => $validated['pr_no'] ?? $emanating->pr_no,
                'is_addendum' => $validated['is_addendum'] ?? $emanating->is_addendum,
                'remarks' => $validated['remarks'] ?? $emanating->remarks,
                'reimbursement' => $validated['reimbursement'] ?? $emanating->reimbursement,
            ]);

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request updated successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('[Emanating Update] Failed to update: ' . $exception->getMessage());

            return back()->withErrors(['error' => 'Failed to update Emanating Request: ' . $exception->getMessage()]);
        }
    }

    public function destroy(Emanating $emanating): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $emanating->delete();
            DB::commit();

            return redirect()->route('emanatings.index')
                ->with('success', 'Emanating Request deleted successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('[Emanating Destroy] Failed to delete: ' . $exception->getMessage());

            return back()->withErrors(['error' => 'Failed to delete Emanating Request: ' . $exception->getMessage()]);
        }
    }

    /**
     * Handle CSV import for existing Emanating
     */
    public function import(Request $request, Emanating $emanating): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        DB::beginTransaction();
        try {
            // Delete existing items
            $emanating->emanatingItems()->delete();

            // Delete old CSV file if exists
            if ($emanating->csv_path && Storage::disk('public')->exists($emanating->csv_path)) {
                Storage::disk('public')->delete($emanating->csv_path);
            }

            // Store new CSV file
            $csvPath = $request->file('csv_file')->store('emanatings', 'public');

            // Import from CSV/Excel - include ppmp_category_id from existing emanating
            $emanatingImport = new EmanatingImport(
                $emanating->project,
                $emanating->ppmp,
                $emanating->ppmp_category_id
            );
            Excel::import($emanatingImport, $request->file('csv_file'));

            // Update Emanating with new csv_path and matching status
            $emanating->update([
                'csv_path' => $csvPath,
                'items_match_ppmp' => $emanatingImport->getItemsMatchPPMP(),
            ]);

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'CSV data imported successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('[Emanating Import] Failed to import: ' . $exception->getMessage());

            return back()->withErrors(['error' => 'Failed to import CSV: ' . $exception->getMessage()]);
        }
    }

    /**
     * Download the uploaded CSV file
     */
    public function downloadCsv(Emanating $emanating): BinaryFileResponse
    {
        if (! $emanating->csv_path || ! Storage::disk('public')->exists($emanating->csv_path)) {
            abort(404, 'CSV file not found.');
        }

        return response()->download(
            Storage::disk('public')->path($emanating->csv_path),
            sprintf('Emanating-%s-FY%s.csv', $emanating->pr_no ?? $emanating->id, $emanating->fiscal_year)
        );
    }

    /**
     * Approve an Emanating
     */
    public function approve(Emanating $emanating): RedirectResponse
    {
        Log::info('[Emanating Approve] Starting approval process', [
            'emanating_id' => $emanating->id,
            'is_approved' => $emanating->is_approved,
            'user_id' => auth()->id(),
        ]);

        // Check if already approved
        if ($emanating->is_approved) {
            Log::warning('[Emanating Approve] Emanating already approved', ['emanating_id' => $emanating->id]);

            return back()->withErrors(['error' => 'This Emanating Request is already approved.']);
        }

        // Check if related PPMP is approved
        if ($emanating->ppmp && ! $emanating->ppmp->is_approved) {
            Log::warning('[Emanating Approve] Related PPMP not approved', [
                'emanating_id' => $emanating->id,
                'ppmp_id' => $emanating->ppmp_id,
            ]);

            return back()->withErrors(['error' => 'Cannot approve Emanating Request. The related PPMP must be approved first.']);
        }

        // Check if items match PPMP by performing actual comparison
        $comparison = $this->compareWithPPMP($emanating);
        if ($comparison['status'] !== 'all_matched') {
            Log::warning('[Emanating Approve] Items do not match PPMP', [
                'emanating_id' => $emanating->id,
                'comparison_status' => $comparison['status'],
            ]);

            return back()->withErrors(['error' => 'Cannot approve Emanating Request. Items do not match the PPMP.']);
        }

        DB::beginTransaction();
        try {
            Log::info('[Emanating Approve] Attempting database update', ['emanating_id' => $emanating->id]);

            $emanating->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejection_reason' => null,
                'status' => 'approved',
            ]);

            Log::info('[Emanating Approve] Database update successful', [
                'emanating_id' => $emanating->id,
                'approved_by' => auth()->id(),
            ]);

            DB::commit();

            Log::info('[Emanating Approve] Transaction committed successfully', ['emanating_id' => $emanating->id]);

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request approved successfully.');
        } catch (\Exception $exception) {
            Log::error('[Emanating Approve] Exception caught', [
                'emanating_id' => $emanating->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to approve Emanating Request: ' . $exception->getMessage()]);
        }
    }

    /**
     * Reject an Emanating
     */
    public function reject(Request $request, Emanating $emanating): RedirectResponse
    {
        Log::info('[Emanating Reject] Starting rejection process', [
            'emanating_id' => $emanating->id,
            'is_approved' => $emanating->is_approved,
            'user_id' => auth()->id(),
        ]);

        // Check if already approved
        if ($emanating->is_approved) {
            Log::warning('[Emanating Reject] Cannot reject approved Emanating', ['emanating_id' => $emanating->id]);

            return back()->withErrors(['error' => 'Cannot reject an approved Emanating Request.']);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        Log::debug('[Emanating Reject] Rejection reason provided', [
            'emanating_id' => $emanating->id,
            'reason_length' => strlen($request->rejection_reason),
        ]);

        DB::beginTransaction();
        try {
            Log::info('[Emanating Reject] Attempting database update', ['emanating_id' => $emanating->id]);

            $emanating->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => $request->rejection_reason,
                'status' => 'rejected',
            ]);

            Log::info('[Emanating Reject] Database update successful', [
                'emanating_id' => $emanating->id,
                'reason_saved' => ! empty($emanating->rejection_reason),
            ]);

            DB::commit();

            Log::info('[Emanating Reject] Transaction committed successfully', ['emanating_id' => $emanating->id]);

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request rejected. Please address the issues and upload a new CSV or create an addendum.');
        } catch (\Exception $exception) {
            Log::error('[Emanating Reject] Exception caught', [
                'emanating_id' => $emanating->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to reject Emanating Request: ' . $exception->getMessage()]);
        }
    }

    /**
     * Compare emanating items with PPMP items
     */
    private function compareWithPPMP(Emanating $emanating): array
    {
        if (! $emanating->ppmpCategory) {
            return [
                'status' => 'no_ppmp_category',
                'message' => 'No PPMP category linked to this emanating request.',
                'items' => [],
            ];
        }

        $ppmpItems = $emanating->ppmpCategory->items;
        $emanatingItems = $emanating->emanatingItems;

        $comparisonItems = [];
        $allMatched = true;
        $matchedPPMPItemIds = [];
        $processedPPMPItemIds = []; // Track ppmpItems we've seen (even if mismatched)

        // Step 1: Check each emanating item references a valid PPMP item and validate quantity/unit
        foreach ($emanatingItems as $emanatingItem) {
            $ppmpItem = $emanatingItem->ppmpItem;
            $matched = false;
            $mismatchReason = null;

            if ($ppmpItem === null) {
                $mismatchReason = 'No matching PPMP item found';
            } else {
                // Track that we've seen this ppmpItem
                $processedPPMPItemIds[] = $ppmpItem->id;

                // PPMP item exists, now validate quantity and unit match
                if ($emanatingItem->quantity != $ppmpItem->quantity) {
                    $mismatchReason = sprintf('Quantity mismatch: Emanating has %s, PPMP has %s', $emanatingItem->quantity, $ppmpItem->quantity);
                } elseif (strcasecmp($emanatingItem->unit, $ppmpItem->unit) !== 0) {
                    $mismatchReason = sprintf("Unit mismatch: Emanating has '%s', PPMP has '%s'", $emanatingItem->unit, $ppmpItem->unit);
                } else {
                    // All checks passed
                    $matched = true;
                    $matchedPPMPItemIds[] = $ppmpItem->id;
                }
            }

            if (! $matched) {
                $allMatched = false;
            }

            $comparisonItems[] = [
                'emanating_item' => $emanatingItem,
                'ppmp_item' => $ppmpItem,
                'matched' => $matched,
                'mismatch_reason' => $mismatchReason,
            ];
        }

        // Step 2: Check if all PPMP items are accounted for in emanating request
        $unmatchedPPMPItems = $ppmpItems->whereNotIn('id', $processedPPMPItemIds);

        if ($unmatchedPPMPItems->isNotEmpty()) {
            $allMatched = false;

            // Add unmatched PPMP items to comparison
            foreach ($unmatchedPPMPItems as $unmatchedPPMPItem) {
                $comparisonItems[] = [
                    'emanating_item' => null,
                    'ppmp_item' => $unmatchedPPMPItem,
                    'matched' => false,
                    'mismatch_reason' => 'PPMP item not found in Emanating request',
                    'is_missing_from_emanating' => true,
                ];
            }
        }

        // Step 3: Verify counts match
        if (count($emanatingItems) !== $ppmpItems->count()) {
            $allMatched = false;
        }

        // Update the emanating record's items_match_ppmp flag if it differs
        if ($emanating->items_match_ppmp !== $allMatched) {
            $emanating->update(['items_match_ppmp' => $allMatched]);
        }

        return [
            'status' => $allMatched ? 'all_matched' : 'partial_match',
            'message' => $allMatched
                ? 'All items match the PPMP.'
                : 'Some items do not match or are missing.',
            'items' => $comparisonItems,
            'ppmp_items' => $ppmpItems,
            'total_emanating_items' => count($emanatingItems),
            'total_ppmp_items' => $ppmpItems->count(),
            'total_matched_items' => collect($comparisonItems)->where('matched', true)->where('is_missing_from_emanating', '!=', true)->count(),
            'unmatched_ppmp_items' => $unmatchedPPMPItems->count(),
        ];
    }
}

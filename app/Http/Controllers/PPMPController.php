<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePPMPRequest;
use App\Http\Requests\UpdatePPMPRequest;
use App\Imports\PPMPImport;
use App\Models\Office;
use App\Models\PPMP;
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

class PPMPController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = PPMP::with(['office', 'project'])
            ->when($request->search, function ($query, string $search): void {
                $query->where('fiscal_year', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('account_code', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('project_code', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('office', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('project', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PPMPs/Index', [
            'ppmps' => $lengthAwarePaginator,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PPMPs/Create', [
            'offices' => Office::all(['id', 'name']),
            'projects' => Project::all(['id', 'name']),
        ]);
    }

    public function store(StorePPMPRequest $storePPMPRequest): RedirectResponse
    {
        $validated = $storePPMPRequest->validated();

        DB::beginTransaction();
        try {
            // Create PPMP
            $ppmp = PPMP::create([
                'office_id' => $validated['office_id'],
                'project_id' => $validated['project_id'],
                'account_code' => $validated['account_code'] ?? null,
                'project_code' => $validated['project_code'] ?? null,
                'fiscal_year' => $validated['fiscal_year'],
                'is_addendum' => $validated['is_addendum'] ?? false,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $budgetNotices = [];
            $csvPath = null;

            // Handle CSV import if file is uploaded
            if ($storePPMPRequest->hasFile('csv_file') && $storePPMPRequest->file('csv_file')->isValid()) {
                // Store CSV file
                $csvPath = $storePPMPRequest->file('csv_file')->store('ppmps', 'public');

                $ppmpImport = new PPMPImport($ppmp);
                Excel::import($ppmpImport, $storePPMPRequest->file('csv_file'));

                // Get budget notices
                $budgetNotices = $ppmpImport->getBudgetNotices();

                // Update PPMP with csv_path and budget_notices
                $ppmp->update([
                    'csv_path' => $csvPath,
                    'budget_notices' => $budgetNotices,
                ]);
            }

            DB::commit();

            return redirect()->route('ppmps.show', $ppmp)
                ->with('success', 'Project Procurement Management Plan created successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to create PPMP: '.$exception->getMessage()]);
        }
    }

    public function show(PPMP $ppmp): Response
    {
        $ppmp->load(['office', 'project', 'categories.items.months', 'approvedBy']);

        return Inertia::render('PPMPs/Show', [
            'ppmp' => $ppmp,
        ]);
    }

    public function edit(PPMP $ppmp): Response
    {
        $ppmp->load(['office', 'project', 'categories.items.months']);

        return Inertia::render('PPMPs/Edit', [
            'ppmp' => $ppmp,
            'offices' => Office::all(['id', 'name']),
            'projects' => Project::all(['id', 'name']),
        ]);
    }

    public function update(UpdatePPMPRequest $updatePPMPRequest, PPMP $ppmp): RedirectResponse
    {
        $validated = $updatePPMPRequest->validated();

        DB::beginTransaction();
        try {
            $ppmp->update([
                'office_id' => $validated['office_id'],
                'project_id' => $validated['project_id'],
                'account_code' => $validated['account_code'] ?? null,
                'project_code' => $validated['project_code'] ?? null,
                'fiscal_year' => $validated['fiscal_year'],
                'is_addendum' => $validated['is_addendum'] ?? false,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('ppmps.index')
                ->with('success', 'Project Procurement Management Plan updated successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to update PPMP: '.$exception->getMessage()]);
        }
    }

    public function destroy(PPMP $ppmp): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $ppmp->delete();
            DB::commit();

            return redirect()->route('ppmps.index')
                ->with('success', 'Project Procurement Management Plan deleted successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to delete PPMP: '.$exception->getMessage()]);
        }
    }

    /**
     * Handle CSV import for existing PPMP
     */
    public function import(Request $request, PPMP $ppmp): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        DB::beginTransaction();
        try {
            // Delete existing categories and items
            $ppmp->categories()->delete();

            // Delete old CSV file if exists
            if ($ppmp->csv_path && Storage::disk('public')->exists($ppmp->csv_path)) {
                Storage::disk('public')->delete($ppmp->csv_path);
            }

            // Store new CSV file
            $csvPath = $request->file('csv_file')->store('ppmps', 'public');

            // Import from CSV/Excel
            $ppmpImport = new PPMPImport($ppmp);
            Excel::import($ppmpImport, $request->file('csv_file'));

            // Get budget notices
            $budgetNotices = $ppmpImport->getBudgetNotices();

            // Update PPMP with new csv_path and budget_notices
            $ppmp->update([
                'csv_path' => $csvPath,
                'budget_notices' => $budgetNotices,
            ]);

            DB::commit();

            return redirect()->route('ppmps.show', $ppmp)
                ->with('success', 'CSV data imported successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to import CSV: '.$exception->getMessage()]);
        }
    }

    /**
     * Download the uploaded CSV file
     */
    public function downloadCsv(PPMP $ppmp): BinaryFileResponse
    {
        if (! $ppmp->csv_path || ! Storage::disk('public')->exists($ppmp->csv_path)) {
            abort(404, 'CSV file not found.');
        }

        return response()->download(
            Storage::disk('public')->path($ppmp->csv_path),
            sprintf('PPMP-%s-%s-FY%s.csv', $ppmp->office->name, $ppmp->project->name, $ppmp->fiscal_year)
        );
    }

    /**
     * Approve a PPMP
     */
    public function approve(PPMP $ppmp): RedirectResponse
    {
        Log::info('[PPMP Approve] Starting approval process', [
            'ppmp_id' => $ppmp->id,
            'is_approved' => $ppmp->is_approved,
            'user_id' => auth()->id(),
        ]);

        // Check if already approved
        if ($ppmp->is_approved) {
            Log::warning('[PPMP Approve] PPMP already approved', ['ppmp_id' => $ppmp->id]);

            return back()->withErrors(['error' => 'This PPMP is already approved.']);
        }

        // Check if budget validation passed
        Log::debug('[PPMP Approve] Checking budget validation', [
            'budget_notices' => $ppmp->budget_notices,
        ]);

        $budgetValidationPassed = collect($ppmp->budget_notices)->every(
            fn ($notice): bool => $notice['status'] === 'within_budget'
        );

        Log::debug('[PPMP Approve] Budget validation result', [
            'passed' => $budgetValidationPassed,
            'notices_count' => count($ppmp->budget_notices ?? []),
        ]);

        if (! $budgetValidationPassed) {
            Log::warning('[PPMP Approve] Budget validation failed', ['ppmp_id' => $ppmp->id]);

            return back()->withErrors(['error' => 'Cannot approve PPMP with budget validation failures.']);
        }

        DB::beginTransaction();
        try {
            Log::info('[PPMP Approve] Attempting database update', ['ppmp_id' => $ppmp->id]);

            $ppmp->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejection_reason' => null,
            ]);

            Log::info('[PPMP Approve] Database update successful', [
                'ppmp_id' => $ppmp->id,
                'approved_by' => auth()->id(),
            ]);

            DB::commit();

            Log::info('[PPMP Approve] Transaction committed successfully', ['ppmp_id' => $ppmp->id]);

            return redirect()->route('ppmps.show', $ppmp)
                ->with('success', 'PPMP approved successfully.');
        } catch (\Exception $exception) {
            Log::error('[PPMP Approve] Exception caught', [
                'ppmp_id' => $ppmp->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to approve PPMP: '.$exception->getMessage()]);
        }
    }

    /**
     * Reject a PPMP
     */
    public function reject(Request $request, PPMP $ppmp): RedirectResponse
    {
        Log::info('[PPMP Reject] Starting rejection process', [
            'ppmp_id' => $ppmp->id,
            'is_approved' => $ppmp->is_approved,
            'user_id' => auth()->id(),
        ]);

        // Check if already approved
        if ($ppmp->is_approved) {
            Log::warning('[PPMP Reject] Cannot reject approved PPMP', ['ppmp_id' => $ppmp->id]);

            return back()->withErrors(['error' => 'Cannot reject an approved PPMP.']);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        Log::debug('[PPMP Reject] Rejection reason provided', [
            'ppmp_id' => $ppmp->id,
            'reason_length' => strlen($request->rejection_reason),
        ]);

        DB::beginTransaction();
        try {
            Log::info('[PPMP Reject] Attempting database update', ['ppmp_id' => $ppmp->id]);

            $ppmp->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => $request->rejection_reason,
            ]);

            Log::info('[PPMP Reject] Database update successful', [
                'ppmp_id' => $ppmp->id,
                'reason_saved' => ! empty($ppmp->rejection_reason),
            ]);

            DB::commit();

            Log::info('[PPMP Reject] Transaction committed successfully', ['ppmp_id' => $ppmp->id]);

            return redirect()->route('ppmps.show', $ppmp)
                ->with('success', 'PPMP rejected. Please address the issues and upload a new CSV or create an addendum.');
        } catch (\Exception $exception) {
            Log::error('[PPMP Reject] Exception caught', [
                'ppmp_id' => $ppmp->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to reject PPMP: '.$exception->getMessage()]);
        }
    }
}

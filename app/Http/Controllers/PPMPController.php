<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePPMPRequest;
use App\Http\Requests\UpdatePPMPRequest;
use App\Imports\PPMPImport;
use App\Models\Office;
use App\Models\PPMP;
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
        $lengthAwarePaginator = PPMP::with(['office'])
            ->when($request->search, function ($query, string $search): void {
                $query->where('fiscal_year', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('office', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($query, string $officeId): void {
                $query->where('office_id', $officeId);
            })
            ->when($request->fiscal_year, function ($query, string $fiscalYear): void {
                $query->where('fiscal_year', $fiscalYear);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Get unique offices and fiscal years for filters
        $offices = PPMP::distinct()
            ->pluck('office_id')
            ->mapWithKeys(function ($officeId) {
                $office = Office::find($officeId);

                return [$officeId => $office?->name];
            })
            ->filter()
            ->sort();

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('PPMPs/Index', [
            'ppmps' => $lengthAwarePaginator,
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
        return Inertia::render('PPMPs/Create', [
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function store(StorePPMPRequest $storePPMPRequest): RedirectResponse
    {
        $validated = $storePPMPRequest->validated();

        Log::info('[PPMP Store] Starting create', [
            'office_id' => $validated['office_id'] ?? null,
            'fiscal_year' => $validated['fiscal_year'] ?? null,
            'has_xlsx_file' => $storePPMPRequest->hasFile('xlsx_file'),
        ]);

        DB::beginTransaction();
        try {
            // Create PPMP
            $ppmp = PPMP::create([
                'office_id' => $validated['office_id'],
                'fiscal_year' => $validated['fiscal_year'],
                'is_addendum' => $validated['is_addendum'] ?? false,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $budgetNotices = [];
            $xlsxPath = null;

            // Handle XLSX import if file is uploaded
            if ($storePPMPRequest->hasFile('xlsx_file') && $storePPMPRequest->file('xlsx_file')->isValid()) {
                Log::info('[PPMP Store] XLSX file detected, starting import', [
                    'ppmp_id' => $ppmp->id,
                    'original_name' => $storePPMPRequest->file('xlsx_file')->getClientOriginalName(),
                    'size_bytes' => $storePPMPRequest->file('xlsx_file')->getSize(),
                ]);

                // Store XLSX file
                $xlsxPath = $storePPMPRequest->file('xlsx_file')->store('ppmps', 'public');

                $ppmpImport = new PPMPImport($ppmp);
                Excel::import($ppmpImport, $storePPMPRequest->file('xlsx_file'));

                // Get budget notices
                $budgetNotices = $ppmpImport->getBudgetNotices();

                // Update PPMP with xlsx_path and budget_notices
                $ppmp->update([
                    'xlsx_path' => $xlsxPath,
                    'budget_notices' => $budgetNotices,
                ]);

                Log::info('[PPMP Store] XLSX import completed', [
                    'ppmp_id' => $ppmp->id,
                    'xlsx_path' => $xlsxPath,
                    'budget_notices_count' => count($budgetNotices),
                    'categories_created' => $ppmpImport->getCategoriesCreated(),
                    'items_created' => $ppmpImport->getItemsCreated(),
                ]);
            }

            DB::commit();

            return redirect()->route('ppmps.show', $ppmp)
                ->with('success', 'Project Procurement Management Plan created successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            Log::error('[PPMP Store] Failed to create PPMP', [
                'office_id' => $validated['office_id'] ?? null,
                'fiscal_year' => $validated['fiscal_year'] ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create PPMP: ' . $exception->getMessage()]);
        }
    }

    public function show(PPMP $ppmp): Response
    {
        $ppmp->load(['office', 'categories.account', 'categories.items.months']);

        return Inertia::render('PPMPs/Show', [
            'ppmp' => $ppmp,
        ]);
    }

    public function edit(PPMP $ppmp): Response
    {
        $ppmp->load(['office', 'categories.account', 'categories.items.months']);

        return Inertia::render('PPMPs/Edit', [
            'ppmp' => $ppmp,
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function update(UpdatePPMPRequest $updatePPMPRequest, PPMP $ppmp): RedirectResponse
    {
        $validated = $updatePPMPRequest->validated();

        DB::beginTransaction();
        try {
            $ppmp->update([
                'office_id' => $validated['office_id'],
                'fiscal_year' => $validated['fiscal_year'],
                'is_addendum' => $validated['is_addendum'] ?? false,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('ppmps.index')
                ->with('success', 'Project Procurement Management Plan updated successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to update PPMP: ' . $exception->getMessage()]);
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

            return back()->withErrors(['error' => 'Failed to delete PPMP: ' . $exception->getMessage()]);
        }
    }

    /**
     * Handle XLSX import for existing PPMP
     */
    public function import(Request $request, PPMP $ppmp): RedirectResponse
    {
        $request->validate([
            'xlsx_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        Log::info('[PPMP Import] Starting import endpoint', [
            'ppmp_id' => $ppmp->id,
            'file_name' => $request->file('xlsx_file')?->getClientOriginalName(),
            'file_size_bytes' => $request->file('xlsx_file')?->getSize(),
        ]);

        DB::beginTransaction();
        try {
            // Delete existing categories and items
            $ppmp->categories()->delete();

            // Delete old XLSX file if exists
            if ($ppmp->xlsx_path && Storage::disk('public')->exists($ppmp->xlsx_path)) {
                Storage::disk('public')->delete($ppmp->xlsx_path);
            }

            // Store new XLSX file
            $xlsxPath = $request->file('xlsx_file')->store('ppmps', 'public');

            // Import from XLSX
            $ppmpImport = new PPMPImport($ppmp);
            Excel::import($ppmpImport, $request->file('xlsx_file'));

            // Get budget notices
            $budgetNotices = $ppmpImport->getBudgetNotices();

            // Update PPMP with new xlsx_path and budget_notices
            $ppmp->update([
                'xlsx_path' => $xlsxPath,
                'budget_notices' => $budgetNotices,
            ]);

            Log::info('[PPMP Import] Import endpoint completed', [
                'ppmp_id' => $ppmp->id,
                'xlsx_path' => $xlsxPath,
                'budget_notices_count' => count($budgetNotices),
                'categories_created' => $ppmpImport->getCategoriesCreated(),
                'items_created' => $ppmpImport->getItemsCreated(),
            ]);

            DB::commit();

            return redirect()->route('ppmps.show', $ppmp)
                ->with('success', 'XLSX data imported successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            Log::error('[PPMP Import] Failed import endpoint', [
                'ppmp_id' => $ppmp->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to import XLSX: ' . $exception->getMessage()]);
        }
    }

    /**
     * Download the uploaded XLSX file
     */
    public function downloadXlsx(PPMP $ppmp): BinaryFileResponse
    {
        if (! $ppmp->xlsx_path || ! Storage::disk('public')->exists($ppmp->xlsx_path)) {
            abort(404, 'XLSX file not found.');
        }

        return response()->download(
            Storage::disk('public')->path($ppmp->xlsx_path),
            sprintf('PPMP-%s-FY%s.xlsx', $ppmp->office->name, $ppmp->fiscal_year)
        );
    }
}

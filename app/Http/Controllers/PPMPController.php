<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePPMPRequest;
use App\Http\Requests\UpdatePPMPRequest;
use App\Imports\PPMPImport;
use App\Models\Fund;
use App\Models\Office;
use App\Models\PPMP;
use App\Models\ProjectCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PPMPController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = PPMP::with(['office', 'projectCode'])
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
            ->mapWithKeys(function ($officeId): array {
                $office = Office::find($officeId);

                return [$officeId => $office?->name];
            })
            ->filter()
            ->sort();

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear))
            ->mapWithKeys(fn ($year): array => [$year => $year])
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
            'offices' => Office::with([
                'funds' => fn ($query) => $query
                    ->select(['id', 'office_id', 'name', 'type', 'project_code_id', 'fiscal_year'])
                    ->with('projectCode:id,code,name'),
            ])->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StorePPMPRequest $storePPMPRequest): RedirectResponse
    {
        $validated = $storePPMPRequest->validated();
        $fund = Fund::query()
            ->where('id', $validated['fund_id'])
            ->where('office_id', $validated['office_id'])
            ->firstOrFail();

        $resolvedProjectCodeId = $fund->type === 'project'
            ? $fund->project_code_id
            : null;

        if (
            $fund->type === 'project'
            && $storePPMPRequest->hasFile('xlsx_file')
            && $storePPMPRequest->file('xlsx_file')->isValid()
        ) {
            $resolvedProjectCode = $this->resolveProjectCodeFromXlsx(
                $storePPMPRequest->file('xlsx_file'),
                (int) $validated['office_id']
            );

            if (!$resolvedProjectCode instanceof \App\Models\ProjectCode) {
                return back()->withErrors([
                    'xlsx_file' => 'Unable to match the PPMP project code to the selected office project codes.',
                ]);
            }

            if ((int) $resolvedProjectCode->id !== (int) $resolvedProjectCodeId) {
                return back()->withErrors([
                    'fund_id' => 'Selected fund project code does not match the PPMP project code.',
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $existingPpmp = PPMP::query()
                ->where('office_id', $validated['office_id'])
                ->where('fiscal_year', $validated['fiscal_year'])
                ->when(
                    $resolvedProjectCodeId === null,
                    fn ($query) => $query->whereNull('project_code_id'),
                    fn ($query) => $query->where('project_code_id', $resolvedProjectCodeId)
                )
                ->orderBy('id')
                ->first();

            if ($existingPpmp) {
                if (! ($storePPMPRequest->hasFile('xlsx_file') && $storePPMPRequest->file('xlsx_file')->isValid())) {
                    return back()->withErrors([
                        'xlsx_file' => 'An XLSX file is required to append an addendum to the existing PPMP.',
                    ]);
                }

                $xlsxPath = $storePPMPRequest->file('xlsx_file')->store('ppmps', 'public');

                $ppmpImport = new PPMPImport($existingPpmp);
                Excel::import($ppmpImport, $storePPMPRequest->file('xlsx_file'));

                $budgetNotices = array_merge(
                    $existingPpmp->budget_notices ?? [],
                    $ppmpImport->getBudgetNotices()
                );

                $existingPpmp->update([
                    'xlsx_path' => $xlsxPath,
                    'budget_notices' => $budgetNotices,
                ]);

                DB::commit();

                return redirect()->route('ppmps.show', $existingPpmp)
                    ->with('success', 'PPMP addendum imported and appended to the existing office-project code-year PPMP.');
            }

            // Create PPMP
            $ppmp = PPMP::create([
                'office_id' => $validated['office_id'],
                'project_code_id' => $resolvedProjectCodeId,
                'fiscal_year' => $validated['fiscal_year'],
                'is_addendum' => $validated['is_addendum'] ?? false,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $budgetNotices = [];
            $xlsxPath = null;

            // Handle XLSX import if file is uploaded
            if ($storePPMPRequest->hasFile('xlsx_file') && $storePPMPRequest->file('xlsx_file')->isValid()) {
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
        $ppmp->load(['office', 'projectCode', 'categories.account', 'categories.items.months']);

        return Inertia::render('PPMPs/Show', [
            'ppmp' => $ppmp,
        ]);
    }

    public function edit(PPMP $ppmp): Response
    {
        $ppmp->load(['office', 'projectCode', 'categories.account', 'categories.items.months']);

        $selectedFund = Fund::query()
            ->where('office_id', $ppmp->office_id)
            ->where('fiscal_year', $ppmp->fiscal_year)
            ->when(
                $ppmp->project_code_id === null,
                fn ($query) => $query->where('type', 'general')->whereNull('project_code_id'),
                fn ($query) => $query->where('project_code_id', $ppmp->project_code_id)
            )
            ->orderBy('id')
            ->first(['id']);

        $ppmp->setAttribute('selected_fund_id', $selectedFund?->id);

        return Inertia::render('PPMPs/Edit', [
            'ppmp' => $ppmp,
            'offices' => Office::with([
                'funds' => fn ($query) => $query
                    ->select(['id', 'office_id', 'name', 'type', 'project_code_id', 'fiscal_year'])
                    ->with('projectCode:id,code,name'),
            ])->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdatePPMPRequest $updatePPMPRequest, PPMP $ppmp): RedirectResponse
    {
        $validated = $updatePPMPRequest->validated();
        $fund = Fund::query()
            ->where('id', $validated['fund_id'])
            ->where('office_id', $validated['office_id'])
            ->firstOrFail();

        $resolvedProjectCodeId = $fund->type === 'project'
            ? $fund->project_code_id
            : null;

        DB::beginTransaction();
        try {
            $ppmp->update([
                'office_id' => $validated['office_id'],
                'project_code_id' => $resolvedProjectCodeId,
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
     * Handle XLSX import for existing PPMP
     */
    public function import(Request $request, PPMP $ppmp): RedirectResponse
    {
        $request->validate([
            'xlsx_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
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

            DB::commit();

            return redirect()->route('ppmps.show', $ppmp)
                ->with('success', 'XLSX data imported successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to import XLSX: '.$exception->getMessage()]);
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

    private function resolveProjectCodeFromXlsx(UploadedFile $uploadedFile, int $officeId): ?ProjectCode
    {
        $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
        $cellValue = trim((string) $spreadsheet->getActiveSheet()->getCell('C4')->getFormattedValue());

        if ($cellValue === '') {
            return null;
        }

        $candidate = $this->extractProjectCodeCandidate($cellValue);
        $candidateLower = mb_strtolower($candidate);

        $projectCode = ProjectCode::query()
            ->where('office_id', $officeId)
            ->where(function ($query) use ($candidateLower): void {
                $query->whereRaw('LOWER(name) = ?', [$candidateLower])
                    ->orWhereRaw('LOWER(code) = ?', [$candidateLower]);
            })
            ->first();

        if ($projectCode) {
            return $projectCode;
        }

        if (str_contains($candidate, '-')) {
            [$left, $right] = array_map('trim', explode('-', $candidate, 2));

            $projectCode = ProjectCode::query()
                ->where('office_id', $officeId)
                ->where(function ($query) use ($left, $right): void {
                    $query->where('code', $left)
                        ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($right)]);
                })
                ->first();
        }

        return $projectCode;
    }

    private function extractProjectCodeCandidate(string $cellValue): string
    {
        $normalized = preg_replace('/\s+/', ' ', $cellValue) ?? $cellValue;

        if (str_contains($normalized, ':')) {
            return trim((string) preg_replace('/^[^:]+:/', '', $normalized));
        }

        return trim($normalized);
    }
}

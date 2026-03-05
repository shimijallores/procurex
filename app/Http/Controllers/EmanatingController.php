<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmanatingRequest;
use App\Http\Requests\UpdateEmanatingRequest;
use App\Imports\EmanatingImport;
use App\Models\APP;
use App\Models\APPItem;
use App\Models\Emanating;
use App\Models\Fund;
use App\Models\Office;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use App\Models\ProjectCode;
use App\Models\WorkProgramItem;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class EmanatingController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = Emanating::with(['fund.office', 'project', 'ppmp', 'ppmpCategory'])
            ->when($request->search, function ($query, string $search): void {
                $query->where('fiscal_year', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('pr_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('charged_to_code', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('fund', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('project', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('fund.office', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($query, string $officeId): void {
                $query->whereHas('fund', function ($q) use ($officeId): void {
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
                    ->orWhereHas('fund', function ($q) use ($request): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $request->search));
                    })
                    ->orWhereHas('project', function ($q) use ($request): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $request->search));
                    })
                    ->orWhereHas('fund.office', function ($q) use ($request): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $request->search));
                    });
            });
        }
        if ($request->office_id) {
            $builder->whereHas('fund', function ($q) use ($request): void {
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
        $offices = Emanating::with('fund.office')
            ->get()
            ->pluck('fund.office')
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
            'offices' => Office::all(['id', 'name']),
            'ppmps' => PPMP::with(['office', 'projectCode'])
                ->get(['id', 'office_id', 'project_code_id', 'fiscal_year']),
            'ppmpCategories' => PPMPCategory::with('ppmp.office', 'account')
                ->get(['id', 'ppmp_id', 'account_id']),
        ]);
    }

    public function store(StoreEmanatingRequest $storeEmanatingRequest): RedirectResponse
    {
        $validated = $storeEmanatingRequest->validated();

        DB::beginTransaction();
        try {
            $ppmp = PPMP::findOrFail($validated['ppmp_id']);
            PPMPCategory::findOrFail($validated['ppmp_category_id']);

            $fund = Fund::query()
                ->where('office_id', $ppmp->office_id)
                ->where('project_code_id', $ppmp->project_code_id)
                ->where('fiscal_year', $ppmp->fiscal_year)
                ->latest()
                ->first();

            $xlsxPath = $storeEmanatingRequest->file('xlsx_file')->store('emanatings', 'public');

            $emanatingImport = new EmanatingImport($ppmp, (int) $validated['ppmp_category_id'], $fund);
            Excel::import($emanatingImport, $storeEmanatingRequest->file('xlsx_file'));

            $emanating = Emanating::query()->latest()->first();

            if ($emanating) {
                $existingEmanating = Emanating::where('ppmp_id', $validated['ppmp_id'])
                    ->where('charged_to_code', $emanating->charged_to_code)
                    ->where('id', '!=', $emanating->id)
                    ->first();

                $isAddendum = $validated['is_addendum'] ?? false;
                if ($existingEmanating) {
                    $isAddendum = true;
                    $existingEmanating->delete();
                }

                $emanating->update([
                    'fund_id' => $fund?->id,
                    'project_id' => $fund?->project?->id,
                    'ppmp_id' => $validated['ppmp_id'],
                    'ppmp_category_id' => $validated['ppmp_category_id'],
                    'pr_no' => $validated['pr_no'] ?? null,
                    'fiscal_year' => $ppmp->fiscal_year,
                    'is_addendum' => $isAddendum,
                    'remarks' => $validated['remarks'] ?? null,
                    'reimbursement' => $validated['reimbursement'] ?? false,
                    'xlsx_path' => $xlsxPath,
                    'requesting_officer_name' => $emanatingImport->getRequestingOfficerName(),
                    'requesting_officer_title' => $emanatingImport->getRequestingOfficerTitle(),
                    'items_match_ppmp' => $emanatingImport->getItemsMatchPPMP(),
                ]);

                $comparison = $this->compareWithDocuments($emanating->fresh(['ppmpCategory.items', 'emanatingItems.ppmpItem']));
                $emanating->update(['items_match_ppmp' => $comparison['status'] === 'all_matched']);
            }

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request created successfully from XLSX.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to create Emanating Request: ' . $exception->getMessage()]);
        }
    }

    public function show(Emanating $emanating): Response
    {
        $emanating->load([
            'project.fund.office',
            'ppmp.categories.items',
            'ppmp.projectCode',
            'ppmpCategory.items',
            'emanatingItems.ppmpItem',
            'approvedBy',
            'fund.projectCode',
            'fund.project.workProgram.items',
            'fund.project.projectBrief',
            'fund.project.projectProposal',
        ]);

        $chargedToProjectCodeName = null;
        $normalizedChargedToCode = preg_replace('/[^0-9]/', '', (string) $emanating->charged_to_code);

        if ($normalizedChargedToCode !== '') {
            $chargedToProjectCodeName = ProjectCode::query()
                ->whereRaw("REPLACE(REPLACE(code, '-', ''), ' ', '') = ?", [$normalizedChargedToCode])
                ->value('name');
        }

        $emanating->setAttribute('charged_to_project_code_name', $chargedToProjectCodeName);

        $comparisonData = $this->compareWithDocuments($emanating);

        return Inertia::render('Emanatings/Show', [
            'emanating' => $emanating,
            'comparison' => $comparisonData,
        ]);
    }

    public function edit(Emanating $emanating): Response
    {
        $emanating->load(['fund', 'project.fund', 'ppmp', 'ppmpCategory', 'emanatingItems']);

        return Inertia::render('Emanatings/Edit', [
            'emanating' => $emanating,
            'offices' => Office::all(['id', 'name']),
            'ppmps' => PPMP::with(['office', 'projectCode'])
                ->get(['id', 'office_id', 'project_code_id', 'fiscal_year']),
            'ppmpCategories' => PPMPCategory::with('ppmp.office', 'account')
                ->get(['id', 'ppmp_id', 'account_id']),
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

            $comparison = $this->compareWithDocuments($emanating->fresh(['ppmpCategory.items', 'emanatingItems.ppmpItem', 'ppmp']));
            $emanating->update(['items_match_ppmp' => $comparison['status'] === 'all_matched']);

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request updated successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

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

            return back()->withErrors(['error' => 'Failed to delete Emanating Request: ' . $exception->getMessage()]);
        }
    }

    /**
     * Handle XLSX import for existing Emanating
     */
    public function import(Request $request, Emanating $emanating): RedirectResponse
    {
        $request->validate([
            'xlsx_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        DB::beginTransaction();
        try {
            // Delete existing items
            $emanating->emanatingItems()->delete();

            if ($emanating->xlsx_path && Storage::disk('public')->exists($emanating->xlsx_path)) {
                Storage::disk('public')->delete($emanating->xlsx_path);
            }

            $xlsxPath = $request->file('xlsx_file')->store('emanatings', 'public');

            $emanatingImport = new EmanatingImport(
                $emanating->ppmp,
                $emanating->ppmp_category_id,
                $emanating->fund
            );
            Excel::import($emanatingImport, $request->file('xlsx_file'));

            $emanating->update([
                'xlsx_path' => $xlsxPath,
                'requesting_officer_name' => $emanatingImport->getRequestingOfficerName(),
                'requesting_officer_title' => $emanatingImport->getRequestingOfficerTitle(),
                'items_match_ppmp' => $emanatingImport->getItemsMatchPPMP(),
            ]);

            $comparison = $this->compareWithDocuments($emanating->fresh(['ppmpCategory.items', 'emanatingItems.ppmpItem', 'ppmp']));
            $emanating->update(['items_match_ppmp' => $comparison['status'] === 'all_matched']);

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'XLSX data imported successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to import XLSX: ' . $exception->getMessage()]);
        }
    }

    /**
     * Download the uploaded XLSX file
     */
    public function downloadXlsx(Emanating $emanating): BinaryFileResponse
    {
        if (! $emanating->xlsx_path || ! Storage::disk('public')->exists($emanating->xlsx_path)) {
            abort(404, 'XLSX file not found.');
        }

        return response()->download(
            Storage::disk('public')->path($emanating->xlsx_path),
            sprintf('Emanating-%s-FY%s.xlsx', $emanating->pr_no ?? $emanating->id, $emanating->fiscal_year)
        );
    }

    /**
     * Approve an Emanating
     */
    public function approve(Emanating $emanating): RedirectResponse
    {
        // Check if already approved
        if ($emanating->is_approved) {
            return back()->withErrors(['error' => 'This Emanating Request is already approved.']);
        }

        $comparison = $this->compareWithDocuments($emanating);

        DB::beginTransaction();
        try {
            $emanating->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'rejection_reason' => null,
                'status' => 'approved',
            ]);

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request approved successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to approve Emanating Request: ' . $exception->getMessage()]);
        }
    }

    /**
     * Reject an Emanating
     */
    public function reject(Request $request, Emanating $emanating): RedirectResponse
    {
        // Check if already approved
        if ($emanating->is_approved) {
            return back()->withErrors(['error' => 'Cannot reject an approved Emanating Request.']);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();
        try {
            $emanating->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => $request->rejection_reason,
                'status' => 'rejected',
            ]);

            DB::commit();

            return redirect()->route('emanatings.show', $emanating)
                ->with('success', 'Emanating Request rejected. Please address the issues and upload a new XLSX or create an addendum.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to reject Emanating Request: ' . $exception->getMessage()]);
        }
    }

    /**
     * Compare emanating items against PPMP and APP using missing/exceeds rules.
     */
    private function compareWithDocuments(Emanating $emanating): array
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
        $app = APP::query()
            ->where('office_id', $emanating->ppmp?->office_id)
            ->where('fiscal_year', $emanating->fiscal_year)
            ->latest()
            ->first();

        $appItems = $app
            ? APPItem::query()->whereHas('appCategory', fn($query) => $query->where('app_id', $app->id))->get()
            : collect();

        $isProjectFund = strtolower((string) ($emanating->fund?->type ?? '')) === 'project';
        $workProgramItems = $isProjectFund
            ? $this->getOrParseWorkProgramItems($emanating)
            : collect();

        $comparisonItems = [];
        $allMatched = true;

        foreach ($emanatingItems as $emanatingItem) {
            $ppmpItem = $emanatingItem->ppmpItem
                ?? $this->findMatchingPpmpItemByName((string) $emanatingItem->name, $ppmpItems);
            $appMatch = $this->findMatchingAppItemByName((string) $emanatingItem->name, $appItems);
            $workProgramMatch = $isProjectFund
                ? $this->findMatchingWorkProgramItemByName((string) $emanatingItem->name, $workProgramItems)
                : null;

            $issues = [];

            if (! $ppmpItem) {
                $issues[] = 'Missing in PPMP';
            } elseif ((int) $emanatingItem->quantity > (int) $ppmpItem->quantity) {
                $issues[] = sprintf('Quantity exceeds PPMP (%d > %d)', (int) $emanatingItem->quantity, (int) $ppmpItem->quantity);
            }

            if (! $appMatch) {
                $issues[] = 'Missing in APP';
            }

            if ($isProjectFund && ! $workProgramMatch) {
                $issues[] = 'Missing in Work Program';
            }

            if ($issues !== []) {
                $allMatched = false;
            }

            $comparisonItems[] = [
                'emanating_item' => $emanatingItem,
                'ppmp_item' => $ppmpItem,
                'app_item' => $appMatch,
                'work_program_item' => $workProgramMatch,
                'matched' => $issues === [],
                'mismatch_reason' => $issues === [] ? null : implode('; ', $issues),
                'issues' => $issues,
            ];
        }

        if ($emanating->items_match_ppmp !== $allMatched) {
            $emanating->update(['items_match_ppmp' => $allMatched]);
        }

        $matchedAppItems = collect($comparisonItems)
            ->pluck('app_item')
            ->filter()
            ->unique('id')
            ->values();

        $matchedWorkProgramItems = collect($comparisonItems)
            ->pluck('work_program_item')
            ->filter()
            ->unique('id')
            ->values();

        return [
            'status' => $allMatched ? 'all_matched' : 'partial_match',
            'message' => $allMatched
                ? ($isProjectFund
                    ? 'All items pass PPMP, APP, and Work Program checks.'
                    : 'All items pass PPMP and APP checks.')
                : ($isProjectFund
                    ? 'Some items are missing from PPMP/APP/Work Program or exceed PPMP quantity.'
                    : 'Some items are missing from PPMP/APP or exceed PPMP quantity.'),
            'items' => $comparisonItems,
            'ppmp_items' => $ppmpItems,
            'app_items' => $matchedAppItems,
            'work_program_items' => $matchedWorkProgramItems,
            'work_program_parsed_items' => $workProgramItems,
            'is_project_fund' => $isProjectFund,
            'total_emanating_items' => count($emanatingItems),
            'total_ppmp_items' => $ppmpItems->count(),
            'total_app_items' => $matchedAppItems->count(),
            'total_work_program_items' => $matchedWorkProgramItems->count(),
            'total_work_program_parsed_items' => $workProgramItems->count(),
            'total_matched_items' => collect($comparisonItems)->where('matched', true)->count(),
            'unmatched_ppmp_items' => collect($comparisonItems)->filter(fn($item) => in_array('Missing in PPMP', $item['issues'] ?? [], true))->count(),
            'unmatched_app_items' => collect($comparisonItems)->filter(fn($item) => in_array('Missing in APP', $item['issues'] ?? [], true))->count(),
            'unmatched_work_program_items' => collect($comparisonItems)->filter(fn($item) => in_array('Missing in Work Program', $item['issues'] ?? [], true))->count(),
        ];
    }

    private function getOrParseWorkProgramItems(Emanating $emanating): Collection
    {
        $workProgram = $emanating->fund?->project?->workProgram;

        if (! $workProgram || ! $workProgram->file_url) {
            Log::info('WorkProgram parse skipped: missing work program or file URL.', [
                'emanating_id' => $emanating->id,
                'fund_id' => $emanating->fund_id,
                'project_id' => $emanating->project_id,
                'work_program_id' => $workProgram?->id,
                'file_url' => $workProgram?->file_url,
            ]);

            return collect();
        }

        $absolutePath = Storage::disk('public')->path($workProgram->file_url);
        if (! is_file($absolutePath)) {
            $existingItems = $workProgram->items()->count();

            Log::warning('WorkProgram file not found on disk, using existing stored items.', [
                'emanating_id' => $emanating->id,
                'work_program_id' => $workProgram->id,
                'file_url' => $workProgram->file_url,
                'resolved_path' => $absolutePath,
                'existing_items_count' => $existingItems,
            ]);

            return $workProgram->items()->orderBy('row_order')->get();
        }

        Log::info('WorkProgram parse started.', [
            'emanating_id' => $emanating->id,
            'work_program_id' => $workProgram->id,
            'file_url' => $workProgram->file_url,
            'resolved_path' => $absolutePath,
        ]);

        $parsedRows = $this->parseWorkProgramDocxRows($absolutePath);
        if ($parsedRows === []) {
            $existingItems = $workProgram->items()->count();

            Log::warning('WorkProgram parsing produced zero rows, using existing stored items.', [
                'emanating_id' => $emanating->id,
                'work_program_id' => $workProgram->id,
                'resolved_path' => $absolutePath,
                'existing_items_count' => $existingItems,
            ]);

            return $workProgram->items()->orderBy('row_order')->get();
        }

        Log::info('WorkProgram parsed rows ready for persistence.', [
            'emanating_id' => $emanating->id,
            'work_program_id' => $workProgram->id,
            'parsed_rows_count' => count($parsedRows),
            'sample_rows' => array_slice($parsedRows, 0, 3),
        ]);

        $workProgram->items()->delete();

        foreach ($parsedRows as $index => $row) {
            WorkProgramItem::create([
                'work_program_id' => $workProgram->id,
                'item_name' => $row['item_name'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'] ?: null,
                'amount' => $row['amount'],
                'row_order' => $index + 1,
            ]);
        }

        Log::info('WorkProgram items persisted from parsed DOCX rows.', [
            'emanating_id' => $emanating->id,
            'work_program_id' => $workProgram->id,
            'inserted_items_count' => count($parsedRows),
        ]);

        return $workProgram->items()->orderBy('row_order')->get();
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWorkProgramDocxRows(string $absolutePath): array
    {
        Log::info('WorkProgram parser entry.', [
            'resolved_path' => $absolutePath,
            'phpword_available' => class_exists('PhpOffice\\PhpWord\\IOFactory'),
        ]);

        if (class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            $parsedUsingPhpWord = $this->parseWorkProgramWithPhpWord($absolutePath);
            if ($parsedUsingPhpWord !== []) {
                Log::info('WorkProgram parser succeeded via PhpWord.', [
                    'resolved_path' => $absolutePath,
                    'rows_count' => count($parsedUsingPhpWord),
                ]);

                return $parsedUsingPhpWord;
            }

            Log::warning('WorkProgram PhpWord parse returned zero rows; falling back to document.xml parser.', [
                'resolved_path' => $absolutePath,
            ]);
        }

        $xmlRows = $this->parseWorkProgramWithDocumentXml($absolutePath);

        Log::info('WorkProgram document.xml parser finished.', [
            'resolved_path' => $absolutePath,
            'rows_count' => count($xmlRows),
        ]);

        return $xmlRows;
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWorkProgramWithPhpWord(string $absolutePath): array
    {
        $ioFactoryClass = 'PhpOffice\\PhpWord\\IOFactory';

        try {
            $phpWord = $ioFactoryClass::load($absolutePath);
        } catch (\Throwable $throwable) {
            Log::warning('WorkProgram PhpWord load failed.', [
                'resolved_path' => $absolutePath,
                'error' => $throwable->getMessage(),
            ]);

            return [];
        }

        $collectedRows = [];
        $tableIndex = 0;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (! is_object($element) || ! method_exists($element, 'getRows') || ! is_a($element, 'PhpOffice\\PhpWord\\Element\\Table')) {
                    continue;
                }

                $tableIndex++;

                $rows = [];
                foreach ($element->getRows() as $row) {
                    if (! method_exists($row, 'getCells')) {
                        continue;
                    }

                    $cells = [];
                    foreach ($row->getCells() as $cell) {
                        $cells[] = trim($this->extractPhpWordCellText($cell));
                    }

                    if ($cells !== []) {
                        $rows[] = $cells;
                    }
                }

                Log::info('WorkProgram PhpWord table collected.', [
                    'resolved_path' => $absolutePath,
                    'table_index' => $tableIndex,
                    'row_count' => count($rows),
                    'sample_rows' => array_slice($rows, 0, 3),
                ]);

                $parsedRows = $this->extractRowsFromThreeColumnTable($rows, sprintf('phpword-table-%d', $tableIndex));
                if ($parsedRows !== []) {
                    Log::info('WorkProgram PhpWord table parsed successfully.', [
                        'resolved_path' => $absolutePath,
                        'table_index' => $tableIndex,
                        'parsed_rows_count' => count($parsedRows),
                    ]);

                    $collectedRows = $parsedRows;
                    break 2;
                }

                Log::warning('WorkProgram PhpWord table did not match expected 3-column structure.', [
                    'resolved_path' => $absolutePath,
                    'table_index' => $tableIndex,
                ]);
            }
        }

        if ($collectedRows === []) {
            Log::warning('WorkProgram PhpWord parser found no matching table.', [
                'resolved_path' => $absolutePath,
                'tables_checked' => $tableIndex,
            ]);
        }

        return $collectedRows;
    }

    private function extractPhpWordCellText(object $cell): string
    {
        if (! method_exists($cell, 'getElements')) {
            return '';
        }

        $parts = [];

        foreach ($cell->getElements() as $element) {
            if (is_object($element) && method_exists($element, 'getText')) {
                $parts[] = (string) $element->getText();
                continue;
            }

            if (is_object($element) && method_exists($element, 'getElements')) {
                $inner = [];
                foreach ($element->getElements() as $innerElement) {
                    if (is_object($innerElement) && method_exists($innerElement, 'getText')) {
                        $inner[] = (string) $innerElement->getText();
                    }
                }
                $parts[] = implode(' ', $inner);
            }
        }

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWorkProgramWithDocumentXml(string $absolutePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            Log::warning('WorkProgram document.xml parser could not open DOCX zip.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xmlContent) || $xmlContent === '') {
            Log::warning('WorkProgram document.xml missing or empty in DOCX.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        $dom = new DOMDocument();
        $loaded = @$dom->loadXML($xmlContent);
        if (! $loaded) {
            Log::warning('WorkProgram document.xml failed to load into DOMDocument.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = $xpath->query('//w:tbl');
        if (! $tables) {
            Log::warning('WorkProgram document.xml has no table nodes.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        Log::info('WorkProgram document.xml tables detected.', [
            'resolved_path' => $absolutePath,
            'table_count' => $tables->length,
        ]);

        foreach ($tables as $tableIndex => $table) {
            $rows = [];
            $rowNodes = $xpath->query('.//w:tr', $table);
            if (! $rowNodes) {
                continue;
            }

            foreach ($rowNodes as $rowNode) {
                $cells = [];
                $cellNodes = $xpath->query('./w:tc', $rowNode);
                if (! $cellNodes) {
                    continue;
                }

                foreach ($cellNodes as $cellNode) {
                    $textNodes = $xpath->query('.//w:t', $cellNode);
                    $parts = [];
                    if ($textNodes) {
                        foreach ($textNodes as $textNode) {
                            $parts[] = (string) $textNode->nodeValue;
                        }
                    }
                    $cells[] = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
                }

                if ($cells !== []) {
                    $rows[] = $cells;
                }
            }

            Log::info('WorkProgram document.xml table collected.', [
                'resolved_path' => $absolutePath,
                'table_index' => $tableIndex + 1,
                'row_count' => count($rows),
                'sample_rows' => array_slice($rows, 0, 3),
            ]);

            $parsedRows = $this->extractRowsFromThreeColumnTable($rows, sprintf('document-xml-table-%d', $tableIndex + 1));
            if ($parsedRows !== []) {
                Log::info('WorkProgram document.xml table parsed successfully.', [
                    'resolved_path' => $absolutePath,
                    'table_index' => $tableIndex + 1,
                    'parsed_rows_count' => count($parsedRows),
                ]);

                return $parsedRows;
            }

            Log::warning('WorkProgram document.xml table did not match expected 3-column structure.', [
                'resolved_path' => $absolutePath,
                'table_index' => $tableIndex + 1,
            ]);
        }

        Log::warning('WorkProgram document.xml parser found no matching table.', [
            'resolved_path' => $absolutePath,
            'tables_checked' => $tables->length,
        ]);

        return [];
    }

    /**
     * @param array<int, array<int, string>> $rows
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function extractRowsFromThreeColumnTable(array $rows, string $source): array
    {
        $headerIndex = null;
        $headerOffset = 0;
        $minimumDataStartIndex = 4;

        foreach ($rows as $index => $row) {
            $upperRow = array_map(
                fn($cell): string => strtoupper(trim((string) $cell)),
                $row,
            );

            for ($offset = 0; $offset <= max(0, count($upperRow) - 3); $offset++) {
                $col1 = (string) ($upperRow[$offset] ?? '');
                $col2 = (string) ($upperRow[$offset + 1] ?? '');
                $col3 = (string) ($upperRow[$offset + 2] ?? '');

                if (
                    str_contains($col1, 'ACCOUNT NAME')
                    && str_contains($col2, 'ACCOUNT CODE')
                    && str_contains($col3, 'AMOUNT')
                ) {
                    $headerIndex = $index;
                    $headerOffset = $offset;
                    break 2;
                }
            }
        }

        if ($headerIndex === null) {
            Log::warning('WorkProgram header not found in table rows.', [
                'source' => $source,
                'rows_count' => count($rows),
                'sample_rows' => array_slice($rows, 0, 5),
            ]);

            return [];
        }

        $nameColumnIndex = $headerOffset;
        $quantityUnitColumnIndex = $headerOffset + 1;
        $amountColumnIndex = $headerOffset + 2;
        $startIndex = max($headerIndex + 1, $minimumDataStartIndex);

        Log::info('WorkProgram header located.', [
            'source' => $source,
            'header_index' => $headerIndex,
            'header_offset' => $headerOffset,
            'rows_count' => count($rows),
            'start_index' => $startIndex,
        ]);

        $items = [];

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $itemName = trim((string) ($row[$nameColumnIndex] ?? ''));
            $quantityUnitRaw = trim((string) ($row[$quantityUnitColumnIndex] ?? ''));
            $amountText = trim((string) ($row[$amountColumnIndex] ?? ''));

            if (str_contains(strtoupper($quantityUnitRaw), 'GRAND TOTAL')) {
                Log::info('WorkProgram GRAND TOTAL marker reached; stopping row extraction.', [
                    'source' => $source,
                    'row_index' => $i,
                    'second_column_value' => $quantityUnitRaw,
                ]);

                break;
            }

            if ($itemName === '' && $quantityUnitRaw === '' && $amountText === '') {
                continue;
            }

            if ($itemName === '') {
                continue;
            }

            ['quantity' => $quantity, 'unit' => $unit] = $this->splitQuantityAndUnit($quantityUnitRaw);

            $items[] = [
                'item_name' => $itemName,
                'quantity' => $quantity,
                'unit' => $unit,
                'amount' => $this->parseCurrencyToFloat($amountText),
            ];
        }

        Log::info('WorkProgram table extraction completed.', [
            'source' => $source,
            'extracted_items_count' => count($items),
            'sample_items' => array_slice($items, 0, 5),
        ]);

        return $items;
    }

    private function parseCurrencyToFloat(string $value): ?float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', $value);
        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function findMatchingWorkProgramItemByName(string $name, Collection $workProgramItems): ?WorkProgramItem
    {
        if ($name === '' || $workProgramItems->isEmpty()) {
            return null;
        }

        $normalizedName = $this->normalizeItemText($name);

        return $workProgramItems->first(function (WorkProgramItem $item) use ($normalizedName): bool {
            return $this->normalizeItemText((string) $item->item_name) === $normalizedName;
        });
    }

    private function findMatchingAppItemByName(string $name, $appItems): ?APPItem
    {
        if ($name === '' || $appItems->isEmpty()) {
            return null;
        }

        $normalizedName = $this->normalizeItemText($name);

        $exact = $appItems->first(function (APPItem $item) use ($normalizedName): bool {
            return $this->normalizeItemText((string) $item->name) === $normalizedName;
        });
        if ($exact) {
            return $exact;
        }

        return $appItems->first(function (APPItem $item) use ($name): bool {
            $normalizedItemName = $this->normalizeItemText((string) $item->name);
            $normalizedSearch = $this->normalizeItemText($name);

            return str_contains($normalizedItemName, $normalizedSearch)
                || str_contains($normalizedSearch, $normalizedItemName);
        });
    }

    private function findMatchingPpmpItemByName(string $name, Collection $ppmpItems): ?PPMPItem
    {
        if ($name === '' || $ppmpItems->isEmpty()) {
            return null;
        }

        $normalizedName = $this->normalizeItemText($name);

        $exact = $ppmpItems->first(function (PPMPItem $item) use ($normalizedName): bool {
            return $this->normalizeItemText((string) $item->name) === $normalizedName;
        });

        if ($exact) {
            return $exact;
        }

        return $ppmpItems->first(function (PPMPItem $item) use ($normalizedName): bool {
            $normalizedItemName = $this->normalizeItemText((string) $item->name);

            return str_contains($normalizedItemName, $normalizedName)
                || str_contains($normalizedName, $normalizedItemName);
        });
    }

    private function normalizeItemText(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = str_replace(['’', '“', '”', "\t", "\r", "\n"], ['\'', '"', '"', ' ', ' ', ' '], $normalized);
        $normalized = preg_replace('/[^\pL\pN\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @return array{quantity:float|null, unit:string}
     */
    private function splitQuantityAndUnit(string $rawValue): array
    {
        $cleaned = trim(preg_replace('/\s+/u', ' ', $rawValue) ?? '');

        if ($cleaned === '') {
            return [
                'quantity' => null,
                'unit' => '',
            ];
        }

        $normalized = preg_replace('/\s*[-–—]\s*/u', ' ', $cleaned) ?? $cleaned;

        if (preg_match('/^([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]+)?|[0-9]+(?:\.[0-9]+)?)\s*(.*)$/u', $normalized, $matches) === 1) {
            $quantityRaw = isset($matches[1]) ? str_replace(',', '', (string) $matches[1]) : '';
            $quantity = is_numeric($quantityRaw) ? (float) $quantityRaw : null;
            $unit = isset($matches[2]) ? trim((string) $matches[2]) : '';

            return [
                'quantity' => $quantity,
                'unit' => $unit,
            ];
        }

        return [
            'quantity' => null,
            'unit' => $cleaned,
        ];
    }
}

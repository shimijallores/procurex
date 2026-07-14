<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\RFQExport;
use App\Http\Requests\StoreRFQRequest;
use App\Http\Requests\UpdateRFQRequest;
use App\Models\Calendar;
use App\Models\PurchaseRequest;
use App\Models\RFQ;
use App\Models\RFQItem;
use App\Services\SvpMatrixSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RFQController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RFQ::with([
            'purchaseRequest.office',
            'items',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('svp_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('project_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('purchaseRequest', function ($pr) use ($search): void {
                        $pr->where('pr_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('office', fn ($o) => $o->where('name', 'like', sprintf('%%%s%%', $search)));
                    });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('rfq_date', $fiscalYear);
            });

        $lengthAwarePaginator = (clone $query)->latest('rfq_date')->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'open' => (clone $query)
                ->where(function ($q): void {
                    $q->whereNull('submission_deadline')->orWhereDate('submission_deadline', '>=', now()->toDateString());
                })->count(),
            'with_deadline' => (clone $query)->whereNotNull('submission_deadline')->count(),
            'for_aoq' => (clone $query)->whereDoesntHave('aoq')->count(),
        ];

        $offices = \App\Models\Office::orderBy('name')->get(['id', 'name']);

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year): array => [$year => $year])
            ->reverse();

        return Inertia::render('RFQs/Index', [
            'rfqs' => $lengthAwarePaginator,
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
        $defaultRfqDate = $this->suggestNextRfqDate();

        return Inertia::render('RFQs/Create', [
            'defaultRfqDate' => $defaultRfqDate->toDateString(),
            'defaultSubmissionDeadline' => $this->suggestSubmissionDeadline($defaultRfqDate)->toDateString(),
            'defaultSvpNo' => $this->generateSvpNo($defaultRfqDate),
        ]);
    }

    public function suggestRfqDate(): JsonResponse
    {
        return response()->json([
            'rfq_date' => $this->suggestNextRfqDate()->toDateString(),
        ]);
    }

    public function store(StoreRFQRequest $storeRFQRequest): RedirectResponse
    {
        $validated = $storeRFQRequest->validated();

        DB::beginTransaction();
        try {
            $purchaseRequestQuery = PurchaseRequest::with([
                'items',
                'emanating.project',
                'emanating.ppmpCategory',
            ])
                ->where('status', 'approved')
                ->whereDoesntHave('rfq');

            if (! empty($validated['pr_id'])) {
                $purchaseRequestQuery->where('id', (int) $validated['pr_id']);
            } elseif (! empty($validated['pr_no'])) {
                $purchaseRequestQuery->where('pr_no', (string) $validated['pr_no']);
            }

            $purchaseRequest = $purchaseRequestQuery->first();

            if (! $purchaseRequest) {
                return redirect()->back()->with('error', 'The selected Purchase Request is not eligible for RFQ creation.');
            }

            $rfqDate = Carbon::parse($validated['rfq_date']);
            $submissionDeadline = empty($validated['submission_deadline'])
                ? $this->suggestSubmissionDeadline($rfqDate)
                : Carbon::parse($validated['submission_deadline']);

            $allowedPrItemIds = $purchaseRequest->items->pluck('id')->map(fn ($id): int => (int) $id)->all();

            foreach ($validated['items'] as $itemPayload) {
                if (! in_array((int) $itemPayload['pr_item_id'], $allowedPrItemIds, true)) {
                    return redirect()->back()->with('error', 'One or more RFQ items are invalid for the selected Purchase Request.');
                }
            }

            $svpNo = empty($validated['svp_no'])
                ? $this->generateSvpNo($rfqDate)
                : (string) $validated['svp_no'];

            $rfq = RFQ::create([
                'pr_id' => $purchaseRequest->id,
                'svp_no' => $svpNo,
                'rfq_date' => $rfqDate->toDateString(),
                'submission_deadline' => $submissionDeadline->toDateString(),
                'project_name' => (string) $validated['project_name'],
                'abc_amount' => (float) $validated['abc_amount'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['items'] as $itemPayload) {
                RFQItem::create([
                    'rfq_id' => $rfq->id,
                    'pr_item_id' => $itemPayload['pr_item_id'],
                    'item_name' => $itemPayload['item_name'],
                    'unit' => $itemPayload['unit'] ?? null,
                    'quantity' => (int) $itemPayload['quantity'],
                ]);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create RFQ. Please try again.');
        }

        SvpMatrixSyncService::syncRfqValue($rfq);

        return redirect()->route('rfqs.show', $rfq)
            ->with('success', 'RFQ created successfully.');
    }

    public function show(RFQ $rfq): Response
    {
        $rfq->load([
            'purchaseRequest.office',
            'purchaseRequest.fund',
            'purchaseRequest.emanating.project',
            'items.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        return Inertia::render('RFQs/Show', [
            'rfq' => $rfq,
        ]);
    }

    public function edit(RFQ $rfq): Response
    {
        $rfq->load([
            'purchaseRequest.office',
            'purchaseRequest.fund',
            'items',
        ]);

        return Inertia::render('RFQs/Edit', [
            'rfq' => $rfq,
        ]);
    }

    public function update(UpdateRFQRequest $updateRFQRequest, RFQ $rfq): RedirectResponse
    {
        $validated = $updateRFQRequest->validated();

        DB::beginTransaction();
        try {
            $rfq->update([
                'svp_no' => $validated['svp_no'] ?? $rfq->svp_no,
                'rfq_date' => $validated['rfq_date'],
                'submission_deadline' => $validated['submission_deadline'] ?? null,
                'project_name' => $validated['project_name'],
                'abc_amount' => (float) $validated['abc_amount'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $submittedItemIds = [];
            foreach ($validated['items'] as $itemPayload) {
                if (! empty($itemPayload['id'])) {
                    $rfqItem = RFQItem::find((int) $itemPayload['id']);
                    if ($rfqItem && $rfqItem->rfq_id === $rfq->id) {
                        $rfqItem->update([
                            'item_name' => $itemPayload['item_name'],
                            'unit' => $itemPayload['unit'] ?? null,
                            'quantity' => (int) $itemPayload['quantity'],
                        ]);
                        $submittedItemIds[] = $rfqItem->id;
                    }
                }
            }

            $rfq->items()->whereNotIn('id', $submittedItemIds)->delete();

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to update RFQ. Please try again.');
        }

        SvpMatrixSyncService::syncRfqValue($rfq);

        return redirect()->route('rfqs.show', $rfq)
            ->with('success', 'RFQ updated successfully.');
    }

    public function destroy(RFQ $rfq): RedirectResponse
    {
        SvpMatrixSyncService::syncRfqValue($rfq);
        $rfq->delete();

        return redirect()->route('rfqs.index')->with('success', 'RFQ deleted successfully.');
    }

    public function suggestPrs(Request $request): JsonResponse
    {
        $query = $request->input('q');

        $prs = PurchaseRequest::with([
            'office:id,name',
            'items:id,purchase_request_id,emanating_item_id,item_name,unit,quantity',
            'items.emanatingItem:id,name,unit',
            'items.emanatingItem.ppmpItem:id,name,unit',
        ])
            ->where('status', 'approved')
            ->whereDoesntHave('rfq')
            ->when($query, function ($builder, string $search): void {
                $builder->where(function ($sub) use ($search): void {
                    $sub->where('pr_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('office', fn ($o) => $o->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest()
            ->take(10)
            ->get(['id', 'pr_no', 'office_id', 'purpose', 'total_amount']);

        return response()->json(['prs' => $prs]);
    }

    public function recentSvps(Request $request): JsonResponse
    {
        $svps = RFQ::query()
            ->whereNotNull('svp_no')
            ->when($request->input('context') === 'noa', function ($q): void {
                $q->whereHas('aoq', function ($aoq): void {
                    $aoq->whereDoesntHave('noa');
                });
            }, function ($q): void {
                $q->whereDoesntHave('aoq');
            })
            ->latest('rfq_date')
            ->take(5)
            ->get(['id', 'svp_no']);

        return response()->json(['svps' => $svps]);
    }

    public function export(RFQ $rfq): BinaryFileResponse
    {
        $rfq->load([
            'purchaseRequest.office',
            'items.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        return Excel::download(
            new RFQExport($rfq),
            'RFQ-'.$rfq->svp_no.'.xlsx'
        );
    }

    public function downloadFiles(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $builder = RFQ::with([
            'purchaseRequest.office',
            'items.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        if ($validated['date_from']) {
            $builder->whereDate('rfq_date', '>=', $validated['date_from']);
        }

        if ($validated['date_to']) {
            $builder->whereDate('rfq_date', '<=', $validated['date_to']);
        }

        $rfqs = $builder->latest('rfq_date')->get();

        if ($rfqs->isEmpty()) {
            return redirect()->back()->with('error', 'No RFQs found for the selected filters.');
        }

        $zipArchive = new \ZipArchive;
        $zipFileName = 'RFQs-'.now()->format('Y-m-d-His').'.zip';
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.\DIRECTORY_SEPARATOR.$zipFileName;

        if ($zipArchive->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Failed to create ZIP archive.');
        }

        foreach ($rfqs as $rfq) {
            try {
                $xlsxPath = $tempDir.\DIRECTORY_SEPARATOR.sprintf('rfq-%s.xlsx', $rfq->id);

                Excel::store(
                    new RFQExport($rfq),
                    sprintf('temp/rfq-%s.xlsx', $rfq->id),
                    null,
                    \Maatwebsite\Excel\Excel::XLSX
                );

                $storedPath = storage_path('app/temp/rfq-'.$rfq->id.'.xlsx');
                if (file_exists($storedPath)) {
                    rename($storedPath, $xlsxPath);
                }

                $zipArchive->addFile($xlsxPath, sprintf('RFQ-%s.xlsx', $rfq->svp_no));
            } catch (\Throwable) {
                continue;
            }
        }

        $zipArchive->close();

        foreach ($rfqs as $rfq) {
            $xlsxPath = $tempDir.\DIRECTORY_SEPARATOR.sprintf('rfq-%s.xlsx', $rfq->id);

            if (file_exists($xlsxPath)) {
                unlink($xlsxPath);
            }
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    private function generateSvpNo(Carbon $rfqDate): string
    {
        $prefix = $rfqDate->format('Y').'-';

        $latest = RFQ::query()
            ->where('svp_no', 'like', $prefix.'%')
            ->orderByDesc('svp_no')
            ->value('svp_no');

        $next = 1;
        if ($latest && preg_match('/^\d{4}-(\d{4})$/', $latest, $matches) === 1) {
            $next = (int) $matches[1] + 1;
        }

        do {
            $svpNo = sprintf('%s%04d', $prefix, $next);
            $next++;
        } while (RFQ::where('svp_no', $svpNo)->exists());

        return $svpNo;
    }

    private function suggestNextRfqDate(): Carbon
    {
        return $this->suggestNextWorkingDay(now()->startOfDay());
    }

    private function suggestSubmissionDeadline(Carbon $rfqDate): Carbon
    {
        return $this->suggestNextWorkingDay($rfqDate->copy()->addWeek());
    }

    private function suggestNextWorkingDay(Carbon $startDate): Carbon
    {
        $date = $startDate->copy()->startOfDay();

        while (! $this->isWorkingDay($date->toDateString())) {
            $date->addDay();
        }

        return $date;
    }

    private function isWorkingDay(?string $date): bool
    {
        if (! $date) {
            return true;
        }

        $calendarEntry = Calendar::whereDate('date', $date)->first();
        if ($calendarEntry) {
            return (bool) $calendarEntry->is_working_day;
        }

        return ! Carbon::parse($date)->isWeekend();
    }
}

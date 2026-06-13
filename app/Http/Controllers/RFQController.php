<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRFQRequest;
use App\Models\Calendar;
use App\Models\PurchaseRequest;
use App\Models\RFQ;
use App\Models\RFQItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

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
        $eligiblePurchaseRequests = PurchaseRequest::with([
            'office',
            'fund',
            'emanating.ppmpCategory',
            'items.emanatingItem.ppmpItem',
        ])
            ->where('status', 'approved')
            ->whereDoesntHave('rfq')
            ->latest()
            ->get();

        $defaultRfqDate = $this->suggestNextRfqDate();

        return Inertia::render('RFQs/Create', [
            'eligiblePurchaseRequests' => $eligiblePurchaseRequests,
            'defaultRfqDate' => $defaultRfqDate->toDateString(),
            'defaultSubmissionDeadline' => $this->suggestSubmissionDeadline($defaultRfqDate)->toDateString(),
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

            $rfq = RFQ::create([
                'pr_id' => $purchaseRequest->id,
                'svp_no' => $this->generateSvpNo($rfqDate),
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

        return redirect()->route('rfqs.show', $rfq)
            ->with('success', 'RFQ created successfully. SVP number was generated automatically.');
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

    public function destroy(RFQ $rfq): RedirectResponse
    {
        $rfq->delete();

        return redirect()->route('rfqs.index')->with('success', 'RFQ deleted successfully.');
    }

    public function printPdf(RFQ $rfq): \Spatie\LaravelPdf\PdfBuilder
    {
        $rfq->load([
            'purchaseRequest.office',
            'items.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        return Pdf::view('pdf.rfq', [
            'rfq' => $rfq,
        ])
            ->format('a4')
            ->name('RFQ-'.$rfq->svp_no.'.pdf')
            ->inline();
    }

    private function generateSvpNo(Carbon $rfqDate): string
    {
        $year = $rfqDate->format('Y');
        $prefix = $year.'-';

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
            ++$next;
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

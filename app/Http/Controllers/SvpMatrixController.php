<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\SvpMatrixExport;
use App\Models\Office;
use App\Models\PurchaseOrder;
use App\Models\SvpMatrix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SvpMatrixController extends Controller
{
    public function index(Request $request): Response
    {
        $this->syncRowsFromPurchaseOrders();

        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);

        $builder = $this->buildMatrixQuery($request);

        $lengthAwarePaginator = $builder
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (SvpMatrix $svpMatrix): array => $this->transformMatrixRow($svpMatrix));

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])
            ->reverse();

        return Inertia::render('SVPMatrix/Index', [
            'matrixRows' => $lengthAwarePaginator,
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'fiscal_year' => $selectedFiscalYear,
            ],
        ]);
    }

    public function exportXlsx(Request $request): BinaryFileResponse
    {
        $this->syncRowsFromPurchaseOrders();

        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);

        $rows = $this->buildMatrixQuery($request)
            ->latest('id')
            ->get()
            ->values()
            ->map(function (SvpMatrix $svpMatrix, int $index): array {
                $transformed = $this->transformMatrixRow($svpMatrix);
                $transformed['row_no'] = $index + 1;

                return $transformed;
            })
            ->all();

        $fileName = sprintf('svp-matrix-%s.xlsx', $selectedFiscalYear !== '' ? $selectedFiscalYear : 'all-years');

        return Excel::download(new SvpMatrixExport($rows), $fileName);
    }

    public function show(SvpMatrix $svpMatrix): Response
    {
        $svpMatrix->load([
            'purchaseOrder.noa.aoq.batch',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.account',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.ppmpCategory',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
            'purchaseOrder.poTransmittals',
        ]);

        return Inertia::render('SVPMatrix/Show', [
            'matrixRow' => $this->transformMatrixRow($svpMatrix),
        ]);
    }

    public function edit(SvpMatrix $svpMatrix): Response
    {
        $svpMatrix->load([
            'purchaseOrder.noa.aoq.batch',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.account',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.ppmpCategory',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
            'purchaseOrder.poTransmittals',
        ]);

        return Inertia::render('SVPMatrix/Edit', [
            'matrixRow' => $this->transformMatrixRow($svpMatrix),
        ]);
    }

    public function update(Request $request, SvpMatrix $svpMatrix): RedirectResponse
    {
        $validated = $request->validate([
            'office_text' => ['nullable', 'string', 'max:255'],
            'po_no_text' => ['nullable', 'string', 'max:255'],
            'mode_of_procurement_text' => ['nullable', 'string', 'max:255'],
            'pr_no_text' => ['nullable', 'string', 'max:255'],
            'abc_amount' => ['nullable', 'numeric', 'min:0'],
            'supplier_text' => ['nullable', 'string', 'max:255'],
            'particulars_text' => ['nullable', 'string', 'max:255'],
            'amount_value' => ['nullable', 'numeric', 'min:0'],
            'rfq_value' => ['nullable', 'string', 'max:255'],
            'abstract_value' => ['nullable', 'string', 'max:255'],
            'resolution_value' => ['nullable', 'string', 'max:255'],
            'noa_po_value' => ['nullable', 'string', 'max:255'],
            'transmittal_form_value' => ['nullable', 'string', 'max:255'],
            'admin_value' => ['nullable', 'string', 'max:255'],
            'frontdesk_value' => ['nullable', 'string', 'max:255'],
            'remarks_value' => ['nullable', 'string', 'max:1000'],
        ]);

        if (blank($validated['admin_value'] ?? null)) {
            $validated['admin_value'] = auth()->user()?->name;
        }

        $svpMatrix->update($validated);

        return redirect()->route('svp-matrix.show', $svpMatrix)
            ->with('success', 'SVP Matrix row updated successfully.');
    }

    private function buildMatrixQuery(Request $request): Builder
    {
        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);

        return SvpMatrix::query()
            ->with([
                'purchaseOrder.noa.aoq.batch',
                'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
                'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.account',
                'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.ppmpCategory',
                'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
                'purchaseOrder.poTransmittals',
            ])
            ->whereHas('purchaseOrder')
            ->when($request->filled('office_id'), function (Builder $builder) use ($request): void {
                $builder->whereHas('purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest', function (Builder $builder) use ($request): void {
                    $builder->where('office_id', (int) $request->input('office_id'));
                });
            })
            ->when($selectedFiscalYear !== '', function (Builder $builder) use ($selectedFiscalYear): void {
                $builder->whereHas('purchaseOrder', function (Builder $builder) use ($selectedFiscalYear): void {
                    $builder->whereYear('po_date', (int) $selectedFiscalYear);
                });
            })
            ->when($request->search, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $builder) use ($search): void {
                    $builder->where('office_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('po_no_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('pr_no_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('supplier_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('particulars_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseOrder', function (Builder $builder) use ($search): void {
                            $builder->where('po_no', 'like', sprintf('%%%s%%', $search))
                                ->orWhereHas('noa.bacResolution.aoq.rfq.purchaseRequest', function (Builder $builder) use ($search): void {
                                    $builder->where('pr_no', 'like', sprintf('%%%s%%', $search));
                                })
                                ->orWhereHas('noa.bacResolution.aoq.winnerSupplier', function (Builder $builder) use ($search): void {
                                    $builder->where('name', 'like', sprintf('%%%s%%', $search));
                                });
                        });
                });
            });
    }

    private function transformMatrixRow(SvpMatrix $svpMatrix): array
    {
        $purchaseOrder = $svpMatrix->purchaseOrder;
        $noa = $purchaseOrder?->noa;
        $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;
        $rfq = $aoq?->rfq;
        $resolution = $noa?->bacResolution;
        $purchaseRequest = $rfq?->purchaseRequest;

        $abcAmount = $svpMatrix->abc_amount !== null
            ? (float) $svpMatrix->abc_amount
            : (float) ($rfq?->abc_amount ?? $purchaseRequest?->total_amount ?? 0);

        $amountValue = $svpMatrix->amount_value !== null
            ? (float) $svpMatrix->amount_value
            : (float) ($purchaseOrder?->total_amount ?? 0);

        $baseForMode = $abcAmount > 0 ? $abcAmount : $amountValue;
        $derivedMode = $baseForMode > 0
            ? ($baseForMode > 200000 ? 'SMALL VALUE 200k A' : 'SMALL VALUE 200k B')
            : null;

        $batch = $purchaseOrder?->noa?->aoq?->batch
            ?? $purchaseOrder?->noa?->bacResolution?->aoq?->batch;

        $coaTransmittal = $purchaseOrder?->poTransmittals
            ?->where('type', 'coa')
            ->sortByDesc('created_at')
            ->first();

        return [
            'id' => $svpMatrix->id,
            'svp_no' => $rfq?->svp_no,
            'purchase_order_id' => $purchaseOrder?->id,
            'office' => $svpMatrix->office_text ?? $purchaseRequest?->office?->name,
            'batch' => $batch?->batch_no,
            'po_no' => $svpMatrix->po_no_text ?? $purchaseOrder?->po_no,
            'mode_of_procurement' => $svpMatrix->mode_of_procurement_text ?? $derivedMode,
            'pr_no' => $svpMatrix->pr_no_text ?? $purchaseRequest?->pr_no,
            'abc' => $abcAmount > 0 ? round($abcAmount, 2) : null,
            'supplier' => $svpMatrix->supplier_text ?? $aoq?->winnerSupplier?->name,
            'particulars' => $svpMatrix->particulars_text
                ?? $purchaseRequest?->emanating?->account?->name
                ?? $purchaseRequest?->emanating?->ppmpCategory?->name,
            'amount' => $amountValue > 0 ? round($amountValue, 2) : null,
            'rfq' => $svpMatrix->rfq_value
                ?? $this->formatDateString($rfq?->rfq_date ?? $rfq?->created_at),
            'abstract' => $svpMatrix->abstract_value
                ?? $this->formatDateString($aoq?->aoq_date),
            'resolution' => $svpMatrix->resolution_value
                ?? $this->formatDateString($resolution?->resolution_date),
            'noa_po' => $svpMatrix->noa_po_value
                ?? $this->composeNoaPoValue($noa?->noa_date, $purchaseOrder?->po_date),
            'transmittal_form' => $svpMatrix->transmittal_form_value
                ?? $this->formatDateString($coaTransmittal?->created_at),
            'bac_members_gov' => $svpMatrix->admin_value,
            'frontdesk' => $svpMatrix->frontdesk_value,
            'remarks' => $svpMatrix->remarks_value,
            'created_at' => $svpMatrix->created_at?->toDateString(),
        ];
    }

    private function syncRowsFromPurchaseOrders(): void
    {
        $existingPurchaseOrderIds = SvpMatrix::query()->pluck('purchase_order_id')->all();

        $purchaseOrderIds = PurchaseOrder::query()
            ->whereNotIn('id', $existingPurchaseOrderIds)
            ->pluck('id');

        foreach ($purchaseOrderIds as $purchaseOrderId) {
            SvpMatrix::query()->firstOrCreate(
                ['purchase_order_id' => (int) $purchaseOrderId],
                ['admin_value' => auth()->user()?->name],
            );
        }
    }

    private function formatDateString($date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->format('m/d/Y');
    }

    private function composeNoaPoValue($noaDate, $poDate): ?string
    {
        $noaDateFormatted = $this->formatDateString($noaDate);
        $poDateFormatted = $this->formatDateString($poDate);

        if (! $noaDateFormatted && ! $poDateFormatted) {
            return null;
        }

        if ($noaDateFormatted && $poDateFormatted) {
            if ($noaDateFormatted === $poDateFormatted) {
                return $noaDateFormatted;
            }

            return sprintf('NOA %s | PO %s', $noaDateFormatted, $poDateFormatted);
        }

        return $noaDateFormatted ?: $poDateFormatted;
    }
}

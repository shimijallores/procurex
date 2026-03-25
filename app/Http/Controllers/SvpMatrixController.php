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

        $query = $this->buildMatrixQuery($request);

        $matrixRows = $query
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn(SvpMatrix $row): array => $this->transformMatrixRow($row));

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn(int $year): array => [(string) $year => (string) $year])
            ->reverse();

        return Inertia::render('SVPMatrix/Index', [
            'matrixRows' => $matrixRows,
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
            ->map(function (SvpMatrix $row, int $index): array {
                $transformed = $this->transformMatrixRow($row);
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

        $svpMatrix->update($validated);

        return redirect()->route('svp-matrix.show', $svpMatrix)
            ->with('success', 'SVP Matrix row updated successfully.');
    }

    private function buildMatrixQuery(Request $request): Builder
    {
        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);

        return SvpMatrix::query()
            ->with([
                'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
                'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.account',
                'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.emanating.ppmpCategory',
                'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
                'purchaseOrder.poTransmittals',
            ])
            ->whereHas('purchaseOrder')
            ->when($request->filled('office_id'), function (Builder $query) use ($request): void {
                $query->whereHas('purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest', function (Builder $purchaseRequestQuery) use ($request): void {
                    $purchaseRequestQuery->where('office_id', (int) $request->input('office_id'));
                });
            })
            ->when($selectedFiscalYear !== '', function (Builder $query) use ($selectedFiscalYear): void {
                $query->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery) use ($selectedFiscalYear): void {
                    $purchaseOrderQuery->whereYear('po_date', (int) $selectedFiscalYear);
                });
            })
            ->when($request->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery->where('office_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('po_no_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('pr_no_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('supplier_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('particulars_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseOrder', function (Builder $purchaseOrderQuery) use ($search): void {
                            $purchaseOrderQuery->where('po_no', 'like', sprintf('%%%s%%', $search))
                                ->orWhereHas('noa.bacResolution.aoq.rfq.purchaseRequest', function (Builder $purchaseRequestQuery) use ($search): void {
                                    $purchaseRequestQuery->where('pr_no', 'like', sprintf('%%%s%%', $search));
                                })
                                ->orWhereHas('noa.bacResolution.aoq.winnerSupplier', function (Builder $supplierQuery) use ($search): void {
                                    $supplierQuery->where('name', 'like', sprintf('%%%s%%', $search));
                                });
                        });
                });
            });
    }

    private function transformMatrixRow(SvpMatrix $row): array
    {
        $purchaseOrder = $row->purchaseOrder;
        $rfq = $purchaseOrder?->noa?->bacResolution?->aoq?->rfq;
        $aoq = $purchaseOrder?->noa?->bacResolution?->aoq;
        $resolution = $purchaseOrder?->noa?->bacResolution;
        $noa = $purchaseOrder?->noa;
        $purchaseRequest = $rfq?->purchaseRequest;

        $abcAmount = $row->abc_amount !== null
            ? (float) $row->abc_amount
            : (float) ($rfq?->abc_amount ?? $purchaseRequest?->total_amount ?? 0);

        $amountValue = $row->amount_value !== null
            ? (float) $row->amount_value
            : (float) ($purchaseOrder?->total_amount ?? 0);

        $baseForMode = $abcAmount > 0 ? $abcAmount : $amountValue;
        $derivedMode = $baseForMode > 0
            ? ($baseForMode > 200000 ? 'SMALL VALUE 200k A' : 'SMALL VALUE 200k B')
            : null;

        $coaTransmittal = $purchaseOrder?->poTransmittals
            ?->where('type', 'coa')
            ->sortByDesc('transmittal_date')
            ->first();

        return [
            'id' => $row->id,
            'purchase_order_id' => $purchaseOrder?->id,
            'office' => $row->office_text ?? $purchaseRequest?->office?->name,
            'po_no' => $row->po_no_text ?? $purchaseOrder?->po_no,
            'mode_of_procurement' => $row->mode_of_procurement_text ?? $derivedMode,
            'pr_no' => $row->pr_no_text ?? $purchaseRequest?->pr_no,
            'abc' => $abcAmount > 0 ? round($abcAmount, 2) : null,
            'supplier' => $row->supplier_text ?? $purchaseOrder?->noa?->bacResolution?->aoq?->winnerSupplier?->name,
            'particulars' => $row->particulars_text
                ?? $purchaseRequest?->emanating?->account?->name
                ?? $purchaseRequest?->emanating?->ppmpCategory?->name,
            'amount' => $amountValue > 0 ? round($amountValue, 2) : null,
            'rfq' => $row->rfq_value
                ?? $this->formatDateString($rfq?->rfq_date ?? $rfq?->created_at),
            'abstract' => $row->abstract_value
                ?? $this->formatDateString($aoq?->created_at),
            'resolution' => $row->resolution_value
                ?? $this->formatDateString($resolution?->created_at),
            'noa_po' => $row->noa_po_value
                ?? $this->composeNoaPoValue($noa?->created_at, $purchaseOrder?->created_at),
            'transmittal_form' => $row->transmittal_form_value
                ?? $this->formatDateString($coaTransmittal?->transmittal_date ?? $coaTransmittal?->created_at),
            'admin' => $row->admin_value,
            'frontdesk' => $row->frontdesk_value,
            'remarks' => $row->remarks_value,
            'created_at' => $row->created_at?->toDateString(),
        ];
    }

    private function syncRowsFromPurchaseOrders(): void
    {
        $existingPurchaseOrderIds = SvpMatrix::query()->pluck('purchase_order_id')->all();

        $purchaseOrderIds = PurchaseOrder::query()
            ->whereNotIn('id', $existingPurchaseOrderIds)
            ->pluck('id');

        foreach ($purchaseOrderIds as $purchaseOrderId) {
            SvpMatrix::query()->firstOrCreate([
                'purchase_order_id' => (int) $purchaseOrderId,
            ]);
        }
    }

    private function formatDateString($date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->format('m/d/Y');
    }

    private function composeNoaPoValue($noaCreatedAt, $poCreatedAt): ?string
    {
        $noaDate = $this->formatDateString($noaCreatedAt);
        $poDate = $this->formatDateString($poCreatedAt);

        if (! $noaDate && ! $poDate) {
            return null;
        }

        if ($noaDate && $poDate) {
            if ($noaDate === $poDate) {
                return $noaDate;
            }

            return sprintf('NOA %s | PO %s', $noaDate, $poDate);
        }

        return $noaDate ?: $poDate;
    }
}

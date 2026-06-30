<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AOQ;
use App\Models\BACResolution;
use App\Models\NOA;
use App\Models\PurchaseOrder;
use App\Models\RFQ;
use App\Models\SvpMatrix;

class SvpMatrixSyncService
{
    public static function createFromRfq(RFQ $rfq): void
    {
        $rfq->loadMissing('purchaseRequest.office');
        $purchaseRequest = $rfq->purchaseRequest;

        $abcAmount = (float) ($rfq->abc_amount ?? $purchaseRequest?->total_amount ?? 0);
        $baseForMode = $abcAmount > 0 ? $abcAmount : 0;
        $derivedMode = $baseForMode > 0
            ? ($baseForMode > 200000 ? 'SMALL VALUE 200k A' : 'SMALL VALUE 200k B')
            : null;

        SvpMatrix::query()->updateOrCreate(
            ['rfq_id' => $rfq->id],
            [
                'office_text' => $purchaseRequest?->office?->name,
                'pr_no_text' => $purchaseRequest?->pr_no,
                'mode_of_procurement_text' => $derivedMode,
                'abc_amount' => $abcAmount > 0 ? $abcAmount : null,
                'rfq_value' => self::formatDate($rfq->rfq_date ?? $rfq->created_at),
            ],
        );
    }

    public static function syncRfqValue(RFQ $rfq): void
    {
        $rfq->loadMissing('aoq.noa.purchaseOrder.svpMatrix');

        // First try by rfq_id (RFQ-created rows)
        $svpMatrix = SvpMatrix::where('rfq_id', $rfq->id)->first();

        // If not found, try by purchase_order_id chain (old PO-created rows)
        if (! $svpMatrix) {
            $svpMatrix = $rfq->aoq?->noa?->purchaseOrder?->svpMatrix;
        }

        if (! $svpMatrix) {
            self::createFromRfq($rfq);

            return;
        }

        // Link the rfq_id if it was missing (migration from old rows)
        $svpMatrix->updateQuietly([
            'rfq_id' => $rfq->id,
            'rfq_value' => self::formatDate($rfq->rfq_date ?? $rfq->created_at),
            'abc_amount' => (float) ($rfq->abc_amount ?? 0),
        ]);
    }

    public static function syncAbstractValue(AOQ $aoq): void
    {
        $svpMatrix = SvpMatrix::where('rfq_id', $aoq->rfq_id)->first();

        if ($svpMatrix) {
            $svpMatrix->updateQuietly([
                'abstract_value' => self::formatDate($aoq->aoq_date),
            ]);
        }
    }

    public static function syncResolutionValue(BACResolution $bacResolution): void
    {
        $bacResolution->loadMissing('aoqs', 'aoq');

        $rfqIds = $bacResolution->aoqs->pluck('rfq_id')->filter()->unique()->all();
        foreach ($rfqIds as $rfqId) {
            $svpMatrix = SvpMatrix::where('rfq_id', $rfqId)->first();
            if ($svpMatrix) {
                $svpMatrix->updateQuietly([
                    'resolution_value' => self::formatDate($bacResolution->resolution_date),
                ]);
            }
        }

        // Also try primary AOQ
        if ($bacResolution->aoq && ! in_array($bacResolution->aoq->rfq_id, $rfqIds, true)) {
            $svpMatrix = SvpMatrix::where('rfq_id', $bacResolution->aoq->rfq_id)->first();
            if ($svpMatrix) {
                $svpMatrix->updateQuietly([
                    'resolution_value' => self::formatDate($bacResolution->resolution_date),
                ]);
            }
        }
    }

    public static function syncNoaValue(NOA $noa): void
    {
        $noa->loadMissing('aoq');
        $svpMatrix = SvpMatrix::where('rfq_id', $noa->aoq?->rfq_id)->first();

        if ($svpMatrix) {
            $noaDate = $noa->noa_date;
            $poDate = $svpMatrix->purchaseOrder?->po_date;
            $svpMatrix->updateQuietly([
                'noa_po_value' => self::composeNoaPoValue($noaDate, $poDate),
            ]);
        }
    }

    public static function syncTransmittalValue(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('svpMatrix', 'poTransmittals');
        $svpMatrix = $purchaseOrder->svpMatrix;

        if (! $svpMatrix) {
            return;
        }

        $coaTransmittal = $purchaseOrder->poTransmittals
            ->where('type', 'coa')
            ->sortByDesc('created_at')
            ->first();

        $svpMatrix->updateQuietly([
            'transmittal_form_value' => self::formatDate($coaTransmittal?->created_at),
        ]);
    }

    public static function createOrSyncFromPo(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.rfq.purchaseRequest.emanating.account',
            'noa.aoq.rfq.purchaseRequest.emanating.ppmpCategory',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution',
            'poTransmittals',
            'svpMatrix',
        ]);

        $noa = $purchaseOrder->noa;
        $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;
        $rfq = $aoq?->rfq;
        $resolution = $noa?->bacResolution;
        $purchaseRequest = $rfq?->purchaseRequest;

        $abcAmount = (float) ($rfq?->abc_amount ?? $purchaseRequest?->total_amount ?? 0);
        $amountValue = (float) ($purchaseOrder->total_amount ?? 0);
        $baseForMode = $abcAmount > 0 ? $abcAmount : $amountValue;
        $derivedMode = $baseForMode > 0
            ? ($baseForMode > 200000 ? 'SMALL VALUE 200k A' : 'SMALL VALUE 200k B')
            : null;

        $coaTransmittal = $purchaseOrder->poTransmittals
            ?->where('type', 'coa')
            ->sortByDesc('created_at')
            ->first();

        $particulars = $purchaseRequest?->emanating?->account?->name
            ?? $purchaseRequest?->emanating?->ppmpCategory?->name;

        $data = [
            'purchase_order_id' => $purchaseOrder->id,
            'office_text' => $purchaseRequest?->office?->name,
            'po_no_text' => $purchaseOrder->po_no,
            'mode_of_procurement_text' => $derivedMode,
            'pr_no_text' => $purchaseRequest?->pr_no,
            'abc_amount' => $abcAmount > 0 ? $abcAmount : null,
            'supplier_text' => $aoq?->winnerSupplier?->name,
            'particulars_text' => $particulars,
            'amount_value' => $amountValue > 0 ? $amountValue : null,
            'rfq_value' => self::formatDate($rfq?->rfq_date ?? $rfq?->created_at),
            'abstract_value' => self::formatDate($aoq?->aoq_date),
            'resolution_value' => self::formatDate($resolution?->resolution_date),
            'noa_po_value' => self::composeNoaPoValue($noa?->noa_date, $purchaseOrder->po_date),
            'transmittal_form_value' => self::formatDate($coaTransmittal?->created_at),
        ];

        if ($rfq) {
            $data['rfq_id'] = $rfq->id;

            // Remove any stale row that only has purchase_order_id (from old flow)
            SvpMatrix::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->whereNull('rfq_id')
                ->delete();

            SvpMatrix::query()->updateOrCreate(
                ['rfq_id' => $rfq->id],
                $data,
            );
        } else {
            SvpMatrix::query()->updateOrCreate(
                ['purchase_order_id' => $purchaseOrder->id],
                $data,
            );
        }
    }

    private static function formatDate(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        if (is_string($date)) {
            return $date;
        }

        return $date->format('m/d/Y');
    }

    private static function composeNoaPoValue(mixed $noaDate, mixed $poDate): ?string
    {
        $noaFormatted = self::formatDate($noaDate);
        $poFormatted = self::formatDate($poDate);

        if (! $noaFormatted && ! $poFormatted) {
            return null;
        }

        if ($noaFormatted && $poFormatted) {
            if ($noaFormatted === $poFormatted) {
                return $noaFormatted;
            }

            return sprintf('NOA %s | PO %s', $noaFormatted, $poFormatted);
        }

        return $noaFormatted ?: $poFormatted;
    }
}

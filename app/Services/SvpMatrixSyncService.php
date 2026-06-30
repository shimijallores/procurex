<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AOQ;
use App\Models\BACResolution;
use App\Models\NOA;
use App\Models\POTransmittal;
use App\Models\PurchaseOrder;
use App\Models\RFQ;
use App\Models\SvpMatrix;

class SvpMatrixSyncService
{
    public static function syncRfqValue(RFQ $rfq): void
    {
        $rfq->loadMissing('aoq.noa.purchaseOrder');
        $aoq = $rfq->aoq;
        $noa = $aoq?->noa;
        $po = $noa?->purchaseOrder;

        if ($po?->svpMatrix) {
            $po->svpMatrix->updateQuietly([
                'rfq_value' => self::formatDate($rfq->rfq_date ?? $rfq->created_at),
                'abc_amount' => (float) ($rfq->abc_amount ?? 0),
            ]);
        }
    }

    public static function syncAbstractValue(AOQ $aoq): void
    {
        $aoq->loadMissing('noa.purchaseOrder.svpMatrix');
        $noa = $aoq->noa;
        $po = $noa?->purchaseOrder;

        if ($po?->svpMatrix) {
            $po->svpMatrix->updateQuietly([
                'abstract_value' => self::formatDate($aoq->aoq_date),
            ]);
        }
    }

    public static function syncResolutionValue(BACResolution $resolution): void
    {
        $resolution->loadMissing('aoqs.noa.purchaseOrder.svpMatrix');
        // Also check primary aoq
        if ($resolution->aoq) {
            $resolution->loadMissing('aoq.noa.purchaseOrder.svpMatrix');
        }

        $updated = false;
        foreach ($resolution->aoqs as $aoq) {
            $noa = $aoq->noa;
            $po = $noa?->purchaseOrder;
            if ($po?->svpMatrix) {
                $po->svpMatrix->updateQuietly([
                    'resolution_value' => self::formatDate($resolution->resolution_date),
                ]);
                $updated = true;
            }
        }

        // If no AOQs through pivot (draft), try the primary aoq
        if (! $updated && $resolution->aoq) {
            $noa = $resolution->aoq->noa;
            $po = $noa?->purchaseOrder;
            if ($po?->svpMatrix) {
                $po->svpMatrix->updateQuietly([
                    'resolution_value' => self::formatDate($resolution->resolution_date),
                ]);
            }
        }
    }

    public static function syncNoaValue(NOA $noa): void
    {
        $noa->loadMissing('purchaseOrder.svpMatrix');
        $po = $noa->purchaseOrder;

        if ($po?->svpMatrix) {
            $po->svpMatrix->updateQuietly([
                'noa_po_value' => self::composeNoaPoValue($noa->noa_date, $po->po_date),
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

        SvpMatrix::query()->updateOrCreate(
            ['purchase_order_id' => $purchaseOrder->id],
            $data,
        );
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

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class AcceptanceInspection extends Model
{
    use Searchable;

    protected $table = 'acceptance_inspections';

    protected $fillable = [
        'purchase_order_id',
        'air_no',
        'invoice_no',
        'acceptance_date_received',
        'acceptance_status',
        'inspection_date_inspected',
        'inspection_findings_text',
        'inspection_status_ok',
        'property_officer_name',
        'property_officer_title',
        'inspection_officer_name',
        'inspection_officer_title',
    ];

    protected $casts = [
        'acceptance_date_received' => 'date',
        'inspection_date_inspected' => 'date',
        'inspection_status_ok' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function toSearchableArray(): array
    {
        return [
            'air_no' => $this->air_no,
            'invoice_no' => $this->invoice_no,
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}

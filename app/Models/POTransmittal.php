<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class POTransmittal extends Model
{
    protected $table = 'po_transmittals';

    protected $fillable = [
        'purchase_order_id',
        'type',
        'transmittal_no',
        'transmittal_date',
        'header_text',
        'signatory_name',
        'signatory_title',
        'coa_circular_no',
    ];

    protected $casts = [
        'transmittal_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}

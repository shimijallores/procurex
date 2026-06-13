<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class POTransmittal extends Model
{
    use Searchable;

    protected $table = 'po_transmittals';

    protected $fillable = [
        'purchase_order_id',
        'type',
        'transmittal_no',
        'header_text',
        'signatory_name',
        'signatory_title',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function toSearchableArray(): array
    {
        return [
            'transmittal_no' => $this->transmittal_no,
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}

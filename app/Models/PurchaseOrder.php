<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'noa_id',
        'po_no',
        'po_date',
        'mode_of_procurement',
        'place_of_delivery',
        'delivery_term_days',
        'payment_term',
        'total_amount',
        'total_amount_words',
        'remarks',
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_term_days' => 'integer',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function noa(): BelongsTo
    {
        return $this->belongsTo(NOA::class, 'noa_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function poTransmittals(): HasMany
    {
        return $this->hasMany(POTransmittal::class, 'purchase_order_id');
    }

    public function acceptanceInspection(): HasOne
    {
        return $this->hasOne(AcceptanceInspection::class, 'purchase_order_id');
    }

    public function coaInspection(): HasOne
    {
        return $this->hasOne(COAInspection::class, 'purchase_order_id');
    }
}

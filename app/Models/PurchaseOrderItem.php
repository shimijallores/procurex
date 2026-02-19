<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'rfq_item_id',
        'quantity_snapshot',
        'unit_cost_snapshot',
        'amount_snapshot',
    ];

    protected $casts = [
        'quantity_snapshot' => 'integer',
        'unit_cost_snapshot' => 'decimal:2',
        'amount_snapshot' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RFQItem::class, 'rfq_item_id');
    }
}

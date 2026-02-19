<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RFQSupplierItem extends Model
{
    protected $table = 'rfq_supplier_items';

    protected $fillable = [
        'rfq_supplier_id',
        'rfq_item_id',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function rfqSupplier(): BelongsTo
    {
        return $this->belongsTo(RFQSupplier::class, 'rfq_supplier_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RFQItem::class, 'rfq_item_id');
    }
}

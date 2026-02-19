<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RFQItem extends Model
{
    protected $table = 'rfq_items';

    protected $fillable = [
        'rfq_id',
        'pr_item_id',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class, 'rfq_id');
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'pr_item_id');
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(RFQSupplierItem::class, 'rfq_item_id');
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'rfq_item_id');
    }
}

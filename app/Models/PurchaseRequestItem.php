<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestItem extends Model
{
    protected $table = 'purchase_request_items';

    protected $fillable = [
        'purchase_request_id',
        'emanating_item_id',
        'quantity',
        'unit_cost',
        'line_total',
        'vat_applicable',
        'vat_rate',
        'remarks',
    ];

    protected $casts = [
        'quantity'       => 'integer',
        'unit_cost'      => 'decimal:2',
        'line_total'     => 'decimal:2',
        'vat_applicable' => 'boolean',
        'vat_rate'       => 'decimal:4',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    protected $appends = [
        'item_name',
        'unit',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function emanatingItem(): BelongsTo
    {
        return $this->belongsTo(EmanatingItem::class);
    }

    public function rfqItems(): HasMany
    {
        return $this->hasMany(RFQItem::class, 'pr_item_id');
    }

    /**
     * Get the item name from the related PPMP item.
     */
    public function getItemNameAttribute(): ?string
    {
        return $this->emanatingItem?->name ?: $this->emanatingItem?->ppmpItem?->name;
    }

    /**
     * Get the unit from the emanating item.
     */
    public function getUnitAttribute(): ?string
    {
        return $this->emanatingItem?->unit;
    }
}

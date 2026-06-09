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
        'matrix_amount_below_1m',
        'matrix_amount_above_1m',
        'matrix_new_amount',
        'matrix_account_id',
        'matrix_pr_admin_user_id',
        'matrix_budgeting_admin_user_id',
        'matrix_date_release',
        'matrix_new_date_release',
        'matrix_remarks',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
        'vat_applicable' => 'boolean',
        'vat_rate' => 'decimal:4',
        'matrix_amount_below_1m' => 'decimal:2',
        'matrix_amount_above_1m' => 'decimal:2',
        'matrix_new_amount' => 'decimal:2',
        'matrix_date_release' => 'date',
        'matrix_new_date_release' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function matrixAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'matrix_account_id');
    }

    public function matrixPrAdminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matrix_pr_admin_user_id');
    }

    public function matrixBudgetingAdminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matrix_budgeting_admin_user_id');
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

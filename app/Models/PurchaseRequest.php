<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseRequest extends Model
{
    protected $table = 'purchase_requests';

    protected $fillable = [
        'emanating_id',
        'office_id',
        'fund_id',
        'pr_no',
        'pr_date',
        'sai_no',
        'sai_date',
        'purpose',
        'total_amount',
        'status',
        'remarks',
    ];

    protected $casts = [
        'pr_date'      => 'date',
        'sai_date'     => 'date',
        'total_amount' => 'decimal:2',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function emanating(): BelongsTo
    {
        return $this->belongsTo(Emanating::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function earmark(): HasOne
    {
        return $this->hasOne(Earmark::class);
    }

    public function rfq(): HasOne
    {
        return $this->hasOne(RFQ::class, 'pr_id');
    }
}

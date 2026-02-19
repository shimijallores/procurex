<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RFQ extends Model
{
    protected $table = 'rfqs';

    protected $fillable = [
        'pr_id',
        'svp_no',
        'rfq_date',
        'submission_deadline',
        'project_name',
        'abc_amount',
        'remarks',
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'submission_deadline' => 'date',
        'abc_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'pr_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RFQItem::class, 'rfq_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(RFQSupplier::class, 'rfq_id');
    }
}

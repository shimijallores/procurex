<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earmark extends Model
{
    protected $table = 'earmarks';

    protected $fillable = [
        'purchase_request_id',
        'fund_id',
        'earmark_no',
        'earmark_date',
        'certified_amount',
        'expense_class',
        'resolution_no',
        'ordinance_no',
        'ordinance_date',
        'remarks',
    ];

    protected $casts = [
        'earmark_date'     => 'date',
        'ordinance_date'   => 'date',
        'certified_amount' => 'decimal:2',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}

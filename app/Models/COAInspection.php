<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class COAInspection extends Model
{
    protected $table = 'coa_inspections';

    protected $fillable = [
        'purchase_order_id',
        'svp_header_text',
        'svp_salutation',
        'bidding_header_text',
        'bidding_salutation',
        'signatory_name',
        'signatory_title',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}

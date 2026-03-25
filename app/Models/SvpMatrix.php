<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SvpMatrix extends Model
{
    protected $table = 'svp_matrices';

    protected $fillable = [
        'purchase_order_id',
        'office_text',
        'po_no_text',
        'mode_of_procurement_text',
        'pr_no_text',
        'abc_amount',
        'supplier_text',
        'particulars_text',
        'amount_value',
        'rfq_value',
        'abstract_value',
        'resolution_value',
        'noa_po_value',
        'transmittal_form_value',
        'admin_value',
        'frontdesk_value',
        'remarks_value',
    ];

    protected $casts = [
        'abc_amount' => 'decimal:2',
        'amount_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}

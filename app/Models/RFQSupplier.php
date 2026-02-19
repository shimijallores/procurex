<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RFQSupplier extends Model
{
    protected $table = 'rfq_suppliers';

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'is_late',
        'submitted_at',
        'remarks',
    ];

    protected $casts = [
        'is_late' => 'boolean',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class, 'rfq_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(RFQSupplierItem::class, 'rfq_supplier_id');
    }
}

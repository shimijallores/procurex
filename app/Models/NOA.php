<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NOA extends Model
{
    protected $table = 'noas';

    protected $fillable = [
        'bac_resolution_id',
        'noa_no',
        'noa_date',
    ];

    protected $casts = [
        'noa_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function bacResolution(): BelongsTo
    {
        return $this->belongsTo(BACResolution::class, 'bac_resolution_id');
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'noa_id');
    }
}

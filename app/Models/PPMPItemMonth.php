<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PPMPItemMonth extends Model
{
    protected $table = 'ppmp_item_months';

    protected $fillable = [
        'ppmp_item_id',
        'month',
        'planned_quantity',
    ];

    protected $casts = [
        'month' => 'integer',
        'planned_quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PPMPItem::class, 'ppmp_item_id');
    }
}

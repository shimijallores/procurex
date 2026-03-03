<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmanatingItem extends Model
{
    protected $table = 'emanating_items';

    protected $fillable = [
        'emanating_id',
        'ppmp_item_id',
        'name',
        'quantity',
        'unit',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function emanating(): BelongsTo
    {
        return $this->belongsTo(Emanating::class);
    }

    public function ppmpItem(): BelongsTo
    {
        return $this->belongsTo(PPMPItem::class);
    }
}

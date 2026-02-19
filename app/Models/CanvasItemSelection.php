<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanvasItemSelection extends Model
{
    protected $fillable = [
        'canvas_item_id',
        'master_list_item_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function canvasItem(): BelongsTo
    {
        return $this->belongsTo(CanvasItem::class);
    }

    public function masterListItem(): BelongsTo
    {
        return $this->belongsTo(MasterListItem::class);
    }
}

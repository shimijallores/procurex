<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanvasItem extends Model
{
    protected $fillable = [
        'canvas_id',
        'emanating_item_id',
        'computed_price',
    ];

    protected $casts = [
        'computed_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function canvas(): BelongsTo
    {
        return $this->belongsTo(Canvas::class);
    }

    public function emanatingItem(): BelongsTo
    {
        return $this->belongsTo(EmanatingItem::class);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(CanvasItemSelection::class);
    }
}

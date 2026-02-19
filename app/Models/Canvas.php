<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canvas extends Model
{
    protected $table = 'canvasses';

    protected $fillable = [
        'emanating_id',
        'created_by',
        'status',
        'return_reason',
        'total_amount',
        'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function emanating(): BelongsTo
    {
        return $this->belongsTo(Emanating::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canvasItems(): HasMany
    {
        return $this->hasMany(CanvasItem::class);
    }
}

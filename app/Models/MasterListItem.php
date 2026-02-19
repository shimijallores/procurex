<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterListItem extends Model
{
    protected $fillable = [
        'master_list_category_id',
        'supplier_id',
        'item_name',
        'unit',
        'default_unit_price',
        'is_phased_out',
        'phased_out_reason',
        'remarks',
        'search_key',
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:2',
        'is_phased_out' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function masterListCategory(): BelongsTo
    {
        return $this->belongsTo(MasterListCategory::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function canvasItemSelections(): HasMany
    {
        return $this->hasMany(CanvasItemSelection::class);
    }
}

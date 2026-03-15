<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PPMPItem extends Model
{
    protected $table = 'ppmp_items';

    protected $fillable = [
        'ppmp_category_id',
        'name',
        'quantity',
        'unit',
        'estimated_budget',
        'remaining_budget',
        'mode_of_procurement',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_budget' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['months'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PPMPCategory::class, 'ppmp_category_id');
    }

    public function months(): HasMany
    {
        return $this->hasMany(PPMPItemMonth::class, 'ppmp_item_id');
    }

    public function getMonthsAttribute()
    {
        return $this->months()->get();
    }
}

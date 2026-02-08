<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PPMPCategory extends Model
{
    protected $table = 'ppmp_categories';

    protected $fillable = [
        'ppmp_id',
        'code',
        'name',
        'estimated_budget',
    ];

    protected $casts = [
        'estimated_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ppmp(): BelongsTo
    {
        return $this->belongsTo(PPMP::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PPMPItem::class, 'ppmp_category_id');
    }
}

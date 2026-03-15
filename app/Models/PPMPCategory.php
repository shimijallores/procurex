<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PPMPCategory extends Model
{
    protected $table = 'ppmp_categories';

    protected $fillable = [
        'ppmp_id',
        'account_id',
        'estimated_budget',
        'remaining_budget',
    ];

    protected $casts = [
        'estimated_budget' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['code', 'name', 'items'];

    public function ppmp(): BelongsTo
    {
        return $this->belongsTo(PPMP::class, 'ppmp_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PPMPItem::class, 'ppmp_category_id');
    }

    public function getItemsAttribute()
    {
        return $this->items()->with('months')->get();
    }

    public function getCodeAttribute(): ?string
    {
        return $this->account?->code;
    }

    public function getNameAttribute(): ?string
    {
        return $this->account?->name;
    }
}

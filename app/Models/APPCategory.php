<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class APPCategory extends Model
{
    use HasFactory;

    protected $table = 'app_categories';

    protected $fillable = [
        'app_id',
        'pap_code',
        'name',
        'early_procurement',
        'mode_of_procurement',
        'schedule_from_month',
        'schedule_to_month',
        'source_of_fund',
        'estimated_budget',
        'mooe_amount',
        'co_amount',
        'remarks',
    ];

    protected $casts = [
        'early_procurement' => 'boolean',
        'schedule_from_month' => 'integer',
        'schedule_to_month' => 'integer',
        'estimated_budget' => 'decimal:2',
        'mooe_amount' => 'decimal:2',
        'co_amount' => 'decimal:2',
    ];

    protected $appends = ['items'];

    public function APP(): BelongsTo
    {
        return $this->belongsTo(APP::class, 'app_id');
    }

    public function APPItems(): HasMany
    {
        return $this->hasMany(APPItem::class, 'app_category_id');
    }

    public function getItemsAttribute()
    {
        return $this->APPItems;
    }
}

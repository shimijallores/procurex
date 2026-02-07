<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class APPItem extends Model
{
    use HasFactory;

    protected $table = 'app_items';

    protected $fillable = [
        'app_category_id',
        'name',
        'estimated_budget',
        'mooe_amount',
        'co_amount',
        'remarks',
    ];

    protected $casts = [
        'estimated_budget' => 'decimal:2',
        'mooe_amount' => 'decimal:2',
        'co_amount' => 'decimal:2',
    ];

    public function appCategory(): BelongsTo
    {
        return $this->belongsTo(APPCategory::class, 'app_category_id');
    }
}

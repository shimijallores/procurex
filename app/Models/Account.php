<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'main_category',
        'subcategory',
        'code',
        'name',
    ];

    public function appCategories(): HasMany
    {
        return $this->hasMany(APPCategory::class, 'account_id');
    }
}

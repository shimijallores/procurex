<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class APP extends Model
{
    use HasFactory;

    protected $table = 'apps';

    protected $fillable = [
        'office_id',
        'fiscal_year',
        'uploaded_file',
    ];

    protected $appends = ['categories'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function APPCategories(): HasMany
    {
        return $this->hasMany(APPCategory::class, 'app_id');
    }

    public function getCategoriesAttribute()
    {
        return $this->APPCategories;
    }
}

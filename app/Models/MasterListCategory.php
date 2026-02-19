<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterListCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function masterListItems(): HasMany
    {
        return $this->hasMany(MasterListItem::class);
    }
}

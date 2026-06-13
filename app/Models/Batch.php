<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'batch_no',
    ];

    public function aoqs(): HasMany
    {
        return $this->hasMany(AOQ::class, 'batch_id');
    }
}

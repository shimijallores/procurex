<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Funds extends Model
{
    /** @use HasFactory<\Database\Factories\FundsFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'fiscal_year',
        'remarks',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }
}

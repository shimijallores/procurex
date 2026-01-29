<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Funds extends Model
{
    /** @use HasFactory<\Database\Factories\FundsFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

//    public function project(): BelongsTo
//    {
//        return $this->belongsTo(Project::class);
//    }
}

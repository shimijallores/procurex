<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProjectCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'code',
        'name',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}

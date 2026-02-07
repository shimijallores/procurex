<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBrief extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectBriefFactory> */
    use HasFactory;

    protected $fillable = [
        'file_url',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

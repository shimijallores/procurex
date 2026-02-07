<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProposal extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectProposalFactory> */
    use HasFactory;

    protected $fillable = [
        'file_url',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

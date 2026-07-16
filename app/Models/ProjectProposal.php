<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProjectProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProposal extends Model
{
    /** @use HasFactory<ProjectProposalFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'file_url',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

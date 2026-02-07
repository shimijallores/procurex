<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'remarks'
    ];

    public function funds(): BelongsTo
    {
        return $this->belongsTo(Funds::class);
    }

    public function workProgram(): HasOne
    {
        return $this->hasOne(WorkProgram::class);
    }

    public function projectBrief(): HasOne
    {
        return $this->hasOne(ProjectBrief::class);
    }

    public function projectProposal(): HasOne
    {
        return $this->hasOne(ProjectProposal::class);
    }

    // public function app(): HasOne
    // {
    //     return $this->hasOne(APP::class);
    // }

    // public function PPMP(): HasOne
    // {
    //     return $this->hasOne(PPMP::class);
    // }
}

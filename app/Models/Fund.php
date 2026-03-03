<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Fund extends Model
{
    /** @use HasFactory<\Database\Factories\FundFactory> */
    use HasFactory;

    protected $table = 'funds';

    protected $fillable = [
        'office_id',
        'project_code_id',
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

    public function projectCode(): BelongsTo
    {
        return $this->belongsTo(ProjectCode::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function ppmp(): HasOne
    {
        return $this->hasOne(PPMP::class, 'office_id', 'office_id')
            ->whereColumn('ppmps.project_code_id', 'funds.project_code_id')
            ->whereColumn('ppmps.fiscal_year', 'funds.fiscal_year');
    }

    public function emanatings(): HasMany
    {
        return $this->hasMany(Emanating::class);
    }

    public function workProgram(): HasOneThrough
    {
        return $this->hasOneThrough(WorkProgram::class, Project::class, 'fund_id', 'project_id');
    }

    public function projectBrief(): HasOneThrough
    {
        return $this->hasOneThrough(ProjectBrief::class, Project::class, 'fund_id', 'project_id');
    }

    public function projectProposal(): HasOneThrough
    {
        return $this->hasOneThrough(ProjectProposal::class, Project::class, 'fund_id', 'project_id');
    }
}

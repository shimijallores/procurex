<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PPMP extends Model
{
    protected $table = 'ppmps';

    protected $fillable = [
        'office_id',
        'project_code_id',
        'fiscal_year',
        'is_addendum',
        'remarks',
        'xlsx_path',
        'budget_notices',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'is_addendum' => 'boolean',
        'budget_notices' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function projectCode(): BelongsTo
    {
        return $this->belongsTo(ProjectCode::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(PPMPCategory::class, 'ppmp_id');
    }

    public function fund(): HasOne
    {
        return $this->hasOne(Fund::class, 'office_id', 'office_id')
            ->whereColumn('funds.fiscal_year', 'ppmps.fiscal_year')
            ->where(function ($query): void {
                $query->whereColumn('funds.project_code_id', 'ppmps.project_code_id')
                    ->orWhere(function ($orQuery): void {
                        $orQuery->whereNull('funds.project_code_id')
                            ->whereNull('ppmps.project_code_id');
                    });
            });
    }
}

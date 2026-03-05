<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}

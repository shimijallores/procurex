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
        'project_id',
        'account_code',
        'project_code',
        'fiscal_year',
        'is_addendum',
        'remarks',
        'csv_path',
        'budget_notices',
        'is_approved',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'is_addendum' => 'boolean',
        'is_approved' => 'boolean',
        'budget_notices' => 'array',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(PPMPCategory::class, 'ppmp_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

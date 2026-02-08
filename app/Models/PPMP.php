<?php

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
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'is_addendum' => 'boolean',
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
        return $this->hasMany(PPMPCategory::class);
    }
}

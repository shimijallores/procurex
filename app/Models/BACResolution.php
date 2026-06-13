<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class BACResolution extends Model
{
    use Searchable;

    protected $table = 'bac_resolutions';

    protected $fillable = [
        'aoq_id',
        'resolution_no',
        'resolution_date',
        'meeting_date',
        'project_name',
        'winner_supplier_name',
        'winner_amount',
        'calculation_label',
        'justification',
        'signatory_chairperson',
        'signatory_member_one',
        'signatory_member_two',
        'signatory_member_three',
        'finalized_at',
    ];

    protected $casts = [
        'resolution_date' => 'date',
        'meeting_date' => 'date',
        'winner_amount' => 'decimal:2',
        'finalized_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function toSearchableArray(): array
    {
        return [
            'resolution_no' => $this->resolution_no,
            'project_name' => $this->project_name,
        ];
    }

    public function aoq(): BelongsTo
    {
        return $this->belongsTo(AOQ::class, 'aoq_id');
    }

    public function aoqs(): BelongsToMany
    {
        return $this->belongsToMany(AOQ::class, 'bac_resolution_aoq', 'bac_resolution_id', 'aoq_id')
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderBy('bac_resolution_aoq.sort_order');
    }

    public function noa(): HasOne
    {
        return $this->hasOne(NOA::class, 'bac_resolution_id');
    }

    public function noas(): HasMany
    {
        return $this->hasMany(NOA::class, 'bac_resolution_id');
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }
}

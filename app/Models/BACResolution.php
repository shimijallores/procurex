<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BACResolution extends Model
{
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

    public function aoq(): BelongsTo
    {
        return $this->belongsTo(AOQ::class, 'aoq_id');
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }
}

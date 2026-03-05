<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBriefItem extends Model
{
    protected $fillable = [
        'project_brief_id',
        'item_name',
        'quantity',
        'unit',
        'amount',
        'row_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'amount' => 'decimal:2',
        'row_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function projectBrief(): BelongsTo
    {
        return $this->belongsTo(ProjectBrief::class);
    }
}

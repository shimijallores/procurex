<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Emanating extends Model
{
    protected $table = 'emanatings';

    protected $fillable = [
        'fund_id',
        'ppmp_id',
        'project_id',
        'ppmp_category_id',
        'charged_to_code',
        'pr_no',
        'fiscal_year',
        'quarter',
        'month',
        'purpose',
        'is_addendum',
        'remarks',
        'reimbursement',
        'csv_path',
        'items_match_ppmp',
        'is_canvassed',
        'is_approved',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'status',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'is_addendum' => 'boolean',
        'reimbursement' => 'boolean',
        'items_match_ppmp' => 'boolean',
        'is_canvassed' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ppmp(): BelongsTo
    {
        return $this->belongsTo(PPMP::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function office(): BelongsTo
    {
        return $this->fund->office();
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function ppmpCategory(): BelongsTo
    {
        return $this->belongsTo(PPMPCategory::class);
    }

    public function emanatingItems(): HasMany
    {
        return $this->hasMany(EmanatingItem::class);
    }

    public function purchaseRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PurchaseRequest::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canvasses(): HasMany
    {
        return $this->hasMany(Canvas::class);
    }
}

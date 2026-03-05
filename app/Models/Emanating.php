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
        'emanating_no',
        'fund_id',
        'ppmp_id',
        'project_id',
        'account_id',
        'ppmp_category_id',
        'charged_to_code',
        'pr_no',
        'fiscal_year',
        'quarter',
        'month',
        'is_addendum',
        'remarks',
        'reimbursement',
        'xlsx_path',
        'requesting_officer_name',
        'requesting_officer_title',
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

    protected static function booted(): void
    {
        static::creating(function (Emanating $emanating): void {
            if (! empty($emanating->emanating_no)) {
                return;
            }

            $year = (int) ($emanating->fiscal_year ?: now()->year);
            $emanating->emanating_no = self::generateEmanatingNoForYear($year);
        });
    }

    private static function generateEmanatingNoForYear(int $year): string
    {
        $prefix = sprintf('%d-', $year);

        $numbers = self::query()
            ->where('fiscal_year', $year)
            ->where('emanating_no', 'like', $prefix . '%')
            ->lockForUpdate()
            ->pluck('emanating_no');

        $maxSequence = $numbers
            ->map(function (string $emanatingNo) use ($year): int {
                if (preg_match('/^' . preg_quote((string) $year, '/') . '-(\d+)$/', $emanatingNo, $matches) !== 1) {
                    return 0;
                }

                return (int) ($matches[1] ?? 0);
            })
            ->max() ?? 0;

        $nextSequence = $maxSequence + 1;

        return sprintf('%d-%s', $year, str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT));
    }

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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

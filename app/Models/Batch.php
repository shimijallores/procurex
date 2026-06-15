<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'batch_no',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function aoqs(): HasMany
    {
        return $this->hasMany(AOQ::class, 'batch_id');
    }

    public function generateResolutionNo(?AOQ $currentAoq = null): string
    {
        $batchSequence = '____';
        if ($this->batch_no) {
            $batchSequence = str_pad((string) ((int) substr((string) $this->batch_no, -4)), 4, '0', STR_PAD_LEFT);
        }

        $amountForBracket = 0;

        if ($currentAoq) {
            // Find the NOA for this AOQ to get the winner_amount
            $noa = $currentAoq->noa;
            if ($noa) {
                $amountForBracket = (float) $noa->winner_amount;
            } else {
                // Fallback to ABC if NOA doesn't exist yet
                $amountForBracket = (float) ($currentAoq->rfq?->abc_amount ?? 0);
            }
        } else {
            $amountForBracket = $this->aoqs()->with('rfq')->get()->sum(function ($aoq) {
                return (float) ($aoq->rfq?->abc_amount ?? 0);
            });
        }

        $bracket = $amountForBracket <= 200000 ? 'B200K' : 'A200K';

        return sprintf('BAC - SVP - %s - %s', $bracket, $batchSequence);
    }
}

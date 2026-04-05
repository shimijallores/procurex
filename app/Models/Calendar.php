<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'type',
        'name',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected function isWorkingDay(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): bool {
            if (array_key_exists('is_working_day', $attributes)) {
                return (bool) $attributes['is_working_day'];
            }

            return ($attributes['type'] ?? null) === 'special_workday';
        });
    }
}

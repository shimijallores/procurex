<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\CalendarService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableDate implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $calendarService = app(CalendarService::class);

        if (! $calendarService->isDateAvailable($value)) {
            $entry = $calendarService->getEntry($value);

            $message = 'The selected date is not available.';

            if ($entry) {
                $typeName = match ($entry->type) {
                    'holiday' => 'a holiday',
                    'suspended' => 'suspended',
                    'blackout' => 'a blackout date',
                    'special_workday' => 'a special workday',
                    default => 'unavailable',
                };

                $name = $entry->name ? sprintf(' (%s)', $entry->name) : '';
                $message = sprintf('The selected date is %s%s and not available for this operation.', $typeName, $name);
            }

            $fail($message);
        }
    }
}

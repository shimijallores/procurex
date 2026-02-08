<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Calendar;
use Carbon\Carbon;

/**
 * Calendar Service for managing government working days.
 * 
 * Note: Weekends (Saturday and Sunday) are automatically considered non-working days
 * for government operations, even if not explicitly marked in the calendar.
 */
class CalendarService
{
    /**
     * Check if a specific date is available (working day).
     * Weekends are automatically considered unavailable.
     */
    public function isDateAvailable(string|Carbon $date): bool
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;
        $carbonDate = Carbon::parse($dateString);

        // Weekends are always non-working days for government
        if ($carbonDate->isWeekend()) {
            return false;
        }

        $calendarEntry = Calendar::where('date', $dateString)->first();

        // If no entry exists, assume it's a working day (except weekends already handled above)
        return !$calendarEntry;
    }

    /**
     * Get calendar entry for a specific date.
     */
    public function getEntry(string|Carbon $date): ?Calendar
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;

        return Calendar::where('date', $dateString)->first();
    }

    /**
     * Get all unavailable dates within a date range.
     * Includes weekends and calendar entries marked as unavailable.
     *
     * @return array<string>
     */
    public function getUnavailableDates(string|Carbon $startDate, string|Carbon $endDate): array
    {
        $start = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate->copy() : Carbon::parse($endDate);

        $unavailableDates = [];
        $currentDate = $start->copy();

        // Iterate through all dates in range
        while ($currentDate->lte($end)) {
            if (!$this->isDateAvailable($currentDate)) {
                $unavailableDates[] = $currentDate->toDateString();
            }
            $currentDate->addDay();
        }

        return $unavailableDates;
    }

    /**
     * Get the next available working day after a specific date.
     * Skips weekends automatically.
     */
    public function getNextAvailableDate(string|Carbon $date, int $maxDaysToCheck = 30): ?Carbon
    {
        $currentDate = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        for ($i = 0; $i < $maxDaysToCheck; $i++) {
            $currentDate->addDay();

            if ($this->isDateAvailable($currentDate)) {
                return $currentDate;
            }
        }

        return null;
    }

    /**
     * Calculate working days between two dates.
     * Excludes weekends automatically.
     */
    public function countWorkingDays(string|Carbon $startDate, string|Carbon $endDate): int
    {
        $start = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate->copy() : Carbon::parse($endDate);

        $workingDays = 0;
        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            if ($this->isDateAvailable($currentDate)) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Add working days to a date.
     * Automatically skips weekends.
     */
    public function addWorkingDays(string|Carbon $date, int $workingDays): Carbon
    {
        $currentDate = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $daysAdded = 0;

        while ($daysAdded < $workingDays) {
            $currentDate->addDay();

            if ($this->isDateAvailable($currentDate)) {
                $daysAdded++;
            }
        }

        return $currentDate;
    }
}

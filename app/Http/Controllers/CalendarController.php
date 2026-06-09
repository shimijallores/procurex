<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Http\Resources\CalendarResource;
use App\Models\Calendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Calendar::query();

        // Filter by month/year if provided
        if ($request->month && $request->year) {
            $query->whereYear('date', $request->year)
                ->whereMonth('date', $request->month);
        } elseif ($request->year) {
            $query->whereYear('date', $request->year);
        }

        // Search by name or type
        if ($request->search) {
            $query->where(function ($q) use ($request): void {
                $q->where('name', 'like', sprintf('%%%s%%', $request->search))
                    ->orWhere('type', 'like', sprintf('%%%s%%', $request->search));
            });
        }

        $calendars = $query->orderBy('date', 'asc')
            ->get();

        return Inertia::render('Calendars/Index', [
            'calendars' => CalendarResource::collection($calendars)->resolve(),
            'filters' => [
                'search' => $request->search,
                'month' => $request->month,
                'year' => $request->year,
            ],
        ]);
    }

    public function store(StoreCalendarRequest $storeCalendarRequest): RedirectResponse
    {
        $validated = $storeCalendarRequest->validated();

        Calendar::create($validated);

        return redirect()->route('calendars.index')
            ->with('success', 'Calendar entry created successfully.');
    }

    public function update(UpdateCalendarRequest $updateCalendarRequest, Calendar $calendar): RedirectResponse
    {
        $validated = $updateCalendarRequest->validated();

        $calendar->update($validated);

        return redirect()->route('calendars.index')
            ->with('success', 'Calendar entry updated successfully.');
    }

    public function destroy(Calendar $calendar): RedirectResponse
    {
        $calendar->delete();

        return redirect()->route('calendars.index')
            ->with('success', 'Calendar entry deleted successfully.');
    }

    /**
     * Check if a specific date is available (working day)
     */
    public function checkDate(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = \Carbon\Carbon::parse($request->date);
        $calendarEntry = Calendar::whereDate('date', (string) $request->date)->first();

        // Check if it's a weekend (Saturday = 6, Sunday = 0)
        $isWeekend = $date->dayOfWeek == 0 || $date->dayOfWeek == 6;

        if (! $calendarEntry) {
            // If no entry exists, check if it's a weekend
            if ($isWeekend) {
                $dayName = $date->format('l');

                return response()->json([
                    'is_available' => false,
                    'is_working_day' => false,
                    'type' => 'weekend',
                    'name' => $dayName,
                    'message' => sprintf('%s is not a working day', $dayName),
                ]);
            }

            // Otherwise assume it's a working day
            return response()->json([
                'is_available' => true,
                'is_working_day' => true,
                'message' => 'Date is available.',
            ]);
        }

        return response()->json([
            'is_available' => $calendarEntry->is_working_day,
            'is_working_day' => $calendarEntry->is_working_day,
            'type' => $calendarEntry->type,
            'name' => $calendarEntry->name,
            'remarks' => $calendarEntry->remarks,
            'message' => $calendarEntry->is_working_day
                ? 'Date is available.'
                : sprintf('%s: %s', ucfirst($calendarEntry->type), $calendarEntry->name ?? 'Not available'),
        ]);
    }
}

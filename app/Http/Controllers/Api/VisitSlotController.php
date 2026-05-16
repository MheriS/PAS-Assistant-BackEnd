<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\VisitSlot;
use App\Models\RecurringVisitSlot;
use Carbon\Carbon;

class VisitSlotController extends Controller
{
    public function index()
    {
        return response()->json(VisitSlot::orderBy('date', 'asc')->orderBy('start_time', 'asc')->get());
    }

    public function getAvailableDates()
    {
        $startDate = now();
        $endDate = now()->addDays(30);
        
        $availableDates = [];
        
        // 1. Get dates from specific slots
        $specificSlots = VisitSlot::where('date', '>=', $startDate->toDateString())
            ->where('date', '<=', $endDate->toDateString())
            ->get()
            ->groupBy(function($slot) {
                return $slot->date->toDateString();
            });

        // 2. Get recurring rules
        $recurringRules = RecurringVisitSlot::where('is_active', true)->get()->groupBy('day_of_week');

        // 3. Iterate through next 30 days
        for ($i = 0; $i < 30; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateString = $currentDate->toDateString();
            $dayOfWeek = $currentDate->dayOfWeek; // 0 (Sun) - 6 (Sat)

            // If a specific slot exists for this date
            if (isset($specificSlots[$dateString])) {
                $hasAvailable = $specificSlots[$dateString]->contains('is_available', true);
                if ($hasAvailable) {
                    $availableDates[] = $dateString;
                }
            } 
            // If no specific slot, check recurring rules
            elseif (isset($recurringRules[$dayOfWeek])) {
                $availableDates[] = $dateString;
            }
        }

        return response()->json(array_unique($availableDates));
    }

    public function getAvailableTimes($date)
    {
        $parsedDate = Carbon::parse($date);
        $dateString = $parsedDate->toDateString();
        $dayOfWeek = $parsedDate->dayOfWeek;

        // Check for specific slots first
        $specificSlots = VisitSlot::where('date', $dateString)
            ->where('is_available', true)
            ->orderBy('start_time', 'asc')
            ->get(['id', 'session_name', 'start_time', 'end_time', 'max_visitors']);

        if ($specificSlots->isNotEmpty()) {
            return response()->json($specificSlots);
        }

        // If no specific slots, return recurring slots as virtual instances
        $recurringSlots = RecurringVisitSlot::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function($rule) use ($dateString) {
                return [
                    'id' => "rec-{$rule->id}-{$dateString}", // Virtual ID
                    'session_name' => $rule->session_name,
                    'start_time' => $rule->start_time,
                    'end_time' => $rule->end_time,
                    'max_visitors' => $rule->max_visitors,
                    'is_recurring' => true
                ];
            });

        return response()->json($recurringSlots);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'session_name' => 'nullable|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'max_visitors' => 'nullable|integer',
        ]);

        $slot = VisitSlot::updateOrCreate(
            [
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time']
            ],
            [
                'session_name' => $validated['session_name'],
                'max_visitors' => $validated['max_visitors'] ?? 10,
                'is_available' => true
            ]
        );

        return response()->json($slot, 201);
    }

    public function destroy($id)
    {
        VisitSlot::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function toggleAvailability($id)
    {
        $slot = VisitSlot::findOrFail($id);
        $slot->is_available = !$slot->is_available;
        $slot->save();

        return response()->json($slot);
    }
}

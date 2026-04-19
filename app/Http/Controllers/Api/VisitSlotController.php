<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\VisitSlot;
use Carbon\Carbon;

class VisitSlotController extends Controller
{
    public function index()
    {
        return response()->json(VisitSlot::orderBy('date', 'asc')->orderBy('start_time', 'asc')->get());
    }

    public function getAvailableDates()
    {
        $dates = VisitSlot::where('date', '>=', now()->toDateString())
            ->where('is_available', true)
            ->distinct()
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->toDateString();
            });

        return response()->json($dates);
    }

    public function getAvailableTimes($date)
    {
        $parsedDate = \Carbon\Carbon::parse($date)->toDateString();
        $slots = VisitSlot::where('date', $parsedDate)
            ->where('is_available', true)
            ->orderBy('start_time', 'asc')
            ->get(['id', 'session_name', 'start_time', 'end_time', 'max_visitors']);

        return response()->json($slots);
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

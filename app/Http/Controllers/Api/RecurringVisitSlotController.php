<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RecurringVisitSlot;

class RecurringVisitSlotController extends Controller
{
    public function index()
    {
        return response()->json(RecurringVisitSlot::orderBy('day_of_week', 'asc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'session_name' => 'nullable|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'max_visitors' => 'nullable|integer',
        ]);

        $slot = RecurringVisitSlot::create($validated);
        return response()->json($slot, 201);
    }

    public function update(Request $request, $id)
    {
        $slot = RecurringVisitSlot::findOrFail($id);
        $validated = $request->validate([
            'day_of_week' => 'integer|min:0|max:6',
            'session_name' => 'nullable|string',
            'start_time' => 'string',
            'end_time' => 'string',
            'max_visitors' => 'integer',
            'is_active' => 'boolean',
        ]);

        $slot->update($validated);
        return response()->json($slot);
    }

    public function destroy($id)
    {
        RecurringVisitSlot::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}

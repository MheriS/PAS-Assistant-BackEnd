<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function show($nik)
    {
        $visitor = Visitor::find($nik);
        if (!$visitor) {
            return response()->json(['message' => 'Visitor not found'], 404);
        }
        return response()->json($visitor);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|string|size:16',
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'relationship' => 'required|string',
        ]);

        $visitor = Visitor::updateOrCreate(
            ['nik' => $validated['nik']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'relationship' => $validated['relationship'],
                'last_visit' => now(),
            ]
        );

        $visitor->increment('visit_count');

        return response()->json($visitor);
    }
}

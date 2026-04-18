<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats()
    {
        $stats = [
            'total' => Registration::count(),
            'pending' => Registration::where('status', 'pending')->count(),
            'approved' => Registration::where('status', 'approved')->count(),
            'rejected' => Registration::where('status', 'rejected')->count(),
            'today' => Registration::whereDate('visit_date', today())->count(),
        ];

        return response()->json($stats);
    }
}

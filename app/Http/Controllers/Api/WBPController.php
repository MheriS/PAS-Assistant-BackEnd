<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WBP;
use Illuminate\Http\Request;

class WBPController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->query('q');
        
        if (!$query) {
            return response()->json([]);
        }

        $wbps = WBP::where('nama', 'ILIKE', "%{$query}%")
            ->limit(10)
            ->get(['nama', 'no_regs', 'blok', 'kamar']);

        return response()->json($wbps);
    }
}

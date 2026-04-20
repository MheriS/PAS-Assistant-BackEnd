<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WBP;
use Illuminate\Http\Request;

class WBPController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->query('q');
        $wbps = WBP::when($query, function ($q) use ($query) {
                $q->where('nama', 'ILIKE', "%{$query}%")
                  ->orWhere('no_regs', 'ILIKE', "%{$query}%");
            })
            ->orderBy('nama')
            ->paginate(15);

        return response()->json($wbps);
    }

    public function generateNoRegs()
    {
        $year = date('Y');
        $lastWbp = WBP::where('no_regs', 'LIKE', "WBP-{$year}-%")
            ->orderBy('no_regs', 'desc')
            ->first();

        if ($lastWbp) {
            $lastNo = (int) substr($lastWbp->no_regs, -5);
            $newNo = str_pad($lastNo + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNo = '00001';
        }

        return response()->json(['no_regs' => "WBP-{$year}-{$newNo}"]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'no_regs' => 'required|string|unique:wbps',
            'jenis_kelamin' => 'required|string',
            'perkara' => 'required|string',
            'blok' => 'required|string',
            'kamar' => 'required|string',
            'foto' => 'nullable|string',
        ]);

        $wbp = WBP::create($validated);

        return response()->json($wbp, 201);
    }

    public function update(Request $request, $id)
    {
        $wbp = WBP::findOrFail($id);
        
        $validated = $request->validate([
            'nama' => 'sometimes|required|string',
            'no_regs' => 'sometimes|required|string|unique:wbps,no_regs,' . $id,
            'jenis_kelamin' => 'sometimes|required|string',
            'perkara' => 'sometimes|required|string',
            'blok' => 'sometimes|required|string',
            'kamar' => 'sometimes|required|string',
            'foto' => 'nullable|string',
            'status' => 'sometimes|required|string',
        ]);

        $wbp->update($validated);

        return response()->json($wbp);
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        
        if (!$query) {
            return response()->json([]);
        }

        $wbps = WBP::where('nama', 'ILIKE', "%{$query}%")
            ->where('status', 'aktif')
            ->limit(10)
            ->get(['id', 'nama', 'no_regs', 'blok', 'kamar', 'foto']);

        return response()->json($wbps);
    }

    public function recordMovement(Request $request, $id)
    {
        $wbp = WBP::findOrFail($id);
        
        $validated = $request->validate([
            'type' => 'required|in:masuk,keluar',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        \App\Models\WBPMovement::create([
            'wbp_id' => $wbp->id,
            'type' => $validated['type'],
            'tanggal' => $validated['tanggal'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        // Update status based on movement
        $wbp->status = $validated['type'] === 'masuk' ? 'aktif' : 'keluar';
        $wbp->save();

        return response()->json([
            'message' => 'Movement recorded successfully',
            'wbp' => $wbp
        ]);
    }
}

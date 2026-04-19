<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function index()
    {
        $registrations = Registration::orderBy('visit_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate queue numbers per day
        $dateCounts = [];
        $registrations->map(function($reg) use (&$dateCounts) {
            $date = $reg->visit_date;
            if (!isset($dateCounts[$date])) {
                $dateCounts[$date] = 0;
            }
            $dateCounts[$date]++;
            $reg->queue_number = $dateCounts[$date];

            // Load relationship or fallback to name matching
            if (!$reg->inmate_number) {
                $wbp = \App\Models\WBP::where('nama', 'LIKE', '%' . $reg->inmate_name . '%')->first();
                if ($wbp) {
                    $reg->setRelation('wbp', $wbp);
                }
            } else {
                $reg->load('wbp');
            }
            return $reg;
        });

        // Return sorted by latest created_at for the admin view
        return response()->json($registrations->sortByDesc('created_at')->values());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:registrations,id',
            'nik' => 'required|string|size:16',
            'visitorName' => 'required|string',
            'visitorPhone' => 'required|string',
            'visitorAddress' => 'required|string',
            'inmateName' => 'required|string',
            'inmateNumber' => 'nullable|string',
            'relationship' => 'required|string',
            'visitDate' => 'required|date',
            'visitTime' => 'nullable|string',
            'roomBlock' => 'nullable|string',
            'pengikutLaki' => 'nullable|integer',
            'pengikutPerempuan' => 'nullable|integer',
            'pengikutAnak' => 'nullable|integer',
            'jumlahPengikut' => 'nullable|integer',
        ]);

        $registration = Registration::create([
            'id' => $validated['id'],
            'nik' => $validated['nik'],
            'visitor_name' => $validated['visitorName'],
            'visitor_phone' => $validated['visitorPhone'],
            'visitor_address' => $validated['visitorAddress'],
            'inmate_name' => $validated['inmateName'],
            'inmate_number' => $validated['inmateNumber'] ?? '',
            'relationship' => $validated['relationship'],
            'visit_date' => $validated['visitDate'],
            'visit_time' => $validated['visitTime'] ?? '',
            'room_block' => $validated['roomBlock'] ?? null,
            'pengikut_laki' => $validated['pengikutLaki'] ?? 0,
            'pengikut_perempuan' => $validated['pengikutPerempuan'] ?? 0,
            'pengikut_anak' => $validated['pengikutAnak'] ?? 0,
            'jumlah_pengikut' => $validated['jumlahPengikut'] ?? 0,
            'status' => 'pending',
        ]);

        return response()->json($registration, 201);
    }

    public function update(Request $request, $id)
    {
        $registration = Registration::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $registration->update(['status' => $validated['status']]);

        return response()->json($registration);
    }

    public function checkStatus($nik)
    {
        $registrations = Registration::where('nik', $nik)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($registrations);
    }

    public function getSchedule($date)
    {
        $registrations = Registration::where('visit_date', $date)
            ->orderBy('visit_time', 'asc')
            ->get();

        return response()->json($registrations);
    }

    public function getUpcomingSchedule()
    {
        $registrations = Registration::whereDate('visit_date', '>=', today())
            ->orderBy('visit_date', 'asc')
            ->orderBy('visit_time', 'asc')
            ->get();

        return response()->json($registrations);
    }
}

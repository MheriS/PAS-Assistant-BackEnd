<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicineDelivery;
use App\Models\MedicineRule;
use App\Models\Registration;
use App\Models\WBP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicineDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = MedicineDelivery::with(['registration', 'wbp']);

        if ($request->has('registration_id')) {
            $query->where('registration_id', $request->registration_id);
        }

        if ($request->has('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        return response()->json($query->latest()->get());
    }

    public function getRules()
    {
        return response()->json(MedicineRule::all());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'registration_id' => 'required|exists:registrations,id',
            'medicine_name' => 'required|string',
            'quantity' => 'required|string',
            'dosage' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $registration = Registration::find($request->registration_id);
        $wbp = null;

        if ($registration->inmate_number) {
            $wbp = WBP::where('no_regs', $registration->inmate_number)->first();
        }

        if (!$wbp) {
            $wbp = WBP::where('nama', 'LIKE', '%' . $registration->inmate_name . '%')->first();
        }

        if (!$wbp) {
            return response()->json(['message' => 'WBP ' . $registration->inmate_name . ' tidak ditemukan di database. Pastikan data pendaftaran benar.'], 404);
        }

        $delivery = MedicineDelivery::create([
            'registration_id' => $request->registration_id,
            'wbp_id' => $wbp->id,
            'medicine_name' => $request->medicine_name,
            'quantity' => $request->quantity,
            'dosage' => $request->dosage,
            'notes' => $request->notes,
            'approval_status' => 'waiting',
            'delivery_status' => 'pending',
            'officer_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Medicine delivery recorded successfully',
            'data' => $delivery->load(['registration', 'wbp'])
        ], 201);
    }

    public function updateApproval(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approval_status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:approval_status,rejected|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $delivery = MedicineDelivery::findOrFail($id);
        $delivery->update([
            'approval_status' => $request->approval_status,
            'rejection_reason' => $request->rejection_reason,
            'medical_officer_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Medicine approval status updated',
            'data' => $delivery
        ]);
    }

    public function updateDelivery(Request $request, $id)
    {
        $delivery = MedicineDelivery::findOrFail($id);
        
        if ($delivery->approval_status !== 'approved') {
            return response()->json(['message' => 'Cannot deliver medicine that is not approved'], 400);
        }

        $delivery->update([
            'delivery_status' => 'delivered',
            'delivery_officer_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Medicine delivered to inmate',
            'data' => $delivery
        ]);
    }
}

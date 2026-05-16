<?php

namespace App\Http\Controllers;

use App\Models\MoneyDeposit;
use Illuminate\Http\Request;

class MoneyDepositController extends Controller
{
    public function index(Request $request)
    {
        $query = MoneyDeposit::with(['registration.wbp']);

        if ($request->has('registration_id')) {
            $query->where('registration_id', $request->registration_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->latest()->get();

        // Fallback for WBP lookup if relation is null
        $items->each(function($item) {
            if ($item->registration && !$item->registration->wbp) {
                $wbp = \App\Models\WBP::where('nama', 'LIKE', '%' . $item->registration->inmate_name . '%')->first();
                if ($wbp) {
                    $item->registration->setRelation('wbp', $wbp);
                }
            }
        });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'registration_id' => 'required|exists:registrations,id',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $deposit = MoneyDeposit::create($validated);

        return response()->json($deposit, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $deposit = MoneyDeposit::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:pending,delivered',
        ]);

        $deposit->update($validated);

        return response()->json($deposit);
    }

    public function destroy($id)
    {
        $deposit = MoneyDeposit::findOrFail($id);
        $deposit->delete();

        return response()->json(['message' => 'Deposit deleted successfully']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cheque;
use App\Models\Bank;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChequeController extends Controller
{
    /**
     * Display a listing of cheques (usually handled by BankController@index).
     */
    public function index()
    {
        return Cheque::with('bank')->latest('due_date')->get();
    }

    /**
     * Store a new incoming cheque (Starts as Pending).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cheque_number' => 'required|string',
            'party_name' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
        ]);

        $validated['status'] = 'pending';

        Cheque::create($validated);

        return redirect()->back()->with('success', 'Cheque registered successfully.');
    }

    /**
     * Clear/Receive a cheque into a specific bank.
     */
    public function receive(Request $request, Cheque $cheque)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
        ]);

        if ($cheque->status !== 'pending') {
            return redirect()->back()->withErrors(['cheque' => 'This cheque has already been cleared or cancelled.']);
        }

        return DB::transaction(function () use ($validated, $cheque) {
            $cheque->update([
                'status' => 'received',
                'bank_id' => $validated['bank_id']
            ]);

            $bank = Bank::lockForUpdate()->find($validated['bank_id']);
            $bank->increment('balance', $cheque->amount);

            return redirect()->back()->with('success', 'Cheque cleared into ' . $bank->name);
        });
    }

    /**
     * Get upcoming cheques (5-day alert logic).
     */
    public function upcoming()
    {
        $today = Carbon::today();
        $fiveDaysFromNow = Carbon::today()->addDays(5);

        return Cheque::where('status', 'pending')
            ->whereBetween('due_date', [$today, $fiveDaysFromNow])
            ->get();
    }

    /**
     * Remove a cheque record.
     */
    public function destroy(Cheque $cheque)
    {
        // If received, consider if we should revert bank balance? 
        // For now, simple delete.
        $cheque->delete();
        return redirect()->back()->with('success', 'Cheque record removed.');
    }
}

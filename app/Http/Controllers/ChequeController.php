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
        return Cheque::with('bank')->latest('due_date')->latest('id')->get();
    }

    /**
     * Store a new cheque.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cheque_number' => 'required|string',
            'sender_name' => 'required|string',
            'receiver_name' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'type' => 'required|in:incoming,outgoing',
            'bank_id' => 'required_if:type,outgoing|nullable|exists:banks,id',
        ]);

        $validated['status'] = 'pending';
        $validated['party_name'] = $validated['type'] === 'incoming' ? $validated['sender_name'] : $validated['receiver_name'];

        Cheque::create($validated);

        return redirect()->back()->with('success', 'Cheque registered successfully.');
    }

    /**
     * Clear/Receive an incoming cheque into a bank.
     */
    public function receive(Request $request, Cheque $cheque)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
        ]);

        if ($cheque->status !== 'pending' || $cheque->type !== 'incoming') {
            return redirect()->back()->withErrors(['cheque' => 'Invalid operation for this cheque type/status.']);
        }

        return DB::transaction(function () use ($validated, $cheque) {
            $cheque->update([
                'status' => 'received',
                'bank_id' => $validated['bank_id']
            ]);

            $bank = Bank::lockForUpdate()->find($validated['bank_id']);
            $bank->increment('balance', $cheque->amount);

            \App\Models\BankTransaction::create([
                'bank_id' => $validated['bank_id'],
                'amount' => $cheque->amount,
                'type' => 'deposit',
                'reference_type' => 'Cheque',
                'reference_id' => $cheque->id,
                'description' => "Cheque Received: #{$cheque->cheque_number} from {$cheque->sender_name}",
                'date' => now()->toDateString(),
            ]);

            return redirect()->back()->with('success', 'Cheque received into ' . $bank->name);
        });
    }

    /**
     * Clear/Pay an outgoing cheque from its assigned bank.
     */
    public function clear(Cheque $cheque)
    {
        if ($cheque->status !== 'pending' || $cheque->type !== 'outgoing' || !$cheque->bank_id) {
            return redirect()->back()->withErrors(['cheque' => 'Invalid operation for this cheque.']);
        }

        return DB::transaction(function () use ($cheque) {
            $cheque->update(['status' => 'received']); // Using 'received' as 'cleared' for simplicity with current schema

            $bank = Bank::lockForUpdate()->find($cheque->bank_id);
            $bank->decrement('balance', $cheque->amount);

            \App\Models\BankTransaction::create([
                'bank_id' => $cheque->bank_id,
                'amount' => $cheque->amount,
                'type' => 'withdrawal',
                'reference_type' => 'Cheque',
                'reference_id' => $cheque->id,
                'description' => "Cheque Paid: #{$cheque->cheque_number} to {$cheque->receiver_name}",
                'date' => now()->toDateString(),
            ]);

            return redirect()->back()->with('success', 'Cheque cleared from ' . $bank->name);
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
        $cheque->delete();
        return redirect()->back()->with('success', 'Cheque record removed.');
    }
}

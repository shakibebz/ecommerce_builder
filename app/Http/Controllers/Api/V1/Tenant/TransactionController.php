<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $account = Auth::user()->account;
        if (!$account) {
            $account = BankAccount::create([
                'tenant_id' => Auth::user()->id,
                'balance' => 0.00,
            ]);
        }

        $account->balance += $request->amount;
        $account->save();

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => $request->amount,
        ]);

        return new TransactionResource($transaction);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $tenant = Auth::user()->account;
        if (!$tenant) {
            Log::error('Unauthorized: No authenticated tenant');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        \Log::info('Tenant found', ['tenant_id' => $tenant->id]);

        if ($tenant->balance < $request->amount) {
            return response()->json(['error' => 'Insufficient balance'], 422);
        }

        $tenant->balance -= $request->amount;
        \Log::info('balance', ['balance' => $tenant->balance]);
        $tenant->save();

        $transaction = Transaction::create([
            'account_id' => $tenant->id,
            'type' => 'withdrawal',
            'amount' => $request->amount,
        ]);

        return new TransactionResource($transaction);
    }

    public function index()
    {
        $account = Auth::user()->account;
        \Log::info('Account:', ['account' => $account]);
        if (!$account) {
            return response()->json(['data' => []], 200);
        }

        $transactions = $account->transactions()->latest()->get();
        \Log::info('Transactions:', ['transactions' => $transactions]);
        return TransactionResource::collection($transactions);
    }
}

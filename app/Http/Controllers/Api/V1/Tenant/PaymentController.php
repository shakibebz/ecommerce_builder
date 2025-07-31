<?php
namespace App\Http\Controllers\Api\V1\Tenant;

// app/Http/Controllers/PaymentController.php
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Services\Payment\PaymentFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|integer|min:1000',
            'gateway' => 'required|in:payping,zarinpal'
        ]);

        $account = BankAccount::findOrFail($request->account_id);
        $gateway = $request->gateway;

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'amount' => $request->amount,
            'type' => 'deposit',
            'gateway' => $gateway,
            'status' => 'pending',
        ]);

        $callbackUrl = route('payment.verify', ['gateway' => $gateway, 'transaction' => $transaction->id]);
        $payUrl = PaymentFactory::make($gateway)->initiate($account, $request->amount, $callbackUrl);

        return redirect($payUrl);
    }


    public function verify(Request $request, $gateway, $transactionId = null)
    {
        $refId = $request->query('refid'); // From PayPing return URL
        $gatewayService = PaymentFactory::make($gateway);

        // Find the transaction using ref_id (which we previously set as clientRefId)
        $transaction = Transaction::where('ref_id', $refId)
            ->where('gateway', $gateway)
            ->where('status', 'pending')
            ->firstOrFail();

        $result = $gatewayService->verify($request);

        if ($result['verified']) {
            $transaction->update([
                'status' => 'completed',
                'description' => $result['trace_id'] ?? null,
            ]);

            $transaction->account->increment('amount', $transaction->amount);

            return view('payment.success', compact('transaction'));
        }

        $transaction->update(['status' => 'failed']);
        return view('payment.error');
    }



}

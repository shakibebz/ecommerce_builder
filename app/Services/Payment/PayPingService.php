<?php
namespace App\Services\Payment;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPingService implements PaymentServiceInterface
{
    public function initiate(BankAccount $account, int $amount, string $callbackUrl): string
    {

        $clientRefId = Str::uuid();
        $tenant = $account->tenant;

        $payload = [
            'amount' => $amount,
            'payerIdentity' => $tenant->email ?? $tenant->id,
            'payerName' => $tenant->name ?? 'User',
            'description' => 'Account Top-up',
            'returnUrl' => $callbackUrl,
            'clientRefId' => $clientRefId,
            'nationalCode' => $tenant->national_code ?? null,
        ];

        $response = Http::withToken(config('services.payping.token'))
            ->post('https://api.payping.ir/v3/pay', $payload);

        throw_if(!$response->successful(), \Exception::class, 'PayPing initiation failed: ' . $response->body());

        //Store `clientRefId` in transaction for verification later
        Transaction::where('account_id', $account->id)
            ->where('status', 'pending')
            ->latest()
            ->first()
            ->update(['ref_id' => $clientRefId]);


       return $response->json()['returnUrl'];
    }

    public function verify(Request $request): array
    {
        $refId = $request->query('refid');
        $transaction = Transaction::where('ref_id', $refId)->firstOrFail();

        $response = Http::withToken(config('services.payping.token'))
            ->post('https://api.payping.ir/v3/pay/verify', [
                'refId' => $refId,
                'amount' => $transaction->amount
            ]);

        $data=$response->json();
        $paymentCode = $data['paymentCode'];

        if ($response->successful()) {
          /*  return [
                'verified' => true,
                'trace_id' => $response->json()['code'] ?? null,
            ];*/
            return redirect("https://api.payping.ir/v3/pay/start/{$paymentCode}");
        }

        return ['verified' => false];
    }

    public function cancelPayment(string $paymentCode): bool
    {
        $response = Http::withToken(config('services.payping.token'))
            ->withHeaders(['Accept' => 'application/json'])
            ->delete('https://api.payping.ir/v3/pay', [
                'paymentCode' => $paymentCode
            ]);

        return $response->status() === 204;
    }

}

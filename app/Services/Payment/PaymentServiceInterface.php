<?php
namespace App\Services\Payment;

use App\Models\BankAccount;
use Illuminate\Http\Request;

interface PaymentServiceInterface
{
    public function initiate(BankAccount $account, int $amount, string $callbackUrl): string;

    public function verify(Request $request): array;
}

<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{

    public function show()
    {
        $tenant = Auth::user();
        if (!$tenant) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $account = $tenant->account;
        if (!$account) {
            $account = $tenant->account()->create([
                'tenant_id' => $tenant->id,
                'balance' => 0.00,
            ]);
        }
        return new AccountResource($account);
    }
}

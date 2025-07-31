<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Stores;
use App\Models\Tenant;
use App\Services\Tenant\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TenantAuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tenant,email',
            'password' => 'required',
        ]);

        $tenant = Tenant::where('email', $request->email)->first();

        if (!$tenant || !Hash::check($request->password, $tenant->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $tenant->createToken('TenantToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'tenant' => $tenant
        ]);
    }
  public function register(Request $request)
    {

        $validated = $request->validate([
            // Tenant fields
            'tname'    => 'required|string|max:255',
            'email'    => 'required|email|unique:tenant,email',
            'status'   => ['nullable', Rule::in(['active', 'pending', 'suspended'])],
            'password' => 'required|string|min:8|confirmed',

        ]);

        $password= $validated['password'];
        $tenant = Tenant::create([
            'tname'     => $validated['tname'],
            'email'    => $validated['email'],
            'status'   => $validated['status'] ?? 'pending',
            'password' => Hash::make($validated['password']),
        ]);

        //create store

        $storeResult = StoreService::createStore($request, $tenant->id);

     //   $token = $tenant->createToken('TenantToken')->plainTextToken;

        return response()->json([
            'message' => 'Tenant and store registered successfully',
          //  'token'   => $token,
            'tenant'  => $tenant,
      //      'store'    => $storeResult['store'],
            'store_code' => $storeResult['code'],
            'password' => $password
        ], 201);
    }


}

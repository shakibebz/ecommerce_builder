<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Stores;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Exception;

class TenantController extends Controller
{


    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            // Tenant fields
            'tname' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:tenant,email,' . $tenant->id,
            'status' => ['nullable', Rule::in(['active', 'pending', 'suspended'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'national_code' => 'nullable|string|max:255',
         //   'name' => 'nullable|string|max:255',


            // Store fields
            'name' => 'nullable|string|max:255',
            'owner_id' => 'nullable|integer|exists:tenant,id',
            'domain' => 'nullable|string|max:255', // assuming you're not enforcing full URL validation here
            'store_category' => 'nullable|string|max:255',
            'registration_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'code' => 'nullable|string|max:10',
        ]);

        $tenantUpdated = false;
        $storeUpdated = false;

        // Update tenant fields (only the ones present)
        $tenantFields = Arr::only($validated, [
            'tname', 'email', 'status', 'phone', 'address',
            'national_code', 'name', 'owner_id'
        ]);

        if (!empty($tenantFields)) {
            $tenant->update($tenantFields);
            $tenantUpdated = true;
        }

        // Update store (if tenant has one)
        if ($tenant->stores()->exists()) {
            $store = $tenant->stores()->first(); // assuming one-to-one

            $storeFields = Arr::only($validated, [
                'domain', 'store_category', 'registration_date',
                'expiration_date', 'is_active', 'code'
            ]);

            if (!empty($storeFields)) {
                $store->update($storeFields);
                $storeUpdated = true;
            }
        }

        if (!$tenantUpdated && !$storeUpdated) {
            return response()->json([
                'message' => 'No fields were provided for update.'
            ], 400);
        }

        return response()->json([
            'message' => 'Tenant and/or store updated successfully',
            'tenant' => $tenant->fresh()
        ]);
    }




    public function index()
    {
        try {
            $tenants = Tenant::leftJoin('stores', 'tenant.id', '=', 'stores.owner_id')
                ->select('tenant.*', 'stores.*')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'tenants' => $tenants
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve tenants.',
                'error' => app()->environment('production') ? null : $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $tenant = Tenant::join('stores', 'tenant.id', '=', 'stores.owner_id')
                ->where('tenant.id', $id)
                ->select('tenant.*', 'stores.*')
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'tenant' => $tenant
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve tenant.',
                'error' => app()->environment('production') ? null : $e->getMessage()
            ], 404);
        }
    }


    public function getTenantByDomain(Request $request)
    {
     //   \Log::info('hii: ');
        try {
            $request->validate([
                'domain' => 'required|string|max:255',
            ]);
            /*  $store = Stores::with('tenant')
                  ->where('domain', $request->domain)
                  ->first();

              if (!$store || !$store->tenant) {
                  return response()->json([
                      'status' => 'error',
                      'message' => 'Tenant not found for the given domain.'
                  ], 404);
              }

              return response()->json([
                  'status' => 'success',
                  'data' => [
                      'tenant' => $store->tenant
                  ]
              ]);*/

            $tenant = Tenant::join('stores', 'tenant.id', '=', 'stores.owner_id')
                ->where('stores.domain', $request->domain)
                ->select('tenant.*', 'stores.*')
                ->first();

            \Log::debug('SQL Query: ' . Tenant::join('stores', 'tenants.id', '=', 'stores.owner_id')
                    ->where('stores.domain', $request->domain)
                    ->select('tenant.*', 'stores.*')
                    ->toSql());
            \Log::debug('Processed Domain: ' . $request->domain);
            if (!$tenant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tenant not found for the given domain.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'tenant' => $tenant
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve tenant.',
                'error' => app()->environment('production') ? null : $e->getMessage()
            ]);
        }
    }

   public function destroy($id)
    {
        Tenant::destroy($id);
        return response()->json(['message' => 'Tenant deleted']);
    }

    public function destroyByEmail(Request $request)
    {
        \Log::debug('here');
        $emails = $request->input('emails');

        if (!is_array($emails) || empty($emails)) {
            return response()->json(['message' => 'No emails provided'], 400);
        }

        $tenants = Tenant::whereIn('email', $emails)->get();
        \Log::debug('tenant Domain: ' . $tenants);
        if ($tenants->isEmpty()) {
            return response()->json(['message' => 'No tenants found for provided emails'], 404);
        }

        $deletedCount = Tenant::whereIn('email', $emails)->forceDelete();
        \Log::debug('tenant Domain: ' . $deletedCount);
        return response()->json([
            'message' => "Deleted $deletedCount tenant(s)",
            'deleted_emails' => $emails,
            'matched_tenants' => $tenants->pluck('email')
        ]);
    }



    /*    public function store(Request $request)
        {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:tenant,email',
                'address' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'status' => ['nullable', Rule::in(['active', 'pending', 'suspended'])],
                'password' => 'required|string|min:8',
            ]);

            $password_hashed=Hash::make($validated['password']);

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'password' => $password_hashed,

            ]);
            $email= $validated['email'];
            $password= $validated['password'];



            $token = $tenant->createToken('TenantToken')->plainTextToken;

            // Return response
            return response()->json([
                'message' => 'Tenant registered successfully',
                'token' => $token,
                'email' => $email ,
                'password' => $password ,
                'tenant' => $tenant
            ], 201);

        }*/
}

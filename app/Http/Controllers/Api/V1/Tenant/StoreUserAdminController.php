<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Exception\ClientException;
use App\Services\Tenant\RolePermissionService;

use App\Services\Tenant\StoreUserAdminService;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StoreUserAdminController extends Controller
{

    public $storeUserAdminService;
    public $rolePermissionService;


    public function __construct(StoreUserAdminService $storeUserAdminService, RolePermissionService $rolePermissionService)
    {
        $this->storeUserAdminService = $storeUserAdminService;
        $this->rolePermissionService = $rolePermissionService;
    }

    public function index(Request $request)
    {

        try {
            $request->validate([
                'store_id' => 'required|integer|exists:stores,id',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $perPage = $request->input('per_page', 15);
            $users = $this->storeUserAdminService->getAllForStore(
                $request->input('store_id'),
                $perPage
            );

            return response()->json([
                'data' => $users->items(),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error in store admin index: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve store admin users'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|max:255|unique:store_user_admin,username',
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:store_user_admin,email',
                'password' => 'required|string|min:8',
                'store_id' => 'required|integer|exists:stores,id',
                'role' => 'required|string|max:255',
                'permissions' => 'required|array',
                'permissions.*' => 'string|distinct',
            ]);

            $createdUserAdmin = $this->storeUserAdminService->createForStore($request->only([
                'username',
                'firstname',
                'lastname',
                'email',
                'password',
                'store_id'
            ]));

            $this->rolePermissionService->assignRoleWithPermissionsToUser(
                $createdUserAdmin,
                $request->role,
                $request->permissions
            );


            return response()->json([
                'message' => 'Store admin created successfully',
                'data' => $createdUserAdmin
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating store admin: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        try {
            $request->validate([
                'store_id' => 'required|integer|exists:stores,id',
            ]);

            $user = $this->storeUserAdminService->getById($id, $request->input('store_id'));

            return response()->json([
                'data' => $user
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Store admin user not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching store admin: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve store admin user'], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'username' => 'sometimes|string|max:255|unique:store_user_admin,username,' . $id,
                'firstname' => 'sometimes|string|max:255',
                'lastname' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:store_user_admin,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'store_id' => 'required|integer|exists:stores,id',
            ]);

            $updatedUser = $this->storeUserAdminService->update(
                $id,
                $request->input('store_id'),
                $request->only(['username', 'firstname', 'lastname', 'email', 'password'])
            );

            return response()->json([
                'message' => 'Store admin updated successfully',
                'data' => $updatedUser
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Store admin user not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating store admin: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update store admin'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $request->validate([
                'store_id' => 'required|integer|exists:stores,id',
            ]);

            $this->storeUserAdminService->delete($id, $request->input('store_id'));

            return response()->json([
                'message' => 'Store admin deleted successfully'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Store admin user not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting store admin: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete store admin'], 500);
        }
    }
}

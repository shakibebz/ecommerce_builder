<?php

namespace App\Services\Tenant;

use App\Models\Role;
use App\Models\Stores;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use App\Models\StoreUserAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StoreUserAdminService
{

    /**
     * Get paginated store admin users for a specific store
     *
     * @param int $storeId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllForStore(int $storeId, int $perPage = 15)
    {
        try {
            return StoreUserAdmin::where('store_id', $storeId)
                ->select('id', 'store_id', 'firstname', 'lastname', 'username', 'email', 'created_at')
                ->latest()
                ->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Error fetching store admin users: ' . $e->getMessage());
            throw new \Exception('Failed to retrieve store admin users');
        }
    }

    /**
     * Create a new store admin user
     *
     * @param array $adminData
     * @return StoreUserAdmin
     */
    public function createForStore(array $adminData)
    {
        try {
            $validatedData = array_filter([
                'store_id' => $adminData['store_id'] ?? null,
                'firstname' => $adminData['firstname'] ?? null,
                'lastname' => $adminData['lastname'] ?? null,
                'username' => $adminData['username'] ?? null,
                'email' => $adminData['email'] ?? null,
                'password' => isset($adminData['password']) ? Hash::make($adminData['password']) : null,
            ]);

            return StoreUserAdmin::create($validatedData);
        } catch (\Exception $e) {
            Log::error('Error creating store admin: ' . $e->getMessage());
            throw new \Exception('Failed to create store admin: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific store admin user by ID and store ID
     *
     * @param int $userId
     * @param int $storeId
     * @return StoreUserAdmin
     */
    public function getById(int $userId, int $storeId)
    {
        try {
            return StoreUserAdmin::where('id', $userId)
                ->where('store_id', $storeId)
                ->select('id', 'store_id', 'firstname', 'lastname', 'username', 'email', 'created_at')
                ->with('roles')
                ->firstOrFail();

        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Store admin user not found');
        } catch (\Exception $e) {
            Log::error('Error fetching store admin user: ' . $e->getMessage());
            throw new \Exception('Failed to retrieve store admin user');
        }
    }

    /**
     * Update a store admin user
     *
     * @param int $userId
     * @param int $storeId
     * @param array $adminData
     * @return StoreUserAdmin
     */
    public function update(int $userId, int $storeId, array $adminData)
    {
        try {
            $user = StoreUserAdmin::where('id', $userId)
                ->where('store_id', $storeId)
                ->with('roles')
                ->firstOrFail();

            $validatedData = array_filter([
                'firstname' => $adminData['firstname'] ?? $user->firstname,
                'lastname' => $adminData['lastname'] ?? $user->lastname,
                'username' => $adminData['username'] ?? $user->username,
                'email' => $adminData['email'] ?? $user->email,
                'password' => isset($adminData['password']) ? $adminData['password'] : $user->password,
            ]);

            $user->update($validatedData);
            return $user;
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Store admin user not found');
        } catch (\Exception $e) {
            Log::error('Error updating store admin: ' . $e->getMessage());
            throw new \Exception('Failed to update store admin');
        }
    }

    /**
     * Delete a store admin user
     *
     * @param int $userId
     * @param int $storeId
     * @return bool
     */
    public function delete(int $userId, int $storeId)
    {
        try {
            $user = StoreUserAdmin::where('id', $userId)
                ->where('store_id', $storeId)
                ->with('roles')
                ->firstOrFail();

            return $user->delete();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Store admin user not found');
        } catch (\Exception $e) {
            Log::error('Error deleting store admin: ' . $e->getMessage());
            throw new \Exception('Failed to delete store admin');
        }
    }
}

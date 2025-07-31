<?php
namespace App\Services\Tenant;

use App\Models\StoreUserAdmin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RolePermissionService
{
    public function assignRoleWithPermissionsToUser(StoreUserAdmin $user, string $roleName, array $permissionNames)
    {
        try {
            $allowedPermissions = config('role_permissions');


            $invalid = array_diff($permissionNames, $allowedPermissions);

            if (!empty($invalid)) {
                throw ValidationException::withMessages([
                    'permissions' => ['Invalid permissions: ' . implode(', ', $invalid)]
                ]);
            }


            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'store_admin',
            ]);


            foreach ($permissionNames as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm,
                    'guard_name' => 'store_admin',
                ]);
            }


            $role->syncPermissions($permissionNames);

            $user->assignRole($role);

            return $role;
        } catch (\Exception $e) {
            Log::error('Role/Permission assignment failed: ' . $e->getMessage());
            throw new \Exception('Failed to assign role and permissions: ' . $e->getMessage());
        }
    }
}

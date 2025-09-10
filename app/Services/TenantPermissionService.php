<?php

namespace App\Services;

use App\Models\Admin\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TenantPermissionService
{
    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return false;
        }

        // Check direct permissions
        $hasDirectPermission = DB::table('tenant_model_has_permissions')
            ->join('tenant_permissions', 'tenant_model_has_permissions.permission_id', '=', 'tenant_permissions.id')
            ->where('tenant_model_has_permissions.model_type', 'App\\Models\\Tenant\\User')
            ->where('tenant_model_has_permissions.model_id', $user->id)
            ->where('tenant_permissions.name', $permission)
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        // Check role permissions
        $hasRolePermission = DB::table('tenant_model_has_roles')
            ->join('tenant_role_has_permissions', 'tenant_model_has_roles.role_id', '=', 'tenant_role_has_permissions.role_id')
            ->join('tenant_permissions', 'tenant_role_has_permissions.permission_id', '=', 'tenant_permissions.id')
            ->where('tenant_model_has_roles.model_type', 'App\\Models\\Tenant\\User')
            ->where('tenant_model_has_roles.model_id', $user->id)
            ->where('tenant_permissions.name', $permission)
            ->exists();

        return $hasRolePermission;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return false;
        }

        return DB::table('tenant_model_has_roles')
            ->join('tenant_roles', 'tenant_model_has_roles.role_id', '=', 'tenant_roles.id')
            ->where('tenant_model_has_roles.model_type', 'App\\Models\\Tenant\\User')
            ->where('tenant_model_has_roles.model_id', $user->id)
            ->where('tenant_roles.name', $role)
            ->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get user's roles
     */
    public function getUserRoles(): array
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return [];
        }

        return DB::table('tenant_model_has_roles')
            ->join('tenant_roles', 'tenant_model_has_roles.role_id', '=', 'tenant_roles.id')
            ->where('tenant_model_has_roles.model_type', 'App\\Models\\Tenant\\User')
            ->where('tenant_model_has_roles.model_id', $user->id)
            ->pluck('tenant_roles.name')
            ->toArray();
    }

    /**
     * Get user's permissions (direct + role-based)
     */
    public function getUserPermissions(): array
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return [];
        }

        // Get direct permissions
        $directPermissions = DB::table('tenant_model_has_permissions')
            ->join('tenant_permissions', 'tenant_model_has_permissions.permission_id', '=', 'tenant_permissions.id')
            ->where('tenant_model_has_permissions.model_type', 'App\\Models\\Tenant\\User')
            ->where('tenant_model_has_permissions.model_id', $user->id)
            ->pluck('tenant_permissions.name')
            ->toArray();

        // Get role permissions
        $rolePermissions = DB::table('tenant_model_has_roles')
            ->join('tenant_role_has_permissions', 'tenant_model_has_roles.role_id', '=', 'tenant_role_has_permissions.role_id')
            ->join('tenant_permissions', 'tenant_role_has_permissions.permission_id', '=', 'tenant_permissions.id')
            ->where('tenant_model_has_roles.model_type', 'App\\Models\\Tenant\\User')
            ->where('tenant_model_has_roles.model_id', $user->id)
            ->pluck('tenant_permissions.name')
            ->toArray();

        return array_unique(array_merge($directPermissions, $rolePermissions));
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, string $roleName): bool
    {
        $role = DB::table('tenant_roles')->where('name', $roleName)->first();
        if (!$role) {
            return false;
        }

        // Check if user already has this role
        $exists = DB::table('tenant_model_has_roles')
            ->where('model_type', 'App\\Models\\Tenant\\User')
            ->where('model_id', $userId)
            ->where('role_id', $role->id)
            ->exists();

        if ($exists) {
            return true; // Already has the role
        }

        DB::table('tenant_model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => 'App\\Models\\Tenant\\User',
            'model_id' => $userId,
        ]);

        return true;
    }

    /**
     * Remove role from user
     */
    public function removeRole(int $userId, string $roleName): bool
    {
        $role = DB::table('tenant_roles')->where('name', $roleName)->first();

        if (!$role) {
            return false;
        }

        return DB::table('tenant_model_has_roles')
            ->where('model_type', 'App\\Models\\Tenant\\User')
            ->where('model_id', $userId)
            ->where('role_id', $role->id)
            ->delete() > 0;
    }

    /**
     * Give permission to user
     */
    public function givePermission(int $userId, string $permissionName): bool
    {
        $permission = DB::table('tenant_permissions')->where('name', $permissionName)->first();

        if (!$permission) {
            return false;
        }

        // Check if user already has this permission
        $exists = DB::table('tenant_model_has_permissions')
            ->where('model_type', 'App\\Models\\Tenant\\User')
            ->where('model_id', $userId)
            ->where('permission_id', $permission->id)
            ->exists();

        if ($exists) {
            return true; // Already has the permission
        }

        DB::table('tenant_model_has_permissions')->insert([
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Tenant\\User',
            'model_id' => $userId,
        ]);

        return true;
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(int $userId, string $permissionName): bool
    {
        $permission = DB::table('tenant_permissions')->where('name', $permissionName)->first();

        if (!$permission) {
            return false;
        }

        return DB::table('tenant_model_has_permissions')
            ->where('model_type', 'App\\Models\\Tenant\\User')
            ->where('model_id', $userId)
            ->where('permission_id', $permission->id)
            ->delete() > 0;
    }

    /**
     * Get all available roles
     */
    public function getAllRoles(): array
    {
        return DB::table('tenant_roles')
            ->select('id', 'name', 'display_name', 'description', 'is_system_role')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get all available permissions
     */
    public function getAllPermissions(): array
    {
        return DB::table('tenant_permissions')
            ->select('id', 'name', 'display_name', 'description', 'category')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get permissions by category
     */
    public function getPermissionsByCategory(): array
    {
        $permissions = DB::table('tenant_permissions')
            ->select('id', 'name', 'display_name', 'description', 'category')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category')
            ->toArray();

        return $permissions;
    }
}
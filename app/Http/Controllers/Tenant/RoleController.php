<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private TenantPermissionService $permissionService;

    public function __construct(TenantPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get all roles
     */
    public function index(): JsonResponse
    {
        $roles = $this->permissionService->getAllRoles();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Get all permissions
     */
    public function permissions(): JsonResponse
    {
        $permissions = $this->permissionService->getPermissionsByCategory();

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Get user's roles and permissions
     */
    public function userPermissions(): JsonResponse
    {
        $user = auth()->guard('tenant')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'roles' => $this->permissionService->getUserRoles(),
                'permissions' => $this->permissionService->getUserPermissions(),
            ]
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role_name' => 'required|string|exists:tenant_roles,name'
        ]);

        $success = $this->permissionService->assignRole(
            $request->user_id,
            $request->role_name
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to assign role'
        ], 500);
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role_name' => 'required|string|exists:tenant_roles,name'
        ]);

        $success = $this->permissionService->removeRole(
            $request->user_id,
            $request->role_name
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove role'
        ], 500);
    }

    /**
     * Give permission to user
     */
    public function givePermission(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permission_name' => 'required|string|exists:tenant_permissions,name'
        ]);

        $success = $this->permissionService->givePermission(
            $request->user_id,
            $request->permission_name
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Permission granted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to grant permission'
        ], 500);
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permission_name' => 'required|string|exists:tenant_permissions,name'
        ]);

        $success = $this->permissionService->revokePermission(
            $request->user_id,
            $request->permission_name
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to revoke permission'
        ], 500);
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\TenantPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantPermission
{
    private TenantPermissionService $permissionService;

    public function __construct(TenantPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$this->permissionService->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'error' => 'insufficient_permissions'
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantJwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        dd(333);
        $guard = Auth::guard('tenant');

        if (!$guard->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        $tenant = $guard->getTenant();
        if (!$tenant || !$tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant suspended or invalid'
            ], 403);
        }

        return $next($request);
    }
}

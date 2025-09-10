<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\TenantLoginRequest;
use App\Http\Requests\Tenant\RefreshTokenRequest;
use App\Models\Admin\Tenant;
use App\Models\Tenant\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function login(TenantLoginRequest $request): JsonResponse
    {

        // Validate tenant and set connection
        $tenant = Tenant::find($request->tenant_id);
        if (!$tenant || !$tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive tenant'
            ], 403);
        }

        app(\App\Services\DatabaseManager::class)->setTenantConnection($tenant);

        // Attempt authentication
        $user = User::on('tenant')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Generate tokens
        $accessToken = $this->jwtService->generateTenantToken(
            $user->id,
            $tenant->id
        );

        $refreshToken = $this->jwtService->generateRefreshToken(
            $user->id,
            $tenant->id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 28800, // 8 hours
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                ]
            ]
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {

        try {
            $payload = $this->jwtService->validateToken($request->refresh_token);

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                throw new \Exception('Invalid refresh token');
            }

            $tenant = Tenant::find($payload['tenant_id']);
            if (!$tenant || !$tenant->isActive()) {
                throw new \Exception('Invalid or inactive tenant');
            }

            app(\App\Services\DatabaseManager::class)->setTenantConnection($tenant);

            $user = User::on('tenant')->find($payload['sub']);
            if (!$user) {
                throw new \Exception('User not found');
            }

            $accessToken = $this->jwtService->generateTenantToken(
                $user->id,
                $tenant->id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 28800, // 8 hours
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid refresh token'
            ], 401);
        }
    }

    public function me(): JsonResponse
    {
        $user = Auth::guard('tenant')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant' => [
                    'id' => $user->tenant->id,
                    'name' => $user->tenant->name,
                    'slug' => $user->tenant->slug,
                ]
            ]
        ]);
    }
}

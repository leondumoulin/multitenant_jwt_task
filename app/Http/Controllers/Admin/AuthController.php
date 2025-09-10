<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Admin\Admin;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $accessToken = $this->jwtService->generateAdminToken($admin->id);

        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 28800, // 8 hours
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ]
            ]
        ]);
    }

    public function me(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ]
        ]);
    }

    public function logout(): JsonResponse
    {
        // In a real application, you might want to blacklist the token
        // For now, we'll just return a success response
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}

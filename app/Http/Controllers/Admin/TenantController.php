<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateTenantRequest;
use App\Http\Resources\Admin\TenantResource;
use App\Models\Admin\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    private TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function index(): JsonResponse
    {
        $tenants = Tenant::select(['id', 'name', 'slug', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TenantResource::collection($tenants)
        ]);
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        try {
            $tenant = $this->tenantService->createTenant($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tenant creation started. You will be notified when the process is complete.',
                'data' => new TenantResource($tenant)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start tenant creation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new TenantResource($tenant)
        ]);
    }

    public function status(Tenant $tenant): JsonResponse
    {
        $status = $this->tenantService->getTenantStatus($tenant->id);

        return response()->json([
            'success' => true,
            'data' => [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'status' => $status,
                'status_message' => $this->getStatusMessage($status)
            ]
        ]);
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        try {
            $this->tenantService->suspendTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant suspended successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend tenant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function activate(Tenant $tenant): JsonResponse
    {
        try {
            $this->tenantService->activateTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant activated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate tenant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get human-readable status message
     */
    private function getStatusMessage(?string $status): string
    {
        return match ($status) {
            'pending' => 'Tenant creation is queued and will start shortly.',
            'creating' => 'Tenant database and setup is in progress.',
            true => 'Tenant is active and ready to use.',
            'failed' => 'Tenant creation failed. Please check logs for details.',
            'suspended' => 'Tenant is suspended.',
            default => 'Unknown status.',
        };
    }
}

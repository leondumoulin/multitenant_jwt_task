<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Get audit logs with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'user_id',
            'action',
            'resource_type',
            'resource_id',
            'date_from',
            'date_to'
        ]);

        $limit = $request->get('limit', 50);
        $limit = min($limit, 100); // Max 100 records per request

        $logs = $this->auditLogService->getLogs($filters, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get audit logs for current user
     */
    public function myLogs(Request $request): JsonResponse
    {
        $user = auth()->guard('tenant')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $limit = $request->get('limit', 50);
        $limit = min($limit, 100);

        $logs = $this->auditLogService->getUserLogs($user->id, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get audit logs for a specific resource
     */
    public function resourceLogs(Request $request, string $resourceType, int $resourceId): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $limit = min($limit, 100);

        $logs = $this->auditLogService->getResourceLogs($resourceType, $resourceId, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get audit statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->auditLogService->getAuditStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get recent activity
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $limit = min($limit, 50);

        $logs = $this->auditLogService->getLogs([], $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get activity by action type
     */
    public function byAction(Request $request, string $action): JsonResponse
    {
        $filters = ['action' => $action];
        $limit = $request->get('limit', 50);
        $limit = min($limit, 100);

        $logs = $this->auditLogService->getLogs($filters, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get activity by resource type
     */
    public function byResourceType(Request $request, string $resourceType): JsonResponse
    {
        $filters = ['resource_type' => $resourceType];
        $limit = $request->get('limit', 50);
        $limit = min($limit, 100);

        $logs = $this->auditLogService->getLogs($filters, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}

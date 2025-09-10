<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * Log an action performed by a user
     */
    public function log(string $action, string $resourceType, ?int $resourceId = null, ?string $resourceName = null, ?array $oldValues = null, ?array $newValues = null, ?array $metadata = null): void
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return; // Don't log if no authenticated user
        }

        $logData = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'resource_name' => $resourceName,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('audit_logs')->insert($logData);
    }

    /**
     * Log model creation
     */
    public function logCreated(Model $model, ?array $metadata = null): void
    {
        $this->log(
            'created',
            class_basename($model),
            $model->id,
            $this->getModelName($model),
            null,
            $model->toArray(),
            $metadata
        );
    }

    /**
     * Log model update
     */
    public function logUpdated(Model $model, array $oldValues, ?array $metadata = null): void
    {
        $this->log(
            'updated',
            class_basename($model),
            $model->id,
            $this->getModelName($model),
            $oldValues,
            $model->toArray(),
            $metadata
        );
    }

    /**
     * Log model deletion
     */
    public function logDeleted(Model $model, ?array $metadata = null): void
    {
        $this->log(
            'deleted',
            class_basename($model),
            $model->id,
            $this->getModelName($model),
            $model->toArray(),
            null,
            $metadata
        );
    }

    /**
     * Log model viewing
     */
    public function logViewed(Model $model, ?array $metadata = null): void
    {
        $this->log(
            'viewed',
            class_basename($model),
            $model->id,
            $this->getModelName($model),
            null,
            null,
            $metadata
        );
    }

    /**
     * Log custom action
     */
    public function logAction(string $action, Model $model, ?array $metadata = null): void
    {
        $this->log(
            $action,
            class_basename($model),
            $model->id,
            $this->getModelName($model),
            null,
            null,
            $metadata
        );
    }

    /**
     * Get audit logs for a specific user
     */
    public function getUserLogs(int $userId, int $limit = 50): \Illuminate\Support\Collection
    {
        return DB::table('audit_logs')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for a specific resource
     */
    public function getResourceLogs(string $resourceType, int $resourceId, int $limit = 50): \Illuminate\Support\Collection
    {
        return DB::table('audit_logs')
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs with filters
     */
    public function getLogs(array $filters = [], int $limit = 50): \Illuminate\Support\Collection
    {
        $query = DB::table('audit_logs');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['resource_id'])) {
            $query->where('resource_id', $filters['resource_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit statistics
     */
    public function getAuditStats(): array
    {
        $totalLogs = DB::table('audit_logs')->count();

        $actionStats = DB::table('audit_logs')
            ->select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action')
            ->toArray();

        $resourceStats = DB::table('audit_logs')
            ->select('resource_type', DB::raw('count(*) as count'))
            ->groupBy('resource_type')
            ->get()
            ->pluck('count', 'resource_type')
            ->toArray();

        $userStats = DB::table('audit_logs')
            ->select('user_id', 'user_name', DB::raw('count(*) as count'))
            ->groupBy('user_id', 'user_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'total_logs' => $totalLogs,
            'action_stats' => $actionStats,
            'resource_stats' => $resourceStats,
            'top_users' => $userStats,
        ];
    }

    /**
     * Get a human-readable name for the model
     */
    private function getModelName(Model $model): string
    {
        // Try to get a name field first
        if (isset($model->name)) {
            return $model->name;
        }

        // Try title field
        if (isset($model->title)) {
            return $model->title;
        }

        // Try email field
        if (isset($model->email)) {
            return $model->email;
        }

        // Fallback to ID
        return class_basename($model) . ' #' . $model->id;
    }
}

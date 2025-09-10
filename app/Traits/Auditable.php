<?php

namespace App\Traits;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static $auditLogService;

    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable(): void
    {
        static::$auditLogService = app(AuditLogService::class);

        // Log model creation
        static::created(function (Model $model) {
            static::$auditLogService->logCreated($model);
        });

        // Log model updates
        static::updated(function (Model $model) {
            $oldValues = $model->getOriginal();
            static::$auditLogService->logUpdated($model, $oldValues);
        });

        // Log model deletion
        static::deleted(function (Model $model) {
            static::$auditLogService->logDeleted($model);
        });
    }

    /**
     * Log a custom action for this model
     */
    public function logAction(string $action, ?array $metadata = null): void
    {
        static::$auditLogService->logAction($action, $this, $metadata);
    }

    /**
     * Log viewing this model
     */
    public function logViewed(?array $metadata = null): void
    {
        static::$auditLogService->logViewed($this, $metadata);
    }

    /**
     * Get audit logs for this model
     */
    public function getAuditLogs(int $limit = 50): \Illuminate\Support\Collection
    {
        return static::$auditLogService->getResourceLogs(
            class_basename($this),
            $this->id,
            $limit
        );
    }
}

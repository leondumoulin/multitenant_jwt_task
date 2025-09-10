<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\Tenant\ContactController;
use App\Http\Controllers\Tenant\DealController;
use App\Http\Controllers\Tenant\ActivityController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\AuditLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Admin Routes (System DB)
Route::prefix('admin')->group(function () {
    // Admin Authentication
    Route::post('login', [AdminAuthController::class, 'login']);

    // Protected Admin Routes
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('logout', [AdminAuthController::class, 'logout']);

        // Tenant Management
        Route::apiResource('tenants', TenantController::class);
        Route::get('tenants/{tenant}/status', [TenantController::class, 'status']);
        Route::patch('tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
        Route::patch('tenants/{tenant}/activate', [TenantController::class, 'activate']);
    });
});

// Tenant Routes (Tenant DB)
Route::prefix('tenant')->group(function () {
    // Tenant Authentication
    Route::post('login', [TenantAuthController::class, 'login']);
    Route::post('refresh', [TenantAuthController::class, 'refresh']);

    // Protected Tenant Routes
    Route::middleware(['auth:tenant'])->group(function () {
        Route::get('me', [TenantAuthController::class, 'me']);
        Route::post('logout', [TenantAuthController::class, 'logout']);

        // CRM Resources with permission middleware
        Route::apiResource('contacts', ContactController::class)->middleware('permission:contacts.view');
        Route::apiResource('deals', DealController::class)->middleware('permission:deals.view');
        Route::apiResource('activities', ActivityController::class)->middleware('permission:activities.view');

        // Reports
        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('deals', [ReportController::class, 'deals']);
            Route::get('contacts', [ReportController::class, 'contacts']);
            Route::get('activities', [ReportController::class, 'activities']);
        });

        // Roles and Permissions
        Route::prefix('roles')->middleware('permission:roles.view')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('permissions', [RoleController::class, 'permissions']);
            Route::get('user-permissions', [RoleController::class, 'userPermissions']);
            Route::post('assign-role', [RoleController::class, 'assignRole'])->middleware('permission:users.manage_roles');
            Route::post('remove-role', [RoleController::class, 'removeRole'])->middleware('permission:users.manage_roles');
            Route::post('give-permission', [RoleController::class, 'givePermission'])->middleware('permission:users.manage_roles');
            Route::post('revoke-permission', [RoleController::class, 'revokePermission'])->middleware('permission:users.manage_roles');
        });

        // Audit Logs
        Route::prefix('audit-logs')->middleware('permission:audit_logs.view')->group(function () {
            Route::get('/', [AuditLogController::class, 'index']);
            Route::get('my-logs', [AuditLogController::class, 'myLogs']);
            Route::get('stats', [AuditLogController::class, 'stats']);
            Route::get('recent', [AuditLogController::class, 'recentActivity']);
            Route::get('action/{action}', [AuditLogController::class, 'byAction']);
            Route::get('resource/{resourceType}', [AuditLogController::class, 'byResourceType']);
            Route::get('resource/{resourceType}/{resourceId}', [AuditLogController::class, 'resourceLogs']);
        });
    });
});

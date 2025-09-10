<?php

namespace App\Services;

use App\Jobs\CreateTenantJob;
use App\Models\Admin\Admin;
use App\Models\Admin\Tenant;
use App\Models\Tenant\User;
use App\Notifications\TenantCreationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    private DatabaseManager $dbManager;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    public function createTenant(array $data): Tenant
    {
        // Create tenant record with 'pending' status
        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'db_name' => 'tenant_' . Str::slug($data['name']),
            'db_user' => isset($data['create_db_user']) ? 'u' . substr(md5($data['name']), 0, 15) : null, // Max 16 chars for MySQL
            'db_pass' => isset($data['create_db_user']) ? Str::random(16) : null,
            'status' => 'pending',
            'admin_email' => $data['admin_email'] ?? null,
        ]);

        // Dispatch the job to create tenant asynchronously
        CreateTenantJob::dispatch($data, $tenant->id);

        // Notify all admins about tenant creation start
        $this->notifyAdmins($tenant, 'creating');

        return $tenant;
    }

    /**
     * Create tenant synchronously (for testing or immediate creation)
     */
    public function createTenantSync(array $data): Tenant
    {
        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'db_name' => 'tenant_' . Str::slug($data['name']),
            'db_user' => isset($data['create_db_user']) ? 'u' . substr(md5($data['name']), 0, 15) : null, // Max 16 chars for MySQL
            'db_pass' => isset($data['create_db_user']) ? Str::random(16) : null,
            'status' => 'creating',
            'admin_email' => $data['admin_email'] ?? null,
        ]);

        // Create database
        if (!$this->dbManager->createTenantDatabase($tenant)) {
            $tenant->delete();
            throw new \Exception('Failed to create tenant database');
        }

        // Run migrations
        if (!$this->dbManager->runTenantMigrations($tenant)) {
            $this->dbManager->dropTenantDatabase($tenant);
            $tenant->delete();
            throw new \Exception('Failed to run tenant migrations');
        }

        // Create default admin user
        $this->createDefaultAdmin($tenant, $data);

        // Seed database
        $this->dbManager->seedTenantDatabase($tenant);

        // Update status to active
        $tenant->update(['status' => true]);

        return $tenant;
    }

    private function createDefaultAdmin(Tenant $tenant, array $data): User
    {
        $this->dbManager->setTenantConnection($tenant);

        $user = User::on('tenant')->create([
            'name' => $data['admin_name'] ?? 'Admin',
            'email' => $data['admin_email'] ?? 'admin@' . $tenant->slug . '.com',
            'password' => Hash::make($data['admin_password'] ?? 'password'),
            'role' => 'admin',
        ]);

        // Assign super_admin role to the default admin user
        $user->assignRole('super_admin');

        return $user;
    }

    public function suspendTenant(Tenant $tenant): bool
    {
        $tenant->suspend();
        return true;
    }

    public function activateTenant(Tenant $tenant): bool
    {
        $tenant->activate();
        return true;
    }

    /**
     * Get tenant creation status
     */
    public function getTenantStatus(int $tenantId): ?string
    {
        $tenant = Tenant::find($tenantId);
        return $tenant ? $tenant->status : null;
    }

    /**
     * Notify all admins about tenant creation status
     */
    private function notifyAdmins(Tenant $tenant, string $status, ?string $message = null): void
    {
        $admins = Admin::all();

        foreach ($admins as $admin) {
            $admin->notify(new TenantCreationNotification($tenant, $status, $message));
        }
    }

    /**
     * Mark tenant creation as completed
     */
    public function markTenantCreationCompleted(Tenant $tenant): void
    {
        $tenant->update(['status' => true]);
        $this->notifyAdmins($tenant, 'completed');
    }

    /**
     * Mark tenant creation as failed
     */
    public function markTenantCreationFailed(Tenant $tenant, string $message): void
    {
        $tenant->update(['status' => 'failed']);
        $this->notifyAdmins($tenant, 'failed', $message);
    }
}

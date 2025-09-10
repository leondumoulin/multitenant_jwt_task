<?php

namespace App\Jobs;

use App\Models\Admin\Tenant;
use App\Services\DatabaseManager;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant\User;

class CreateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times on failure

    private array $tenantData;
    private int $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $tenantData, int $tenantId)
    {
        $this->tenantData = $tenantData;
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(DatabaseManager $dbManager, TenantService $tenantService): void
    {
        try {
            Log::info("Starting tenant creation job for tenant ID: {$this->tenantId}");

            // Get the tenant record
            $tenant = Tenant::find($this->tenantId);
            if (!$tenant) {
                throw new \Exception("Tenant not found with ID: {$this->tenantId}");
            }

            // Update tenant status to 'creating'
            $tenant->update(['status' => 'creating']);

            // Create the tenant database
            if (!$dbManager->createTenantDatabase($tenant)) {
                throw new \Exception('Failed to create tenant database');
            }

            Log::info("Database created for tenant: {$tenant->name}");

            // Run migrations
            if (!$dbManager->runTenantMigrations($tenant)) {
                throw new \Exception('Failed to run tenant migrations');
            }

            Log::info("Migrations completed for tenant: {$tenant->name}");

            // Seed roles and permissions (after migrations are complete)
            $this->seedRolesAndPermissions($tenant);

            // Seed the database
            $dbManager->seedTenantDatabase($tenant);
            Log::info("Database seeded for tenant: {$tenant->name}");

            // Create default admin user
            $this->createDefaultAdmin($tenant, $this->tenantData);

            Log::info("Default admin user created for tenant: {$tenant->name}");


            // Mark tenant creation as completed
            $tenantService->markTenantCreationCompleted($tenant);

            Log::info("Tenant creation completed successfully for: {$tenant->name}");
        } catch (\Exception $e) {
            Log::error("Tenant creation failed for ID {$this->tenantId}: " . $e->getMessage());

            // Mark tenant creation as failed
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                $tenantService->markTenantCreationFailed($tenant, $e->getMessage());
            }

            // Clean up database if it was created
            if (isset($tenant) && $tenant->db_name) {
                try {
                    $dbManager->dropTenantDatabase($tenant);
                    Log::info("Cleaned up failed tenant database: {$tenant->db_name}");
                } catch (\Exception $cleanupException) {
                    Log::error("Failed to cleanup tenant database: " . $cleanupException->getMessage());
                }
            }

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Create the default admin user for the tenant.
     */
    private function createDefaultAdmin(Tenant $tenant, array $data): User
    {
        $dbManager = app(DatabaseManager::class);
        $dbManager->setTenantConnection($tenant);

        $admin = User::on('tenant')->create([
            'name' => $data['admin_name'] ?? 'Admin',
            'email' => $data['admin_email'] ?? 'admin@' . $tenant->slug . '.com',
            'password' => Hash::make($data['admin_password'] ?? 'password'),
        ]);
        $admin->assignRole('super_admin');
        return $admin;
    }

    /**
     * Seed roles and permissions for the tenant
     */
    private function seedRolesAndPermissions(Tenant $tenant): void
    {
        $dbManager = app(DatabaseManager::class);
        $dbManager->setTenantConnection($tenant);

        // Run the roles and permissions seeder using Artisan command
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => 'TenantRolesAndPermissionsSeeder',
            '--force' => true,
        ]);

        Log::info("Roles and permissions seeded for tenant: {$tenant->name}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("CreateTenantJob failed permanently for tenant ID {$this->tenantId}: " . $exception->getMessage());

        // Mark tenant creation as failed
        $tenant = Tenant::find($this->tenantId);
        if ($tenant) {
            $tenantService = app(TenantService::class);
            $tenantService->markTenantCreationFailed($tenant, $exception->getMessage());
        }
    }
}

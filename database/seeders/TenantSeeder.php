<?php

namespace Database\Seeders;

use App\Models\Admin\Tenant;
use App\Services\TenantService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantService = app(TenantService::class);

        // Create ACME tenant
        $acmeTenant = $tenantService->createTenant([
            'name' => 'ACME Corporation',
            'admin_name' => 'John Doe',
            'admin_email' => 'admin@acme.com',
            'admin_password' => 'password123',
            'create_db_user' => true
        ]);

        // Create Globex tenant
        $globexTenant = $tenantService->createTenant([
            'name' => 'Globex Corporation',
            'admin_name' => 'Jane Smith',
            'admin_email' => 'admin@globex.com',
            'admin_password' => 'password123',
            'create_db_user' => true
        ]);

        $this->command->info('Created tenants: ACME Corporation and Globex Corporation');
    }
}

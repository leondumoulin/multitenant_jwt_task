<?php

namespace Database\Seeders;

use App\Jobs\CreateTenantJob;
use App\Models\Admin\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class SampleTenantsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantSeeds = [
            [
                'name' => 'acme',
                'create_db_user' => false,
                'admin_email' => 'acme@admin.com',
                "admin_name" => "acme",
                "admin_password" => "password123",
            ],
            [
                'name' => 'globex',
                'create_db_user' => false,
                'admin_email' => 'globex@admin.com',
                "admin_name" => "globex",
                "admin_password" => "password123",
            ],
        ];

        foreach ($tenantSeeds as $data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'db_name' => 'tenant_' . Str::slug($data['name']),
                'db_user' => isset($data['create_db_user']) ? 'u' . substr(md5($data['name']), 0, 15) : null, // Max 16 chars for MySQL
                'db_pass' => isset($data['create_db_user']) ? Str::random(16) : null,
                'status' => 'pending',
                'admin_email' => $data['admin_email'] ?? null,
            ]);

            CreateTenantJob::dispatch($data, $tenant->id);
        }
    }
}

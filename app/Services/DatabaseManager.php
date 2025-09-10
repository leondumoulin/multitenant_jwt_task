<?php

namespace App\Services;

use App\Models\Admin\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseManager
{
    public function createTenantDatabase(Tenant $tenant): bool
    {
        try {
            // Create database
            DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$tenant->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Create database user (optional - for better security)
            if ($tenant->db_user && $tenant->db_pass) {
                DB::connection('mysql')->statement("CREATE USER IF NOT EXISTS '{$tenant->db_user}'@'localhost' IDENTIFIED BY '{$tenant->db_pass}'");
                DB::connection('mysql')->statement("GRANT ALL PRIVILEGES ON `{$tenant->db_name}`.* TO '{$tenant->db_user}'@'localhost'");
                DB::connection('mysql')->statement("FLUSH PRIVILEGES");
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create database for tenant {$tenant->slug}: " . $e->getMessage());
            return false;
        }
    }

    public function runTenantMigrations(Tenant $tenant): bool
    {
        try {
            $this->setTenantConnection($tenant);

            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to run migrations for tenant {$tenant->slug}: " . $e->getMessage());
            return false;
        }
    }

    public function seedTenantDatabase(Tenant $tenant): bool
    {
        try {
            $this->setTenantConnection($tenant);

            Artisan::call('db:seed', [
                '--database' => 'tenant',
                '--class' => 'TenantDataSeeder',
                '--force' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to seed database for tenant {$tenant->slug}: " . $e->getMessage());
            return false;
        }
    }

    public function setTenantConnection(Tenant $tenant): void
    {
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $tenant->db_name,
            'username' => $tenant->db_user ?: env('DB_USERNAME', 'root'),
            'password' => $tenant->db_pass ?: env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
        Config::set('database.default', 'tenant');
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function dropTenantDatabase(Tenant $tenant): bool
    {
        try {
            DB::connection('mysql')->statement("DROP DATABASE IF EXISTS `{$tenant->db_name}`");

            if ($tenant->db_user) {
                DB::connection('mysql')->statement("DROP USER IF EXISTS '{$tenant->db_user}'@'localhost'");
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to drop database for tenant {$tenant->slug}: " . $e->getMessage());
            return false;
        }
    }
}

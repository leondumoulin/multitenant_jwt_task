<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default roles
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full access to all features and settings',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access with most permissions',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Management access to contacts, deals, and activities',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sales_rep',
                'display_name' => 'Sales Representative',
                'description' => 'Access to manage contacts and deals',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Basic access to view and manage own data',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tenant_roles')->insert($roles);

        // Create permissions
        $permissions = [
            // User Management
            ['name' => 'users.view', 'display_name' => 'View Users', 'description' => 'View user list and details', 'category' => 'users'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'description' => 'Create new users', 'category' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'description' => 'Edit user information', 'category' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'description' => 'Delete users', 'category' => 'users'],
            ['name' => 'users.manage_roles', 'display_name' => 'Manage User Roles', 'description' => 'Assign and manage user roles', 'category' => 'users'],

            // Contact Management
            ['name' => 'contacts.view', 'display_name' => 'View Contacts', 'description' => 'View contact list and details', 'category' => 'contacts'],
            ['name' => 'contacts.create', 'display_name' => 'Create Contacts', 'description' => 'Create new contacts', 'category' => 'contacts'],
            ['name' => 'contacts.edit', 'display_name' => 'Edit Contacts', 'description' => 'Edit contact information', 'category' => 'contacts'],
            ['name' => 'contacts.delete', 'display_name' => 'Delete Contacts', 'description' => 'Delete contacts', 'category' => 'contacts'],
            ['name' => 'contacts.view_all', 'display_name' => 'View All Contacts', 'description' => 'View all contacts in the system', 'category' => 'contacts'],

            // Deal Management
            ['name' => 'deals.view', 'display_name' => 'View Deals', 'description' => 'View deal list and details', 'category' => 'deals'],
            ['name' => 'deals.create', 'display_name' => 'Create Deals', 'description' => 'Create new deals', 'category' => 'deals'],
            ['name' => 'deals.edit', 'display_name' => 'Edit Deals', 'description' => 'Edit deal information', 'category' => 'deals'],
            ['name' => 'deals.delete', 'display_name' => 'Delete Deals', 'description' => 'Delete deals', 'category' => 'deals'],
            ['name' => 'deals.view_all', 'display_name' => 'View All Deals', 'description' => 'View all deals in the system', 'category' => 'deals'],
            ['name' => 'deals.close', 'display_name' => 'Close Deals', 'description' => 'Close and finalize deals', 'category' => 'deals'],

            // Activity Management
            ['name' => 'activities.view', 'display_name' => 'View Activities', 'description' => 'View activity list and details', 'category' => 'activities'],
            ['name' => 'activities.create', 'display_name' => 'Create Activities', 'description' => 'Create new activities', 'category' => 'activities'],
            ['name' => 'activities.edit', 'display_name' => 'Edit Activities', 'description' => 'Edit activity information', 'category' => 'activities'],
            ['name' => 'activities.delete', 'display_name' => 'Delete Activities', 'description' => 'Delete activities', 'category' => 'activities'],
            ['name' => 'activities.view_all', 'display_name' => 'View All Activities', 'description' => 'View all activities in the system', 'category' => 'activities'],

            // Reports
            ['name' => 'reports.view', 'display_name' => 'View Reports', 'description' => 'View reports and analytics', 'category' => 'reports'],
            ['name' => 'reports.export', 'display_name' => 'Export Reports', 'description' => 'Export reports to various formats', 'category' => 'reports'],

            // Settings
            ['name' => 'settings.view', 'display_name' => 'View Settings', 'description' => 'View system settings', 'category' => 'settings'],
            ['name' => 'settings.edit', 'display_name' => 'Edit Settings', 'description' => 'Edit system settings', 'category' => 'settings'],

            // Audit Logs
            ['name' => 'audit_logs.view', 'display_name' => 'View Audit Logs', 'description' => 'View audit logs and activity history', 'category' => 'audit'],

            // Role Management
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'description' => 'View roles and permissions', 'category' => 'roles'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'description' => 'Create new roles', 'category' => 'roles'],
            ['name' => 'roles.edit', 'display_name' => 'Edit Roles', 'description' => 'Edit roles and permissions', 'category' => 'roles'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'description' => 'Delete roles', 'category' => 'roles'],
        ];

        // Add timestamps to permissions
        $permissions = array_map(function ($permission) {
            $permission['guard_name'] = 'tenant';
            $permission['created_at'] = now();
            $permission['updated_at'] = now();
            return $permission;
        }, $permissions);

        DB::table('tenant_permissions')->insert($permissions);

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles(): void
    {
        // Get role IDs
        $superAdminRoleId = DB::table('tenant_roles')->where('name', 'super_admin')->value('id');
        $adminRoleId = DB::table('tenant_roles')->where('name', 'admin')->value('id');
        $managerRoleId = DB::table('tenant_roles')->where('name', 'manager')->value('id');
        $salesRepRoleId = DB::table('tenant_roles')->where('name', 'sales_rep')->value('id');
        $userRoleId = DB::table('tenant_roles')->where('name', 'user')->value('id');

        // Get all permission IDs
        $allPermissionIds = DB::table('tenant_permissions')->pluck('id')->toArray();

        // Super Admin - All permissions
        $this->assignPermissionsToRole($superAdminRoleId, $allPermissionIds);

        // Admin - Most permissions except user management
        $adminPermissions = DB::table('tenant_permissions')
            ->whereNotIn('name', ['users.create', 'users.edit', 'users.delete', 'users.manage_roles'])
            ->pluck('id')
            ->toArray();
        $this->assignPermissionsToRole($adminRoleId, $adminPermissions);

        // Manager - Contacts, deals, activities, reports
        $managerPermissions = DB::table('tenant_permissions')
            ->whereIn('category', ['contacts', 'deals', 'activities', 'reports'])
            ->pluck('id')
            ->toArray();
        $this->assignPermissionsToRole($managerRoleId, $managerPermissions);

        // Sales Rep - Own contacts and deals
        $salesRepPermissions = DB::table('tenant_permissions')
            ->whereIn('name', [
                'contacts.view',
                'contacts.create',
                'contacts.edit',
                'contacts.delete',
                'deals.view',
                'deals.create',
                'deals.edit',
                'deals.delete',
                'deals.close',
                'activities.view',
                'activities.create',
                'activities.edit',
                'activities.delete',
                'reports.view'
            ])
            ->pluck('id')
            ->toArray();
        $this->assignPermissionsToRole($salesRepRoleId, $salesRepPermissions);

        // User - Basic view permissions
        $userPermissions = DB::table('tenant_permissions')
            ->whereIn('name', [
                'contacts.view',
                'deals.view',
                'activities.view'
            ])
            ->pluck('id')
            ->toArray();
        $this->assignPermissionsToRole($userRoleId, $userPermissions);
    }

    private function assignPermissionsToRole(int $roleId, array $permissionIds): void
    {
        $rolePermissions = array_map(function ($permissionId) use ($roleId) {
            return [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ];
        }, $permissionIds);

        DB::table('tenant_role_has_permissions')->insert($rolePermissions);
    }
}

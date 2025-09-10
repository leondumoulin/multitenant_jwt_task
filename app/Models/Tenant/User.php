<?php

namespace App\Models\Tenant;

use App\Models\Admin\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return app(\App\Services\TenantPermissionService::class)->hasPermission($permission);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return app(\App\Services\TenantPermissionService::class)->hasRole($role);
    }

    /**
     * Get user's roles
     */
    public function getRoles(): array
    {
        return app(\App\Services\TenantPermissionService::class)->getUserRoles();
    }

    /**
     * Get user's permissions
     */
    public function getPermissions(): array
    {
        return app(\App\Services\TenantPermissionService::class)->getUserPermissions();
    }

    /**
     * Assign role to user
     */
    public function assignRole(string $roleName): bool
    {
        return app(\App\Services\TenantPermissionService::class)->assignRole($this->id, $roleName);
    }

    /**
     * Remove role from user
     */
    public function removeRole(string $roleName): bool
    {
        return app(\App\Services\TenantPermissionService::class)->removeRole($this->id, $roleName);
    }
}

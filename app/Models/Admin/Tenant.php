<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'slug',
        'db_name',
        'db_user',
        'db_pass',
        'status',
        'admin_email'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Check if tenant is in a creation state
     */
    public function isCreating(): bool
    {
        return in_array($this->status, ['pending', 'creating']);
    }

    /**
     * Check if tenant creation failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isActive(): bool
    {
        return $this->status;
    }

    public function suspend(): void
    {
        $this->update(['status' => false]);
    }

    public function activate(): void
    {
        $this->update(['status' => true]);
    }
}

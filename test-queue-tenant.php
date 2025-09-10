<?php

/**
 * Test script for queue-based tenant creation
 *
 * This script demonstrates how to test the queue functionality
 * Run this script to create a test tenant using the queue system
 */

require_once 'vendor/autoload.php';

use App\Services\TenantService;
use App\Models\Admin\Tenant;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Testing Queue-Based Tenant Creation\n";
echo "=====================================\n\n";

try {
    $tenantService = app(TenantService::class);

    // Test data
    $testData = [
        'name' => 'Test Company ' . date('Y-m-d H:i:s'),
        'admin_name' => 'Test Admin',
        'admin_email' => 'admin@testcompany.com',
        'admin_password' => 'password123',
        'admin_password_confirmation' => 'password123',
        'create_db_user' => true,
    ];

    echo "📝 Creating tenant with data:\n";
    echo "   Name: {$testData['name']}\n";
    echo "   Admin Email: {$testData['admin_email']}\n\n";

    // Create tenant (async)
    $tenant = $tenantService->createTenant($testData);

    echo "✅ Tenant record created successfully!\n";
    echo "   ID: {$tenant->id}\n";
    echo "   Status: {$tenant->status}\n";
    echo "   Database: {$tenant->db_name}\n\n";

    echo "⏳ Tenant creation job has been dispatched to the queue.\n";
    echo "   To process the job, run: php artisan queue:work\n\n";

    echo "📊 You can check the status using:\n";
    echo "   GET /api/admin/tenants/{$tenant->id}/status\n\n";

    echo "🔍 Monitor the queue with:\n";
    echo "   php artisan queue:monitor\n";
    echo "   php artisan queue:failed\n\n";

    echo "📧 Admins will receive email notifications about the creation status.\n\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "✨ Test completed!\n";

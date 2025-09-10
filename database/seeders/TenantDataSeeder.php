<?php

namespace Database\Seeders;

use App\Models\Tenant\User;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Activity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create additional users
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@tenant.com',
            'password' => Hash::make('password123'),
        ]);
        $manager->assignRole('manager');

        $salesRep = User::create([
            'name' => 'Sales Representative',
            'email' => 'sales@tenant.com',
            'password' => Hash::make('password123'),
        ]);
        $salesRep->assignRole('sales_rep');

        // Create contacts
        $contacts = [
            [
                'user_id' => $manager->id,
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'phone' => '+1234567890',
                'company' => 'Tech Corp',
                'status' => 'active'
            ],
            [
                'user_id' => $salesRep->id,
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'phone' => '+1234567891',
                'company' => 'Business Inc',
                'status' => 'lead'
            ],
            [
                'user_id' => $manager->id,
                'name' => 'Carol Davis',
                'email' => 'carol@example.com',
                'phone' => '+1234567892',
                'company' => 'Enterprise Ltd',
                'status' => 'active'
            ]
        ];

        foreach ($contacts as $contactData) {
            Contact::create($contactData);
        }

        // Create deals
        $deals = [
            [
                'user_id' => $manager->id,
                'contact_id' => 1,
                'title' => 'Enterprise Software License',
                'description' => 'Annual license for enterprise software package',
                'value' => 50000.00,
                'status' => 'open',
                'probability' => 75,
                'expected_close_date' => now()->addDays(30)
            ],
            [
                'user_id' => $salesRep->id,
                'contact_id' => 2,
                'title' => 'Consulting Services',
                'description' => '6-month consulting engagement',
                'value' => 25000.00,
                'status' => 'won',
                'probability' => 100,
                'expected_close_date' => now()->subDays(5)
            ],
            [
                'user_id' => $manager->id,
                'contact_id' => 3,
                'title' => 'Cloud Migration Project',
                'description' => 'Complete cloud infrastructure migration',
                'value' => 100000.00,
                'status' => 'open',
                'probability' => 50,
                'expected_close_date' => now()->addDays(60)
            ]
        ];

        foreach ($deals as $dealData) {
            Deal::create($dealData);
        }

        // Create activities
        $activities = [
            [
                'user_id' => $manager->id,
                'contact_id' => 1,
                'deal_id' => 1,
                'type' => 'meeting',
                'title' => 'Initial Discovery Call',
                'description' => 'Discuss requirements and timeline',
                'due_date' => now()->addDays(7),
                'status' => 'pending'
            ],
            [
                'user_id' => $salesRep->id,
                'contact_id' => 2,
                'deal_id' => 2,
                'type' => 'call',
                'title' => 'Follow-up Call',
                'description' => 'Check on project status',
                'due_date' => now()->addDays(3),
                'status' => 'completed'
            ],
            [
                'user_id' => $manager->id,
                'contact_id' => 3,
                'deal_id' => 3,
                'type' => 'email',
                'title' => 'Send Proposal',
                'description' => 'Send detailed proposal and pricing',
                'due_date' => now()->addDays(2),
                'status' => 'pending'
            ]
        ];

        foreach ($activities as $activityData) {
            Activity::create($activityData);
        }
    }
}

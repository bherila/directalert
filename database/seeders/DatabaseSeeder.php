<?php

namespace Database\Seeders;

use App\Models\DirectAlert;
use App\Models\DirectAlertHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for local development.
     *
     * Sample account numbers/names are documented in README.md under
     * "Local Development" - keep the two in sync if you change this file.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Ben Herila',
            'email' => 'ben@herila.net',
            'role' => 'admin',
        ]);

        // Freshly imported, no contact info yet - the state every row is in
        // right after an admin CSV import, before the citizen self-registers.
        DirectAlert::factory()->create([
            'account_number' => '1000001',
            'account_name' => 'SMITH, JOHN',
            'zip_code' => '08830',
        ]);

        // Same last name as above, different account_number/person - exercises
        // that verification matches on account_number first, not just name.
        DirectAlert::factory()->create([
            'account_number' => '1000007',
            'account_name' => 'SMITH, ROBERT',
            'zip_code' => '08830',
        ]);

        // Fully self-registered: email + all phones + every opt-in enabled.
        DirectAlert::factory()->create([
            'account_number' => '1000002',
            'account_name' => 'DOE, JANE',
            'zip_code' => '08831',
            'email' => 'jane.doe@example.com',
            'home_phone' => '7325551002',
            'work_phone' => '7325551102',
            'cell_phone' => '7325551202',
            'alternate_phone' => '7325551302',
            'optin_emergency_email' => now(),
            'optin_home_call' => now(),
            'optin_work_call' => now(),
            'optin_cell_call' => now(),
            'optin_cell_sms' => now(),
        ]);

        // Partially registered: only an email on file.
        DirectAlert::factory()->create([
            'account_number' => '1000003',
            'account_name' => 'GARCIA, MARIA',
            'zip_code' => '08817',
            'email' => 'maria.garcia@example.com',
            'optin_emergency_email' => now(),
        ]);

        // Commercial/organization account - account_name isn't always a person.
        DirectAlert::factory()->create([
            'account_number' => '1000004',
            'account_name' => 'ACME PROPERTY MANAGEMENT LLC',
            'zip_code' => '08901',
            'email' => 'billing@acme-example.com',
            'optin_emergency_email' => now(),
        ]);

        // Cell-only registration (no email, no landline), SMS opt-in.
        DirectAlert::factory()->create([
            'account_number' => '1000005',
            'account_name' => 'PATEL, RAJESH',
            'zip_code' => '08854',
            'cell_phone' => '7325551205',
            'optin_cell_sms' => now(),
        ]);

        // Already exported and purged: exported_at is set but contact info has
        // since been cleared - the state a row is in after the on-demand
        // "purge exported contact info" admin action runs.
        $purged = DirectAlert::factory()->create([
            'account_number' => '1000006',
            'account_name' => 'KOWALSKI, ANNA',
            'zip_code' => '08820',
            'exported_at' => now()->subWeek(),
        ]);

        // A history snapshot, as if Anna's contact info had changed once
        // before being purged - exercises the direct_alert_history table.
        DirectAlertHistory::factory()->create([
            'account_number' => $purged->account_number,
            'account_name' => $purged->account_name,
            'zip_code' => $purged->zip_code,
            'email' => 'anna.kowalski.old@example.com',
        ]);
    }
}

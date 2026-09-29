<?php

namespace Tests\Feature;

use App\Models\DirectAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectAlertDumpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_pending_2fa_cannot_export_csv(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->generateTwoFactorCode();

        $this->actingAs($user);

        $response = $this->post('/api/admin/export/csv', [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->toIso8601String(),
        ]);

        $response->assertRedirect(route('verify.index'));
    }

    public function test_admin_who_completed_2fa_can_export_csv(): void
    {
        DirectAlert::factory()->create([
            'account_number' => '5559999',
            'email' => 'citizen@example.com',
        ]);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $response = $this->post('/api/admin/export/csv', [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_export_reports_the_emergency_email_optin_flag(): void
    {
        DirectAlert::factory()->create([
            'account_number' => '5559998',
            'email' => 'citizen@example.com',
            'optin_emergency_email' => now(),
        ]);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $response = $this->post('/api/admin/export/csv', [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ]);

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('5559998', $csv);

        $rows = array_map('str_getcsv', explode("\n", trim($csv)));
        $dataRow = collect($rows)->first(fn ($row) => ($row[1] ?? null) === '5559998');

        $this->assertNotNull($dataRow);
        $this->assertSame('yes', $dataRow[5]); // wantEmail column
    }
}

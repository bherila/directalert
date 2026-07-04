<?php

namespace Tests\Feature;

use App\Models\DirectAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectAlertPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_stamps_exported_at_on_matched_rows(): void
    {
        $account = DirectAlert::factory()->create(['email' => 'citizen@example.com']);
        $this->assertNull($account->exported_at);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->post('/api/admin/export/csv', [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ]);

        $this->assertNotNull($account->fresh()->exported_at);
    }

    public function test_purge_only_clears_contact_info_for_exported_rows_in_range(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $exportedInRange = DirectAlert::factory()->create([
            'email' => 'a@example.com',
            'cell_phone' => '5551234567',
            'exported_at' => now()->subDays(2),
        ]);

        $exportedOutOfRange = DirectAlert::factory()->create([
            'email' => 'b@example.com',
            'exported_at' => now()->subDays(30),
        ]);

        $neverExported = DirectAlert::factory()->create([
            'email' => 'c@example.com',
            'exported_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/admin/purge-contact-info', [
            'purge_start' => now()->subDays(3)->toIso8601String(),
            'purge_end' => now()->toIso8601String(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $exportedInRange->refresh();
        $this->assertNull($exportedInRange->email);
        $this->assertNull($exportedInRange->cell_phone);
        // identity fields untouched
        $this->assertNotNull($exportedInRange->account_number);
        $this->assertNotNull($exportedInRange->account_name);

        $this->assertSame('b@example.com', $exportedOutOfRange->fresh()->email);
        $this->assertSame('c@example.com', $neverExported->fresh()->email);
    }

    public function test_purge_requires_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post('/admin/purge-contact-info', [
            'purge_start' => now()->subDay()->toIso8601String(),
            'purge_end' => now()->toIso8601String(),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_purge_logs_action_via_audit_service(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        DirectAlert::factory()->create(['exported_at' => now()->subDay()]);

        $this->actingAs($user)->post('/admin/purge-contact-info', [
            'purge_start' => now()->subDays(2)->toIso8601String(),
            'purge_end' => now()->toIso8601String(),
        ]);

        $this->assertDatabaseHas('admin_audit_log', [
            'action' => 'purge',
            'was_successful' => true,
            'records_affected' => 1,
        ]);
    }
}

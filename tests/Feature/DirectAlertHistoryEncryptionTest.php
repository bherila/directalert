<?php

namespace Tests\Feature;

use App\Models\DirectAlert;
use App\Models\DirectAlertHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DirectAlertHistoryEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_contact_info_snapshots_encrypted_history(): void
    {
        $account = DirectAlert::factory()->create([
            'account_number' => '5551234',
            'account_name' => 'DOE, JANE',
            'email' => 'old@example.com',
        ]);

        $cookie = json_encode([
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
        ]);

        $this->withCookie('current_account', $cookie)->post('/update-information', [
            'email' => 'new@example.com',
            'home_phone' => '',
            'work_phone' => '',
            'cell_phone' => '',
        ]);

        $this->assertSame('new@example.com', $account->fresh()->email);

        $history = DirectAlertHistory::firstOrFail();
        $this->assertSame('old@example.com', $history->email);
        $this->assertSame('DOE, JANE', $history->account_name);
        $this->assertSame('5551234', $history->account_number);

        $raw = DB::table('direct_alert_history')->first();
        $this->assertNotSame('old@example.com', $raw->email);
        $this->assertNotSame('5551234', $raw->account_number);
    }
}

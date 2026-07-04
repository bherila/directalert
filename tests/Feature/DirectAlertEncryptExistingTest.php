<?php

namespace Tests\Feature;

use App\Models\DirectAlert;
use App\Models\DirectAlertHistory;
use App\Support\DirectAlertCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DirectAlertEncryptExistingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_encrypts_existing_plaintext_rows(): void
    {
        DB::table('direct_alert')->insert([
            'account_number' => '5551234',
            'account_name' => 'DOE, JANE',
            'zip_code' => '08830',
            'email' => 'jane@example.com',
            'cell_phone' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('direct-alert:encrypt-existing', ['--force' => true])->assertSuccessful();

        $raw = DB::table('direct_alert')->first();
        $this->assertNotSame('5551234', $raw->account_number);
        $this->assertNotSame('DOE, JANE', $raw->account_name);
        $this->assertNotSame('jane@example.com', $raw->email);
        $this->assertNull($raw->cell_phone);
        $this->assertSame(DirectAlertCrypto::blindIndex('5551234'), $raw->account_number_hash);

        $account = DirectAlert::first();
        $this->assertSame('5551234', $account->account_number);
        $this->assertSame('DOE, JANE', $account->account_name);
        $this->assertSame('jane@example.com', $account->email);
    }

    public function test_it_skips_already_processed_rows(): void
    {
        $account = DirectAlert::factory()->create(['account_number' => '5551234']);
        $originalCiphertext = DB::table('direct_alert')->where('id', $account->id)->value('account_number');

        $this->artisan('direct-alert:encrypt-existing', ['--force' => true])->assertSuccessful();

        $this->assertSame($originalCiphertext, DB::table('direct_alert')->where('id', $account->id)->value('account_number'));
    }

    public function test_it_encrypts_existing_history_rows(): void
    {
        DB::table('direct_alert_history')->insert([
            'account_number' => '5559999',
            'account_name' => 'ROE, RICHARD',
            'zip_code' => '08830',
            'email' => 'old@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('direct-alert:encrypt-existing', ['--force' => true])->assertSuccessful();

        $history = DirectAlertHistory::first();
        $this->assertSame('5559999', $history->account_number);
        $this->assertSame('ROE, RICHARD', $history->account_name);
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        DB::table('direct_alert')->insert([
            'account_number' => '5551234',
            'account_name' => 'DOE, JANE',
            'zip_code' => '08830',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('direct-alert:encrypt-existing', ['--force' => true])->assertSuccessful();
        $this->artisan('direct-alert:encrypt-existing', ['--force' => true])->assertSuccessful();

        $this->assertSame('5551234', DirectAlert::first()->account_number);
    }
}

<?php

namespace Tests\Feature;

use App\Models\DirectAlert;
use App\Models\User;
use App\Support\DirectAlertCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function csvFile(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $contents);
    }

    public function test_import_stores_encrypted_data_and_a_matching_blind_index(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $csv = "PERSON,ACCT_ID,POSTAL\n\"DOE, JANE\",5551234,08830\n";

        $response = $this->post('/admin/import', ['csv_file' => $this->csvFile($csv)]);

        $response->assertOk();

        $raw = DB::table('direct_alert')->where('account_number_hash', DirectAlertCrypto::blindIndex('5551234'))->first();

        $this->assertNotNull($raw);
        $this->assertNotSame('5551234', $raw->account_number);
        $this->assertNotSame('DOE, JANE', $raw->account_name);

        $account = DirectAlert::where('account_number_hash', DirectAlertCrypto::blindIndex('5551234'))->firstOrFail();
        $this->assertSame('5551234', $account->account_number);
        $this->assertSame('DOE, JANE', $account->account_name);
        $this->assertSame('08830', $account->zip_code);
    }

    public function test_import_results_page_shows_plaintext_not_ciphertext(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $csv = "PERSON,ACCT_ID,POSTAL\n\"DOE, JANE\",5551234,08830\n";

        $response = $this->post('/admin/import', ['csv_file' => $this->csvFile($csv)]);

        $response->assertSeeText('DOE, JANE');
        $response->assertSeeText('5551234');
    }

    public function test_duplicate_account_number_is_skipped_on_reimport(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $csv = "PERSON,ACCT_ID,POSTAL\n\"DOE, JANE\",5551234,08830\n";

        $this->post('/admin/import', ['csv_file' => $this->csvFile($csv)]);
        $this->assertSame(1, DirectAlert::count());

        $response = $this->post('/admin/import', ['csv_file' => $this->csvFile($csv)]);

        $this->assertSame(1, DirectAlert::count());
        $response->assertSeeText('Skipped Duplicate Records (1)');
    }

    public function test_intra_file_duplicate_account_numbers_do_not_abort_the_whole_import(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Multi-person utility accounts commonly repeat an account number.
        $csv = "PERSON,ACCT_ID,POSTAL\n\"DOE, JANE\",5551234,08830\n\"DOE, JOHN\",5551234,08830\n\"ROE, RICHARD\",5559999,08831\n";

        $response = $this->post('/admin/import', ['csv_file' => $this->csvFile($csv)]);

        $response->assertOk();
        $this->assertSame(2, DirectAlert::count());
        $response->assertSeeText('Skipped Duplicate Records (1)');
        $response->assertSeeText('Successfully Imported Records (2)');
    }
}

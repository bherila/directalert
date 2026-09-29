<?php

namespace App\Console\Commands;

use App\Support\DirectAlertCrypto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DirectAlertEncryptExisting extends Command
{
    protected $signature = 'direct-alert:encrypt-existing
        {--chunk=500 : Number of rows to process per batch}
        {--force : Skip the confirmation prompt}';

    protected $description = 'One-time backfill: encrypt existing plaintext direct_alert/direct_alert_history rows and populate their blind-index hash. Safe to re-run - already-processed rows (account_number_hash already set) are skipped.';

    /**
     * @var array<int, string>
     */
    private array $encryptedColumns = [
        'cell_phone',
        'home_phone',
        'work_phone',
        'alternate_phone',
        'email',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'This will encrypt every not-yet-processed row in direct_alert and direct_alert_history in place. Make sure you have a verified backup first. Continue?'
        )) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        if (! $this->verifyExistingHashesAreConsistent()) {
            $this->error(
                'Found already-processed rows whose stored account_number_hash does not match '.
                'DirectAlertCrypto::blindIndex(decrypted account_number) on this environment. '.
                'This usually means APP_KEY or DIRECT_ALERT_BLIND_INDEX_PEPPER here does not match '.
                'whatever encrypted the existing data - aborting before writing anything.'
            );

            return self::FAILURE;
        }

        foreach (['direct_alert', 'direct_alert_history'] as $table) {
            $this->encryptTable($table);
        }

        return self::SUCCESS;
    }

    /**
     * Guard against running with the wrong APP_KEY/pepper for this data (the
     * exact mistake that broke production once already this session: running
     * the backfill from an environment whose APP_KEY didn't match the
     * deployed app's, silently producing undecryptable ciphertext). Sample
     * one already-processed row (if any exist) and confirm decrypting +
     * re-hashing it reproduces the stored hash.
     */
    private function verifyExistingHashesAreConsistent(): bool
    {
        foreach (['direct_alert', 'direct_alert_history'] as $table) {
            $sample = DB::table($table)->whereNotNull('account_number_hash')->first();

            if (! $sample) {
                continue;
            }

            try {
                $decrypted = DirectAlertCrypto::decryptAccountNumber($sample->account_number);
            } catch (\Throwable $e) {
                $this->error("{$table}: could not decrypt an already-processed row (id={$sample->id}): {$e->getMessage()}");

                return false;
            }

            if (! hash_equals($sample->account_number_hash, DirectAlertCrypto::blindIndex($decrypted))) {
                $this->error("{$table}: blind index mismatch on already-processed row (id={$sample->id}).");

                return false;
            }
        }

        return true;
    }

    private function encryptTable(string $table): void
    {
        $chunkSize = (int) $this->option('chunk');
        $total = DB::table($table)->whereNull('account_number_hash')->count();

        if ($total === 0) {
            $this->info("{$table}: nothing to do (no rows with a null account_number_hash).");

            return;
        }

        $this->info("{$table}: encrypting {$total} row(s)...");
        $bar = $this->output->createProgressBar($total);
        $processed = 0;

        DB::table($table)
            ->whereNull('account_number_hash')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($table, $bar, &$processed) {
                $skipped = $rows->filter(fn ($row) => $row->account_number === null || $row->account_name === null);

                foreach ($skipped as $row) {
                    $this->warn("\n{$table}: skipping row id={$row->id} - null account_number or account_name.");
                }

                $updates = $rows->reject(fn ($row) => $skipped->contains('id', $row->id))
                    ->map(fn ($row) => $this->buildRowUpdate($row))
                    ->all();

                if (! empty($updates)) {
                    // A single INSERT ... ON DUPLICATE KEY UPDATE per chunk (one
                    // round trip) instead of one UPDATE per row - the latter was
                    // ~90ms/row over the network to the remote MySQL host, which
                    // would have taken hours for 260k+ rows.
                    DB::table($table)->upsert(
                        $updates,
                        ['id'],
                        ['account_number_hash', 'account_number', 'account_name', ...$this->encryptedColumns]
                    );
                }

                $processed += $rows->count();
                $bar->advance($rows->count());
            });

        $bar->finish();
        $this->newLine();
        $this->info("{$table}: done ({$processed} row(s) encrypted).");
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRowUpdate(object $row): array
    {
        $update = [
            'id' => $row->id,
            'account_number_hash' => DirectAlertCrypto::blindIndex($row->account_number),
            'account_number' => DirectAlertCrypto::encryptAccountNumber($row->account_number),
            'account_name' => DirectAlertCrypto::encryptBoundName($row->account_number, $row->account_name),
        ];

        foreach ($this->encryptedColumns as $column) {
            $update[$column] = $row->{$column} !== null && $row->{$column} !== ''
                ? DirectAlertCrypto::encryptString($row->{$column})
                : $row->{$column};
        }

        return $update;
    }
}

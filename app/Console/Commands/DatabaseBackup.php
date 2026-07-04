<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup {--path= : Directory to write the backup file to}';

    protected $description = 'Create a compressed mysqldump backup of the configured database connection';

    public function handle(): int
    {
        $connection = config('database.default');
        $connectionConfig = config("database.connections.{$connection}");

        if (($connectionConfig['driver'] ?? null) !== 'mysql') {
            $this->error("db:backup only supports the mysql driver, connection [{$connection}] uses [{$connectionConfig['driver']}].");

            return self::FAILURE;
        }

        $directory = rtrim($this->option('path') ?: storage_path('app/backups'), '/');
        File::ensureDirectoryExists($directory);

        $baseName = sprintf('%s_%s', $connectionConfig['database'], now()->format('Ymd_His'));
        $sqlPath = "{$directory}/{$baseName}.sql";
        $gzPath = "{$sqlPath}.gz";

        $credentialsFile = $this->writeCredentialsFile($connectionConfig);

        $this->info("Backing up database [{$connectionConfig['database']}] from [{$connectionConfig['host']}] ...");

        try {
            $dump = new Process([
                'mysqldump',
                '--defaults-extra-file='.$credentialsFile,
                '--single-transaction',
                '--quick',
                '--routines',
                '--set-gtid-purged=OFF',
                '--result-file='.$sqlPath,
                $connectionConfig['database'],
            ]);
            $dump->setTimeout(3600);
            $dump->run();

            if (! $dump->isSuccessful()) {
                $this->error('mysqldump failed: '.$dump->getErrorOutput());

                return self::FAILURE;
            }

            $gzip = new Process(['gzip', '-f', $sqlPath]);
            $gzip->setTimeout(600);
            $gzip->run();

            if (! $gzip->isSuccessful()) {
                $this->error('gzip failed: '.$gzip->getErrorOutput());

                return self::FAILURE;
            }
        } finally {
            @unlink($credentialsFile);
        }

        $this->info(sprintf('Backup complete: %s (%s)', $gzPath, $this->formatBytes(filesize($gzPath))));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $connectionConfig
     */
    private function writeCredentialsFile(array $connectionConfig): string
    {
        $credentialsFile = tempnam(sys_get_temp_dir(), 'db-backup-');

        file_put_contents($credentialsFile, sprintf(
            "[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
            $connectionConfig['host'],
            $connectionConfig['port'],
            $connectionConfig['username'],
            $connectionConfig['password']
        ));
        chmod($credentialsFile, 0600);

        return $credentialsFile;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return sprintf('%.2f %s', $bytes / (1024 ** $power), $units[$power]);
    }
}

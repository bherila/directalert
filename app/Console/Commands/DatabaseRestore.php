<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DatabaseRestore extends Command
{
    protected $signature = 'db:restore {path : Path to a .sql or .sql.gz backup file} {--force : Skip the confirmation prompt}';

    protected $description = 'Restore the configured database connection from a mysqldump backup file';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_string($path) || ! file_exists($path)) {
            $this->error("Backup file not found: {$path}");

            return self::FAILURE;
        }

        $connection = config('database.default');
        $connectionConfig = config("database.connections.{$connection}");

        if (($connectionConfig['driver'] ?? null) !== 'mysql') {
            $this->error("db:restore only supports the mysql driver, connection [{$connection}] uses [{$connectionConfig['driver']}].");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            "This will OVERWRITE all data in database [{$connectionConfig['database']}] on host [{$connectionConfig['host']}] with the contents of {$path}. Continue?"
        )) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $credentialsFile = $this->writeCredentialsFile($connectionConfig);
        $decompressedPath = null;
        $sqlPath = $path;

        try {
            if (str_ends_with($path, '.gz')) {
                $decompressedPath = $this->decompress($path);
                $sqlPath = $decompressedPath;
            }

            $this->info("Restoring database [{$connectionConfig['database']}] from {$path} ...");

            $handle = fopen($sqlPath, 'rb');

            $restore = new Process([
                'mysql',
                '--defaults-extra-file='.$credentialsFile,
                $connectionConfig['database'],
            ]);
            $restore->setInput($handle);
            $restore->setTimeout(3600);
            $restore->run();

            fclose($handle);

            if (! $restore->isSuccessful()) {
                $this->error('mysql restore failed: '.$restore->getErrorOutput());

                return self::FAILURE;
            }
        } finally {
            @unlink($credentialsFile);
            if ($decompressedPath) {
                @unlink($decompressedPath);
            }
        }

        $this->info('Restore complete.');

        return self::SUCCESS;
    }

    private function decompress(string $gzPath): string
    {
        $destination = tempnam(sys_get_temp_dir(), 'db-restore-').'.sql';

        $gz = gzopen($gzPath, 'rb');
        $out = fopen($destination, 'wb');

        while (! gzeof($gz)) {
            fwrite($out, gzread($gz, 1024 * 1024));
        }

        gzclose($gz);
        fclose($out);

        return $destination;
    }

    /**
     * @param  array<string, mixed>  $connectionConfig
     */
    private function writeCredentialsFile(array $connectionConfig): string
    {
        $credentialsFile = tempnam(sys_get_temp_dir(), 'db-restore-');

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
}

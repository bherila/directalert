<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns that will hold encrypted values and need to be widened from
     * varchar(255) to text (ciphertext is much longer than the plaintext).
     *
     * @var array<int, string>
     */
    private array $encryptedColumns = [
        'account_name',
        'account_number',
        'cell_phone',
        'home_phone',
        'work_phone',
        'alternate_phone',
        'email',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->hasIndex('direct_alert', 'direct_alert_account_number_account_name_unique')) {
            Schema::table('direct_alert', function (Blueprint $table) {
                $table->dropUnique(['account_number', 'account_name']);
            });
        }

        // SQLite has no real column length limit (type affinity only, no MODIFY
        // syntax), so this widening is only meaningful - and only valid - on MySQL.
        if (DB::connection()->getDriverName() === 'mysql') {
            $this->widenColumns('TEXT');
        }

        Schema::table('direct_alert', function (Blueprint $table) {
            if (! Schema::hasColumn('direct_alert', 'account_number_hash')) {
                $table->string('account_number_hash', 64)->nullable()->after('account_number');
            }
            if (! Schema::hasColumn('direct_alert', 'exported_at')) {
                $table->timestamp('exported_at')->nullable()->after('updated_at');
            }
        });

        if (! $this->hasIndex('direct_alert', 'direct_alert_account_number_hash_unique')) {
            Schema::table('direct_alert', function (Blueprint $table) {
                $table->unique('account_number_hash');
            });
        }

        Schema::table('direct_alert_history', function (Blueprint $table) {
            if (! Schema::hasColumn('direct_alert_history', 'account_number_hash')) {
                $table->string('account_number_hash', 64)->nullable()->after('account_number');
            }
        });

        if (! $this->hasIndex('direct_alert_history', 'direct_alert_history_account_number_hash_index')) {
            Schema::table('direct_alert_history', function (Blueprint $table) {
                $table->index('account_number_hash');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))->pluck('name')->contains($indexName);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_alert', function (Blueprint $table) {
            $table->dropUnique(['account_number_hash']);
            $table->dropColumn(['account_number_hash', 'exported_at']);
        });

        Schema::table('direct_alert_history', function (Blueprint $table) {
            $table->dropIndex(['account_number_hash']);
            $table->dropColumn('account_number_hash');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->widenColumns('VARCHAR(255)');
        }

        Schema::table('direct_alert', function (Blueprint $table) {
            $table->unique(['account_number', 'account_name']);
        });
    }

    /**
     * Apply a MODIFY of all encrypted-column-to-be types in a single ALTER
     * TABLE per table (one full table rebuild instead of one per column).
     *
     * Some existing rows have a legacy '0000-00-00 00:00:00' created_at,
     * which a MODIFY-triggered table rebuild validates under the active
     * sql_mode even though created_at isn't one of the altered columns -
     * relax it for just this statement so the rebuild isn't blocked by
     * pre-existing, unrelated data.
     */
    private function widenColumns(string $type): void
    {
        $originalSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = ''");

        try {
            foreach (['direct_alert', 'direct_alert_history'] as $tableName) {
                $modifications = collect($this->encryptedColumns)
                    ->map(fn (string $column) => "MODIFY `{$column}` {$type} NULL")
                    ->implode(', ');

                DB::statement("ALTER TABLE `{$tableName}` {$modifications}");
            }
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$originalSqlMode]);
        }
    }
};

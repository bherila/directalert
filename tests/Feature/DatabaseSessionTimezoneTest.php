<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseSessionTimezoneTest extends TestCase
{
    public function test_application_timezone_is_utc(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_mysql_family_sessions_use_utc(): void
    {
        $checkedConnections = [];

        foreach (config('database.connections') as $name => $connection) {
            if (! in_array($connection['driver'], ['mysql', 'mariadb'], true)) {
                continue;
            }

            $checkedConnections[] = $name;
            $this->assertSame('+00:00', $connection['timezone'] ?? null, sprintf(
                'Connection `%s` must use UTC for TIMESTAMP values and CURRENT_TIMESTAMP defaults.',
                $name,
            ));
        }

        $this->assertNotEmpty($checkedConnections);
    }
}

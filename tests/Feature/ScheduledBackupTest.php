<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduledBackupTest extends TestCase
{
    public function test_db_backup_is_scheduled_daily(): void
    {
        $schedule = app(Schedule::class);

        $matching = collect($schedule->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'db:backup'));

        $this->assertTrue($matching->isNotEmpty(), 'Expected db:backup to be registered on the schedule.');
        $this->assertSame('0 3 * * *', $matching->first()->expression);
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AdminAuditLogService;
use App\Models\AdminAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminAuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminAuditLogService();
    }

    public function test_can_log_successful_action(): void
    {
        $log = $this->service->log(
            action: 'export',
            wasSuccessful: true,
            recordsAffected: 100
        );

        $this->assertInstanceOf(AdminAuditLog::class, $log);
        $this->assertEquals('export', $log->action);
        $this->assertTrue($log->was_successful);
        $this->assertEquals(100, $log->records_affected);
        $this->assertEquals(0, $log->records_skipped);
        $this->assertEquals(0, $log->records_failed);
        $this->assertNull($log->error_message);
    }

    public function test_can_log_failed_action(): void
    {
        $log = $this->service->log(
            action: 'import',
            wasSuccessful: false,
            recordsFailed: 5,
            errorMessage: 'Test error message'
        );

        $this->assertInstanceOf(AdminAuditLog::class, $log);
        $this->assertEquals('import', $log->action);
        $this->assertFalse($log->was_successful);
        $this->assertEquals(5, $log->records_failed);
        $this->assertEquals('Test error message', $log->error_message);
    }

    public function test_can_log_with_all_fields(): void
    {
        $log = $this->service->log(
            action: 'import',
            wasSuccessful: true,
            recordsAffected: 50,
            recordsSkipped: 10,
            recordsFailed: 5
        );

        $this->assertEquals(50, $log->records_affected);
        $this->assertEquals(10, $log->records_skipped);
        $this->assertEquals(5, $log->records_failed);
    }
}

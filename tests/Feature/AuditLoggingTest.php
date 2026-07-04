<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_events_capture_ip_and_user_agent(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->withHeaders(['User-Agent' => 'PHPUnit-Test-Agent'])->post('/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $log = AdminAuditLog::where('action', 'login')->where('was_successful', true)->firstOrFail();

        $this->assertNotNull($log->ip_address);
        $this->assertSame('PHPUnit-Test-Agent', $log->user_agent);
        $this->assertSame($user->id, $log->auth_user_id);
    }

    public function test_failed_login_is_logged_with_attempted_email(): void
    {
        $this->post('/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ]);

        $log = AdminAuditLog::where('action', 'login')->where('was_successful', false)->firstOrFail();

        $this->assertStringContainsString('nobody@example.com', $log->error_message);
        $this->assertNull($log->auth_user_id);
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->post('/auth/logout');

        $log = AdminAuditLog::where('action', 'logout')->firstOrFail();
        $this->assertSame($user->id, $log->auth_user_id);
    }

    public function test_two_factor_verify_success_and_failure_are_logged(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->generateTwoFactorCode();
        $code = $user->two_factor_code;
        $wrongCode = $code === '999999' ? '888888' : '999999';

        $this->actingAs($user)->post(route('verify.store'), ['two_factor_code' => $wrongCode]);
        $this->assertDatabaseHas('admin_audit_log', [
            'action' => 'two_factor_verify',
            'was_successful' => false,
        ]);

        $this->actingAs($user)->post(route('verify.store'), ['two_factor_code' => $code]);
        $this->assertDatabaseHas('admin_audit_log', [
            'action' => 'two_factor_verify',
            'was_successful' => true,
        ]);
    }

    public function test_two_factor_resend_is_logged(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'admin']);
        $user->generateTwoFactorCode();

        $this->actingAs($user)->get(route('verify.resend'));

        $this->assertDatabaseHas('admin_audit_log', [
            'action' => 'two_factor_resend',
            'was_successful' => true,
            'auth_user_id' => $user->id,
        ]);
    }

    public function test_registration_is_logged(): void
    {
        $invite = AdminInvite::factory()->create(['email' => 'newadmin@example.com']);

        $this->post('/auth/register', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invite' => $invite->token,
        ]);

        $user = User::where('email', 'newadmin@example.com')->firstOrFail();

        $this->assertDatabaseHas('admin_audit_log', [
            'action' => 'register',
            'was_successful' => true,
            'auth_user_id' => $user->id,
        ]);
    }
}

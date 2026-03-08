<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SendTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_redirected_to_2fa_page_after_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/export'); // Redirected to intended initially
        
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertNotNull($user->two_factor_code);
        $this->assertNotNull($user->two_factor_expires_at);

        Notification::assertSentTo($user, SendTwoFactorCode::class);

        // Next request should redirect to verify page
        $response = $this->get('/admin/export');
        $response->assertRedirect(route('verify.index'));
    }

    public function test_user_can_verify_2fa_code(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $user->generateTwoFactorCode();
        $code = $user->two_factor_code;

        $this->actingAs($user);

        $response = $this->post(route('verify.store'), [
            'two_factor_code' => $code,
        ]);

        $response->assertRedirect('/admin/export');
        $user->refresh();
        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
    }

    public function test_user_cannot_verify_incorrect_2fa_code(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $user->generateTwoFactorCode();

        $this->actingAs($user);

        $response = $this->post(route('verify.store'), [
            'two_factor_code' => '000000', // Incorrect code
        ]);

        $response->assertSessionHasErrors('two_factor_code');
        $user->refresh();
        $this->assertNotNull($user->two_factor_code);
    }

    public function test_user_can_resend_2fa_code(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $user->generateTwoFactorCode();
        $oldCode = $user->two_factor_code;

        $this->actingAs($user);

        $response = $this->get(route('verify.resend'));

        $response->assertRedirect();
        $response->assertSessionHas('status', __('Code has been sent again'));
        
        $user->refresh();
        $this->assertNotEquals($oldCode, $user->two_factor_code);
        Notification::assertSentTo($user, SendTwoFactorCode::class);
    }

    public function test_expired_2fa_code_logs_user_out(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'two_factor_code' => '123456',
            'two_factor_expires_at' => now()->subMinutes(1),
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/export');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        
        $user->refresh();
        $this->assertNull($user->two_factor_code);
    }
}

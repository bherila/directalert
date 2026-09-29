<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_per_email_and_ip(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('correct-password'),
            'role' => 'admin',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_two_factor_verify_is_rate_limited(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->generateTwoFactorCode();

        $this->actingAs($user);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('verify.store'), ['two_factor_code' => '000000']);
        }

        $response = $this->post(route('verify.store'), ['two_factor_code' => $user->two_factor_code]);

        $response->assertStatus(429);
    }

    public function test_two_factor_resend_is_rate_limited(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->generateTwoFactorCode();

        $this->actingAs($user);

        for ($i = 0; $i < 3; $i++) {
            $this->get(route('verify.resend'));
        }

        $response = $this->get(route('verify.resend'));

        $response->assertStatus(429);
    }

    public function test_two_factor_code_comparison_rejects_non_matching_code(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'two_factor_code' => '123456',
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user);

        $response = $this->post(route('verify.store'), ['two_factor_code' => '654321']);

        $response->assertSessionHasErrors('two_factor_code');
    }
}

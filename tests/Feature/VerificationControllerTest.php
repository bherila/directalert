<?php

namespace Tests\Feature;

use App\Models\DirectAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_wildcard_last_name_does_not_bypass_verification(): void
    {
        DirectAlert::factory()->create([
            'account_number' => '5551234',
            'account_name' => 'DOE, JANE',
        ]);

        $response = $this->post('/verify', [
            'account_number' => '5551234',
            'last_name' => '%',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
        $response->assertCookieMissing('current_account');
    }

    public function test_underscore_last_name_does_not_bypass_verification(): void
    {
        DirectAlert::factory()->create([
            'account_number' => '5551235',
            'account_name' => 'DOE, JANE',
        ]);

        $response = $this->post('/verify', [
            'account_number' => '5551235',
            'last_name' => '_OE',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_correct_last_name_still_verifies_successfully(): void
    {
        DirectAlert::factory()->create([
            'account_number' => '5551236',
            'account_name' => 'DOE, JANE',
        ]);

        $response = $this->post('/verify', [
            'account_number' => '5551236',
            'last_name' => 'DOE',
        ]);

        $response->assertRedirect('/update-information');
        $response->assertCookie('current_account');
    }

    public function test_verify_route_is_rate_limited(): void
    {
        DirectAlert::factory()->create([
            'account_number' => '5551237',
            'account_name' => 'DOE, JANE',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/verify', [
                'account_number' => '5551237',
                'last_name' => 'NOBODY',
            ]);
            $response->assertStatus(302);
        }

        $response = $this->post('/verify', [
            'account_number' => '5551237',
            'last_name' => 'NOBODY',
        ]);

        $response->assertStatus(429);
    }
}

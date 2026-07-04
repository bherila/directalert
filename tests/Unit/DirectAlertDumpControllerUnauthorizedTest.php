<?php

namespace Tests\Unit;

use App\Http\Controllers\DirectAlertDumpController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DirectAlertDumpControllerUnauthorizedTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_response_does_not_leak_user_data(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'someone@example.com',
        ]);
        Auth::login($user);

        $response = (new DirectAlertDumpController)->dumpCsv(new Request);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthorized', $response->getContent());
        $this->assertStringNotContainsString('someone@example.com', $response->getContent());
    }
}

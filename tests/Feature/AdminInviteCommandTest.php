<?php

namespace Tests\Feature;

use App\Models\AdminInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInviteCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_valid_invite(): void
    {
        $this->artisan('admin:invite', ['email' => 'newperson@example.com'])
            ->assertSuccessful();

        $invite = AdminInvite::where('email', 'newperson@example.com')->firstOrFail();

        $this->assertSame('admin', $invite->role);
        $this->assertTrue($invite->isValid());
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->artisan('admin:invite', ['email' => 'not-an-email'])
            ->assertFailed();

        $this->assertDatabaseCount('admin_invites', 0);
    }
}

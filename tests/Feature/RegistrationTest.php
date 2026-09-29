<?php

namespace Tests\Feature;

use App\Models\AdminInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_form_requires_a_valid_invite(): void
    {
        $response = $this->get('/auth/register');

        $response->assertRedirect(route('login'));
    }

    public function test_registration_form_rejects_expired_invite(): void
    {
        $invite = AdminInvite::factory()->expired()->create();

        $response = $this->get('/auth/register?invite='.$invite->token);

        $response->assertRedirect(route('login'));
    }

    public function test_registration_form_rejects_used_invite(): void
    {
        $invite = AdminInvite::factory()->used()->create();

        $response = $this->get('/auth/register?invite='.$invite->token);

        $response->assertRedirect(route('login'));
    }

    public function test_cannot_register_without_a_matching_invite(): void
    {
        $response = $this->post('/auth/register', [
            'name' => 'Eve',
            'email' => 'eve@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invite' => 'not-a-real-token',
        ]);

        $response->assertSessionHasErrors('invite');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'eve@example.com']);
    }

    public function test_cannot_register_with_someone_elses_invite_email(): void
    {
        $invite = AdminInvite::factory()->create(['email' => 'intended@example.com']);

        $response = $this->post('/auth/register', [
            'name' => 'Eve',
            'email' => 'attacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invite' => $invite->token,
        ]);

        $response->assertSessionHasErrors('invite');
        $this->assertGuest();
    }

    public function test_can_register_with_a_valid_invite_and_receives_invites_role(): void
    {
        $invite = AdminInvite::factory()->create([
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);

        $response = $this->post('/auth/register', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invite' => $invite->token,
        ]);

        $response->assertRedirect('/admin/export');
        $this->assertAuthenticated();

        $user = User::where('email', 'newadmin@example.com')->firstOrFail();
        $this->assertSame('admin', $user->role);

        $this->assertNotNull($invite->fresh()->used_at);
    }

    public function test_invite_cannot_be_reused(): void
    {
        $invite = AdminInvite::factory()->create(['email' => 'reuse@example.com']);

        $this->post('/auth/register', [
            'name' => 'First',
            'email' => 'reuse@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invite' => $invite->token,
        ]);

        $this->post('/auth/logout');

        $response = $this->post('/auth/register', [
            'name' => 'Second',
            'email' => 'reuse2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invite' => $invite->token,
        ]);

        $response->assertSessionHasErrors('invite');
        $this->assertDatabaseMissing('users', ['email' => 'reuse2@example.com']);
    }

    public function test_role_cannot_be_mass_assigned_via_registration_payload(): void
    {
        $invite = AdminInvite::factory()->create([
            'email' => 'sneaky@example.com',
            'role' => 'user',
        ]);

        $this->post('/auth/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invite' => $invite->token,
            'role' => 'admin',
        ]);

        $user = User::where('email', 'sneaky@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
    }
}

<?php

namespace Database\Factories;

use App\Models\AdminInvite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminInvite>
 */
class AdminInviteFactory extends Factory
{
    protected $model = AdminInvite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(48),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => 'admin',
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => now()->subDay()]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => ['used_at' => now()]);
    }
}

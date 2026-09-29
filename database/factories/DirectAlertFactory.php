<?php

namespace Database\Factories;

use App\Models\DirectAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DirectAlert>
 */
class DirectAlertFactory extends Factory
{
    protected $model = DirectAlert::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_number' => (string) $this->faker->unique()->numerify('##########'),
            'account_name' => strtoupper($this->faker->lastName()).', '.$this->faker->firstName(),
            'zip_code' => $this->faker->numerify('#####'),
        ];
    }
}

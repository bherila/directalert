<?php

namespace Database\Factories;

use App\Models\DirectAlertHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DirectAlertHistory>
 */
class DirectAlertHistoryFactory extends Factory
{
    protected $model = DirectAlertHistory::class;

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

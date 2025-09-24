<?php

namespace Database\Factories;

use App\Models\TournamentRegistration;
use App\Models\User;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentRegistrationFactory extends Factory
{
    protected $model = TournamentRegistration::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'player_id' => User::factory(),
            'registration_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed']),
            'amount_paid' => $this->faker->randomFloat(2, 10, 100),
            'payment_method' => $this->faker->randomElement(['mpesa', 'card', 'cash']),
            'payment_reference' => $this->faker->uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => 'paid',
                'amount_paid' => $this->faker->randomFloat(2, 50, 200),
            ];
        });
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => 'pending',
            ];
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class PoolMatchFactory extends Factory
{
    protected $model = PoolMatch::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'player_1_id' => User::factory(),
            'player_2_id' => User::factory(),
            'round_name' => 'Round ' . $this->faker->numberBetween(1, 5),
            'level' => $this->faker->randomElement(['community', 'county', 'regional', 'national']),
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed']),
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'player_1_points' => $this->faker->numberBetween(0, 15),
            'player_2_points' => $this->faker->numberBetween(0, 15),
            'winner_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $player1Score = $this->faker->numberBetween(8, 15);
            $player2Score = $this->faker->numberBetween(0, 7);
            
            return [
                'status' => 'completed',
                'player_1_points' => $player1Score,
                'player_2_points' => $player2Score,
                'winner_id' => $player1Score > $player2Score ? $attributes['player_1_id'] : $attributes['player_2_id'],
            ];
        });
    }

    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
                'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days'),
                'player_1_points' => null,
                'player_2_points' => null,
                'winner_id' => null,
            ];
        });
    }
}

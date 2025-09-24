<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'special' => false,
            'community_prize' => $this->faker->numberBetween(500, 2000),
            'county_prize' => $this->faker->numberBetween(1000, 3000),
            'regional_prize' => $this->faker->numberBetween(2000, 5000),
            'national_prize' => $this->faker->numberBetween(5000, 10000),
            'area_scope' => $this->faker->randomElement(['community', 'county', 'region', 'national']),
            'area_name' => $this->faker->city(),
            'tournament_charge' => $this->faker->numberBetween(50, 200),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'registration_deadline' => $this->faker->dateTimeBetween('now', '+3 weeks'),
            'status' => $this->faker->randomElement(['upcoming', 'ongoing', 'completed']),
            'automation_mode' => $this->faker->randomElement(['automatic', 'manual']),
        ];
    }
}

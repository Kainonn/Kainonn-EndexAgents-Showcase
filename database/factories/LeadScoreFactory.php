<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadScore>
 */
class LeadScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'total_score' => fake()->numberBetween(40, 95),
            'urgency_score' => fake()->numberBetween(30, 100),
            'fit_score' => fake()->numberBetween(30, 100),
            'payment_capacity_score' => fake()->numberBetween(30, 100),
            'rationale' => ['reason' => fake()->sentence(8)],
            'scored_by_agent' => 'Minos',
        ];
    }
}

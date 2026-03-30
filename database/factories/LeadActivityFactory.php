<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadActivity>
 */
class LeadActivityFactory extends Factory
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
            'actor_type' => fake()->randomElement(['agent', 'system', 'user']),
            'actor_name' => fake()->optional()->randomElement(['Ananke', 'Argos', 'Jerardo']),
            'event' => fake()->randomElement(['stage.updated', 'finding.created', 'review.completed']),
            'event_data' => ['note' => fake()->sentence(6)],
            'occurred_at' => now(),
        ];
    }
}

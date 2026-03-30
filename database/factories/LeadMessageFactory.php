<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadMessage>
 */
class LeadMessageFactory extends Factory
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
            'channel' => 'email',
            'subject' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
            'tone' => fake()->randomElement(['consultivo', 'directo']),
            'generated_by_agent' => 'Caliope',
            'version' => 1,
        ];
    }
}

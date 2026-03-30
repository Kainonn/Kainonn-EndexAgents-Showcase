<?php

namespace Database\Factories;

use App\Enums\AgentStage;
use App\Models\Lead;
use App\Models\LeadFinding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadFinding>
 */
class LeadFindingFactory extends Factory
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
            'agent_name' => fake()->randomElement(['Argos', 'Tique', 'Minos']),
            'stage' => fake()->randomElement([
                AgentStage::LeadDetection,
                AgentStage::LeadEnrichment,
                AgentStage::LeadScoring,
            ]),
            'summary' => fake()->sentence(10),
            'evidence' => [fake()->sentence(6), fake()->sentence(6)],
            'payload' => ['signal' => fake()->word()],
            'confidence' => fake()->randomFloat(4, 0.6, 0.99),
        ];
    }
}

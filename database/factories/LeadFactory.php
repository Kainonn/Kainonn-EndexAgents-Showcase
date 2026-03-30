<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\CampaignRun;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_run_id' => CampaignRun::factory(),
            'company_name' => fake()->company(),
            'website_url' => fake()->optional()->url(),
            'city' => fake()->city(),
            'sector' => fake()->randomElement(['salud', 'legal', 'retail', 'educacion']),
            'source' => fake()->randomElement(['web_analysis', 'manual', 'directory']),
            'status' => LeadStatus::Detected,
            'commercial_status' => 'new',
            'priority' => fake()->numberBetween(20, 90),
            'latest_confidence' => fake()->randomFloat(4, 0.5, 0.98),
        ];
    }
}

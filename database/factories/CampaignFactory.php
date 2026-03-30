<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $campaignName = sprintf('Campana %s', fake()->company());

        return [
            'name' => $campaignName,
            'slug' => Str::slug($campaignName).'-'.fake()->unique()->numberBetween(100, 999),
            'solution_name' => fake()->randomElement(['EndexCare', 'EndexWeb', 'EndexCRM']),
            'description' => fake()->sentence(16),
            'target_segments' => ['clinicas', 'dentistas', 'consultorios'],
            'target_regions' => ['mx-cdmx', 'mx-gdl'],
            'pain_points' => ['agenda manual', 'seguimiento deficiente'],
            'opportunity_signals' => ['sin sitio responsive', 'sin expediente digital'],
            'allowed_offers' => ['landing_page', 'sitio_corporativo', 'saas'],
            'commercial_tone' => fake()->randomElement(['consultivo', 'directo']),
            'status' => CampaignStatus::Draft,
            'operational_limits' => [
                'max_leads_per_run' => 25,
                'auto_outreach_enabled' => false,
            ],
        ];
    }
}

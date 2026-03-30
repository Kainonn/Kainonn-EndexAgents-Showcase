<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadOffer>
 */
class LeadOfferFactory extends Factory
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
            'offer_type' => fake()->randomElement(['landing_page', 'sitio_corporativo', 'saas']),
            'offer_summary' => fake()->sentence(14),
            'price_range_min' => fake()->numberBetween(300, 2000),
            'price_range_max' => fake()->numberBetween(2001, 15000),
            'justification' => ['fit' => fake()->sentence(8)],
            'recommended_by_agent' => 'Temis',
        ];
    }
}

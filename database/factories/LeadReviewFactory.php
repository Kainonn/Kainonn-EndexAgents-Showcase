<?php

namespace Database\Factories;

use App\Enums\ReviewDecision;
use App\Models\Lead;
use App\Models\LeadReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadReview>
 */
class LeadReviewFactory extends Factory
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
            'reviewed_by_user_id' => User::factory(),
            'decision' => fake()->randomElement([
                ReviewDecision::Approve,
                ReviewDecision::NeedsAdjustment,
            ]),
            'review_notes' => fake()->optional()->sentence(12),
            'adjusted_offer' => null,
            'adjusted_message' => null,
            'reviewed_at' => now(),
        ];
    }
}

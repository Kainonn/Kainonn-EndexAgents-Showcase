<?php

namespace Database\Factories;

use App\Enums\LeadFeedbackType;
use App\Models\Lead;
use App\Models\LeadFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadFeedback>
 */
class LeadFeedbackFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'feedback_type' => fake()->randomElement(LeadFeedbackType::values()),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

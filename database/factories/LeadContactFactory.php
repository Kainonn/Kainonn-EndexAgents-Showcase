<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadContact>
 */
class LeadContactFactory extends Factory
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
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'whatsapp' => fake()->optional()->e164PhoneNumber(),
            'contact_form_url' => fake()->optional()->url(),
            'instagram' => fake()->optional()->url(),
            'facebook' => fake()->optional()->url(),
            'linkedin' => fake()->optional()->url(),
            'contact_name' => fake()->optional()->name(),
            'source_confidence' => fake()->randomFloat(4, 0.5, 0.95),
        ];
    }
}

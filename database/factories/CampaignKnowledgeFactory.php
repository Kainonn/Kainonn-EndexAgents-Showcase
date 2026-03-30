<?php

namespace Database\Factories;

use App\Enums\KnowledgeFileType;
use App\Models\Campaign;
use App\Models\CampaignKnowledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignKnowledge>
 */
class CampaignKnowledgeFactory extends Factory
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
            'title' => fake()->sentence(4),
            'file_name' => fake()->slug().'.md',
            'file_type' => fake()->randomElement([KnowledgeFileType::Md, KnowledgeFileType::Txt]),
            'storage_path' => 'campaigns/'.fake()->numberBetween(1, 999).'/knowledge/'.fake()->uuid().'.md',
            'raw_content' => fake()->paragraphs(3, true),
            'parsed_content' => fake()->paragraphs(2, true),
            'status' => 'active',
        ];
    }
}

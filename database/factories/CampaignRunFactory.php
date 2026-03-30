<?php

namespace Database\Factories;

use App\Enums\CampaignRunStatus;
use App\Models\Campaign;
use App\Models\CampaignRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignRun>
 */
class CampaignRunFactory extends Factory
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
            'triggered_by_user_id' => User::factory(),
            'status' => CampaignRunStatus::Queued,
            'started_at' => null,
            'finished_at' => null,
            'total_leads_analyzed' => 0,
            'total_leads_generated' => 0,
            'error_count' => 0,
            'summary' => null,
        ];
    }
}

<?php

namespace App\Agents\DTOs;

use App\Models\Campaign;
use App\Models\CampaignRun;
use App\Models\Lead;

class AgentContext
{
    /**
     * @param  array<int, array<string, mixed>>  $previousArtifacts
     */
    public function __construct(
        public Campaign $campaign,
        public ?CampaignRun $campaignRun,
        public Lead $lead,
        public string $knowledgeContext = '',
        public array $previousArtifacts = [],
        public ?int $triggeredByUserId = null,
    ) {}
}

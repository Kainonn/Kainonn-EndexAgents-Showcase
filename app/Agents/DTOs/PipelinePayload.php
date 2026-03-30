<?php

namespace App\Agents\DTOs;

class PipelinePayload
{
    public function __construct(
        public int $leadId,
        public ?int $campaignRunId = null,
        public ?int $triggeredByUserId = null,
    ) {}
}

<?php

namespace App\Agents\DTOs;

use App\Enums\AgentStage;

class AgentResult
{
    /**
     * @param  array<int, string>  $evidence
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $agent,
        public string $status,
        public AgentStage $stage,
        public string $summary,
        public array $evidence,
        public float $confidence,
        public string $recommendedAction,
        public array $payload = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agent' => $this->agent,
            'status' => $this->status,
            'stage' => $this->stage->value,
            'summary' => $this->summary,
            'evidence' => $this->evidence,
            'confidence' => $this->confidence,
            'recommended_action' => $this->recommendedAction,
            'payload' => $this->payload,
        ];
    }
}

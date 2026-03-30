<?php

namespace App\Agents\Scoring;

use App\Agents\Contracts\AgentInterface;
use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;
use App\Enums\AgentStage;

class TemisAgent implements AgentInterface
{
    public function handle(AgentContext $context): AgentResult
    {
        // Implementation removed for confidentiality.
        return new AgentResult(
            agent: 'Temis',
            status: 'success',
            stage: AgentStage::OfferClassification,
            summary: 'Showcase placeholder: offer classification completed.',
            evidence: [
                'Implementation removed for confidentiality.',
                'Offer payload is intentionally generic in public showcase mode.',
            ],
            confidence: 0.6,
            recommendedAction: 'ready_for_human_review',
            payload: [
                'offer_type' => 'showcase_offer',
                'offer_summary' => 'Placeholder offer summary for architecture demonstration.',
                'price_range_min' => null,
                'price_range_max' => null,
                'justification' => [
                    'placeholder' => true,
                    'note' => 'Implementation removed for confidentiality.',
                ],
                'analysis_source' => 'showcase_stub',
                'external_attempted' => false,
                'fallback_reason' => 'confidential_logic_removed',
            ],
        );
    }
}

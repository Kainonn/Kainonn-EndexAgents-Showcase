<?php

namespace App\Agents\Prospecting;

use App\Agents\Contracts\AgentInterface;
use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;
use App\Enums\AgentStage;

class TiqueAgent implements AgentInterface
{
    public function handle(AgentContext $context): AgentResult
    {
        // Implementation removed for confidentiality.
        return new AgentResult(
            agent: 'Tique',
            status: 'success',
            stage: AgentStage::LeadEnrichment,
            summary: 'Showcase placeholder: opportunity enrichment completed.',
            evidence: [
                'Implementation removed for confidentiality.',
                'Only the orchestration contract is demonstrated.',
            ],
            confidence: 0.6,
            recommendedAction: 'run_minos_scoring',
            payload: [
                'opportunity_hypothesis' => 'placeholder_hypothesis',
                'sales_hypotheses' => ['placeholder_hypothesis_1', 'placeholder_hypothesis_2'],
                'primary_problem' => 'placeholder_problem',
                'expected_impact' => 'placeholder_impact',
                'talk_track' => 'placeholder_talk_track',
                'analysis_source' => 'showcase_stub',
                'external_attempted' => false,
                'fallback_reason' => 'confidential_logic_removed',
            ],
        );
    }
}

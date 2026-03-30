<?php

namespace App\Agents\Prospecting;

use App\Agents\Contracts\AgentInterface;
use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;
use App\Enums\AgentStage;

class ArgosAgent implements AgentInterface
{
    public function handle(AgentContext $context): AgentResult
    {
        // Implementation removed for confidentiality.
        $hasWebsite = filled($context->lead->website_url);

        return new AgentResult(
            agent: 'Argos',
            status: 'success',
            stage: AgentStage::LeadDetection,
            summary: 'Showcase placeholder: lead discovery stage completed.',
            evidence: [
                'Implementation removed for confidentiality.',
                'This stage preserves interface and orchestration flow only.',
            ],
            confidence: 0.6,
            recommendedAction: 'run_hefesto_web_audit',
            payload: [
                'has_website' => $hasWebsite,
                'website_url' => $context->lead->website_url,
                'detected_signals' => ['showcase_placeholder_signal'],
                'is_real_business' => true,
                'is_active_business' => $hasWebsite,
                'commercial_fit' => 'showcase',
                'should_continue_pipeline' => true,
                'priority_reason' => 'placeholder_decision',
                'activity_signals' => ['placeholder_activity'],
                'maps' => null,
                'analysis_source' => 'showcase_stub',
                'external_attempted' => false,
                'fallback_reason' => 'confidential_logic_removed',
            ],
        );
    }
}

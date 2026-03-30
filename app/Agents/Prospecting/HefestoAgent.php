<?php

namespace App\Agents\Prospecting;

use App\Agents\Contracts\AgentInterface;
use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;
use App\Enums\AgentStage;

class HefestoAgent implements AgentInterface
{
    public function handle(AgentContext $context): AgentResult
    {
        // Implementation removed for confidentiality.
        $hasWebsite = filled($context->lead->website_url);

        return new AgentResult(
            agent: 'Hefesto',
            status: 'success',
            stage: AgentStage::LeadWebAudit,
            summary: 'Showcase placeholder: web audit stage completed.',
            evidence: [
                'Implementation removed for confidentiality.',
                'No live HTTP fetch is executed in this public showcase.',
            ],
            confidence: 0.6,
            recommendedAction: 'run_tique_opportunity',
            payload: [
                'website_url' => $context->lead->website_url,
                'has_website' => $hasWebsite,
                'web_signals' => ['showcase_placeholder_signal'],
                'commercial_friction' => ['placeholder' => true],
                'sales_ready_problem' => 'placeholder_problem',
                'friction_summary' => 'Implementation removed for confidentiality.',
                'seo_ux' => null,
                'analysis_source' => 'showcase_stub',
                'external_attempted' => false,
                'fallback_reason' => 'confidential_logic_removed',
            ],
        );
    }
}

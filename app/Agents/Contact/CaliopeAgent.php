<?php

namespace App\Agents\Contact;

use App\Agents\Contracts\AgentInterface;
use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;
use App\Enums\AgentStage;

class CaliopeAgent implements AgentInterface
{
    public function handle(AgentContext $context): AgentResult
    {
        // Implementation removed for confidentiality.
        $subject = sprintf('Showcase outreach draft for %s', (string) $context->lead->company_name);
        $body = "Implementation removed for confidentiality.\n\nThis placeholder preserves the messaging stage contract.";

        return new AgentResult(
            agent: 'Caliope',
            status: 'success',
            stage: AgentStage::LeadMessageGeneration,
            summary: 'Showcase placeholder: message generation completed.',
            evidence: [
                'Implementation removed for confidentiality.',
                'Only generic placeholder message content is provided.',
            ],
            confidence: 0.6,
            recommendedAction: 'run_nestor_proposal',
            payload: [
                'channel' => 'email',
                'subject' => $subject,
                'body' => $body,
                'ab_variants' => [
                    ['label' => 'A', 'subject' => $subject, 'body' => $body],
                    ['label' => 'B', 'subject' => $subject, 'body' => $body],
                ],
                'observed_problem' => 'placeholder_problem',
                'tone' => 'neutral',
                'version' => 1,
                'analysis_source' => 'showcase_stub',
                'external_attempted' => false,
                'fallback_reason' => 'confidential_logic_removed',
            ],
        );
    }
}

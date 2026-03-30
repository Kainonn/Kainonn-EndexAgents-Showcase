<?php

namespace App\Agents\Contact;

use App\Agents\Contracts\AgentInterface;
use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;
use App\Enums\AgentStage;

class HermesAgent implements AgentInterface
{
    public function handle(AgentContext $context): AgentResult
    {
        // Implementation removed for confidentiality.
        return new AgentResult(
            agent: 'Hermes',
            status: 'success',
            stage: AgentStage::LeadContactExtraction,
            summary: 'Showcase placeholder: contact extraction completed.',
            evidence: [
                'Implementation removed for confidentiality.',
                'No real contact extraction or ranking is executed.',
            ],
            confidence: 0.6,
            recommendedAction: 'run_caliope_message',
            payload: [
                'email' => null,
                'phone' => null,
                'whatsapp' => null,
                'contact_name' => (string) $context->lead->company_name,
                'channel' => 'form',
                'email_quality' => 'unknown',
                'channel_priority' => ['form'],
                'source_confidence' => 0.5,
                'analysis_source' => 'showcase_stub',
                'external_attempted' => false,
                'fallback_reason' => 'confidential_logic_removed',
            ],
        );
    }
}

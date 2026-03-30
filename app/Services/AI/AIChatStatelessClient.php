<?php

namespace App\Services\AI;

use App\Agents\DTOs\AgentContext;

class AIChatStatelessClient
{
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    private function setLastError(string $reason): void
    {
        $this->lastError = $reason;
    }

    /** @return array<string, mixed>|null */
    public function analyzeLeadDetection(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /** @return array<string, mixed>|null */
    public function analyzeOpportunity(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /** @return array<string, mixed>|null */
    public function scoreLead(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /** @return array<string, mixed>|null */
    public function generateMessage(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /**
     * @param  array<string, mixed>  $audit
     * @return array<string, mixed>|null
     */
    public function analyzeWebAudit(AgentContext $context, array $audit): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /** @return array<string, mixed>|null */
    public function classifyOffer(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /** @return array<string, mixed>|null */
    public function generateProposal(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /** @return array<string, mixed>|null */
    public function checkCompliance(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }

    /** @return array<string, mixed>|null */
    public function extractContact(AgentContext $context): ?array
    {
        // Implementation removed for confidentiality.
        $this->setLastError('showcase_external_calls_disabled');

        return null;
    }
}

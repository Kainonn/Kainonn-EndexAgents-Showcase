<?php

namespace App\Agents\Scoring;

use App\Agents\Contracts\AgentInterface;
use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;
use App\Enums\AgentStage;

class MinosAgent implements AgentInterface
{
    public function handle(AgentContext $context): AgentResult
    {
        // Implementation removed for confidentiality.
        // This placeholder keeps the scoring stage contract for portfolio demonstrations.
        $hasMinimumData = filled($context->lead->company_name) && filled($context->lead->city);
        $commercialPriority = $hasMinimumData ? 'MEDIO' : 'INCOMPLETO';
        $contactRecommendation = $hasMinimumData ? 'manual_review_then_contact' : 'complete_data_before_contact';
        $urgency = $hasMinimumData ? 60 : 35;
        $fit = $hasMinimumData ? 60 : 35;
        $paymentCapacity = $hasMinimumData ? 60 : 35;
        $total = (int) round(($urgency + $fit + $paymentCapacity) / 3);
        $scoreExplanation = 'Showcase placeholder score generated from minimal lead completeness checks.';

        return new AgentResult(
            agent: 'Minos',
            status: 'success',
            stage: AgentStage::LeadScoring,
            summary: sprintf('Lead clasificado como %s con score %d/100.', $commercialPriority, $total),
            evidence: [
                sprintf('Urgencia estimada: %d.', $urgency),
                sprintf('Ajuste a ICP de campana: %d.', $fit),
                sprintf('Capacidad probable de pago: %d.', $paymentCapacity),
                $scoreExplanation,
            ],
            confidence: 0.86,
            recommendedAction: 'run_temis_classification',
            payload: [
                'total_score' => $total,
                'urgency_score' => $urgency,
                'fit_score' => $fit,
                'payment_capacity_score' => $paymentCapacity,
                'commercial_priority' => $commercialPriority,
                'contact_recommendation' => $contactRecommendation,
                'score_explanation' => $scoreExplanation,
                'rationale' => [
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

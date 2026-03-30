<?php

namespace App\Actions\Commercial;

use App\Enums\OperationalPriority;
use App\Enums\CommercialLeadStatus;
use App\Models\Lead;

class RecalculateLeadPriorityAction
{
    public function execute(Lead $lead): OperationalPriority
    {
        $score = $lead->latestScore?->total_score;
        $channel = $lead->recommended_channel;
        $status = $lead->commercial_status;

        $hasChannel = $channel !== null && $channel->value !== 'none';
        $numericScore = $score !== null ? (float) $score : null;

        // Cerrado o perdido => siempre baja
        if ($status === CommercialLeadStatus::Closed || $status === CommercialLeadStatus::NotInterested) {
            $priority = OperationalPriority::LowPriority;
            $lead->update(['operational_priority' => $priority]);

            return $priority;
        }

        // Follow-up vencido + esperando respuesta => contactar hoy
        if (
            $status === CommercialLeadStatus::WaitingResponse
            && $lead->next_follow_up_at !== null
            && $lead->next_follow_up_at->isPast()
            && $hasChannel
        ) {
            $priority = OperationalPriority::ContactToday;
            $lead->update(['operational_priority' => $priority]);

            return $priority;
        }

        // Score alto + canal + estado accionable => contactar hoy
        $isActionable = in_array($status, [
            CommercialLeadStatus::New,
            CommercialLeadStatus::WaitingResponse,
            CommercialLeadStatus::ReadyToContact,
            CommercialLeadStatus::Analyzed,
        ], true);

        if ($numericScore !== null && $numericScore >= 65 && $hasChannel && $isActionable) {
            $priority = OperationalPriority::ContactToday;
            $lead->update(['operational_priority' => $priority]);

            return $priority;
        }

        // Respondió => contactar hoy (no dejar enfriar)
        if ($status === CommercialLeadStatus::Responded) {
            $priority = OperationalPriority::ContactToday;
            $lead->update(['operational_priority' => $priority]);

            return $priority;
        }

        // Interesado => esta semana, salvo follow-up para hoy/vencido
        if ($status === CommercialLeadStatus::Interested) {
            if ($lead->next_follow_up_at !== null && $lead->next_follow_up_at->lte(now()->endOfDay())) {
                $priority = OperationalPriority::ContactToday;
            } else {
                $priority = OperationalPriority::ThisWeek;
            }

            $lead->update(['operational_priority' => $priority]);

            return $priority;
        }

        // Score medio + canal => esta semana
        if ($numericScore !== null && $numericScore >= 35 && $hasChannel) {
            $priority = OperationalPriority::ThisWeek;
            $lead->update(['operational_priority' => $priority]);

            return $priority;
        }

        $priority = OperationalPriority::LowPriority;
        $lead->update(['operational_priority' => $priority]);

        return $priority;
    }
}

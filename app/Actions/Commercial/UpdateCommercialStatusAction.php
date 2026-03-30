<?php

namespace App\Actions\Commercial;

use App\Enums\CommercialActionType;
use App\Enums\CommercialLeadStatus;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\Prospecto;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UpdateCommercialStatusAction
{
    public function __construct(
        private readonly RegisterCommercialActionAction $registerAction,
        private readonly RecalculateLeadPriorityAction $recalculatePriority,
    ) {}

    public function execute(
        Lead $lead,
        CommercialLeadStatus $toStatus,
        ?int $userId = null,
        ?string $reason = null,
    ): void {
        $fromStatus = $lead->commercial_status ?? CommercialLeadStatus::New;

        if (! CommercialLeadStatus::canTransition($fromStatus, $toStatus)) {
            throw ValidationException::withMessages([
                'commercial_status' => "No se puede pasar de '{$fromStatus->value}' a '{$toStatus->value}'.",
            ]);
        }

        $lead->update(['commercial_status' => $toStatus]);

        if (Schema::hasTable('lead_status_histories')) {
            LeadStatusHistory::query()->create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'changed_at' => now(),
            ]);
        }

        $this->registerAction->execute(
            lead: $lead,
            actionType: CommercialActionType::StatusUpdated,
            userId: $userId,
            notes: $reason,
            metadata: [
                'from' => $fromStatus->value,
                'to' => $toStatus->value,
            ],
        );

        $this->syncProspectoStatus($lead, $toStatus);

        $lead->load(['latestScore', 'primaryContact']);
        $this->recalculatePriority->execute($lead);
    }

    private function syncProspectoStatus(Lead $lead, CommercialLeadStatus $status): void
    {
        Prospecto::query()
            ->where('campaign_id', $lead->campaign_id)
            ->where('nombre', $lead->company_name)
            ->limit(1)
            ->update([
                'estatus' => $status->value,
                'contactado' => in_array($status, [
                    CommercialLeadStatus::ReadyToContact,
                    CommercialLeadStatus::InContact,
                    CommercialLeadStatus::WaitingResponse,
                    CommercialLeadStatus::Responded,
                    CommercialLeadStatus::Interested,
                    CommercialLeadStatus::Closed,
                ], true),
            ]);
    }
}

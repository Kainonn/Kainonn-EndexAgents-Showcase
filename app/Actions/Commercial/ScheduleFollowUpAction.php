<?php

namespace App\Actions\Commercial;

use App\Enums\CommercialActionType;
use App\Models\Lead;

class ScheduleFollowUpAction
{
    public function __construct(
        private readonly RegisterCommercialActionAction $registerAction,
        private readonly RecalculateLeadPriorityAction $recalculatePriority,
    ) {}

    public function execute(Lead $lead, string $date, ?string $notes = null, ?int $userId = null): void
    {
        $lead->update(['next_follow_up_at' => $date]);

        $this->registerAction->execute(
            lead: $lead,
            actionType: CommercialActionType::FollowUpScheduled,
            userId: $userId,
            notes: $notes,
            metadata: ['follow_up_at' => $date],
        );

        $lead->load(['latestScore', 'primaryContact']);
        $this->recalculatePriority->execute($lead);
    }
}

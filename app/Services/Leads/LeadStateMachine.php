<?php

namespace App\Services\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use InvalidArgumentException;

class LeadStateMachine
{
    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'detected' => ['web_audited'],
        'web_audited' => ['enriched'],
        'enriched' => ['scored'],
        'scored' => ['offer_classified'],
        'offer_classified' => ['contact_extracted'],
        'contact_extracted' => ['message_generated'],
        'message_generated' => ['proposal_generated'],
        'proposal_generated' => ['compliance_checked'],
        'compliance_checked' => ['pending_human_review'],
        'pending_human_review' => ['approved', 'needs_adjustment', 'discarded', 'detected'],
        'needs_adjustment' => ['enriched', 'detected'],
        'approved' => ['detected'],
        'discarded' => ['detected'],
    ];

    public function canTransition(LeadStatus $from, LeadStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true);
    }

    public function transition(Lead $lead, LeadStatus $to, string $actorType, string $actorName): void
    {
        $from = $lead->status;

        if (! $from instanceof LeadStatus) {
            throw new InvalidArgumentException('Lead status must be cast to LeadStatus enum.');
        }

        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid lead status transition from %s to %s.',
                $from->value,
                $to->value,
            ));
        }

        $lead->update(['status' => $to]);

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'actor_type' => $actorType,
            'actor_name' => $actorName,
            'event' => 'status.transitioned',
            'event_data' => [
                'from' => $from->value,
                'to' => $to->value,
            ],
            'occurred_at' => now(),
        ]);
    }
}

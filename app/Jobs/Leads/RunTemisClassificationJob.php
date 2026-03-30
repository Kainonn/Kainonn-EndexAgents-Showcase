<?php

namespace App\Jobs\Leads;

use App\Agents\DTOs\AgentContext;
use App\Agents\Orchestrators\AnankeOrchestrator;
use App\Agents\Scoring\TemisAgent;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFinding;
use App\Models\LeadOffer;
use App\Services\Leads\LeadStateMachine;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunTemisClassificationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 1800;

    public function __construct(public int $leadId) {}

    public function uniqueId(): string
    {
        return (string) $this->leadId;
    }

    public function handle(TemisAgent $agent, LeadStateMachine $stateMachine): void
    {
        $lead = Lead::query()
            ->with(['campaign', 'campaignRun'])
            ->find($this->leadId);

        if (! $lead || ! $lead->campaign) {
            return;
        }

        $knowledgeContext = $lead->campaign
            ->campaignKnowledge()
            ->pluck('parsed_content')
            ->filter()
            ->implode("\n\n");

        $previousArtifacts = $lead->findings()
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (LeadFinding $finding): array => $finding->toArray())
            ->values()
            ->all();

        $context = new AgentContext(
            campaign: $lead->campaign,
            campaignRun: $lead->campaignRun,
            lead: $lead,
            knowledgeContext: $knowledgeContext,
            previousArtifacts: $previousArtifacts,
            triggeredByUserId: $lead->campaignRun?->triggered_by_user_id,
        );

        $result = $agent->handle($context);

        LeadFinding::query()->updateOrCreate(
            [
                'lead_id' => $lead->id,
                'agent_name' => $result->agent,
                'stage' => $result->stage,
            ],
            [
                'summary' => $result->summary,
                'evidence' => $result->evidence,
                'payload' => $result->payload,
                'confidence' => $result->confidence,
            ],
        );

        LeadOffer::query()->updateOrCreate(
            [
                'lead_id' => $lead->id,
                'recommended_by_agent' => $result->agent,
            ],
            [
                'offer_type' => $result->payload['offer_type'],
                'offer_summary' => $result->payload['offer_summary'],
                'price_range_min' => $result->payload['price_range_min'],
                'price_range_max' => $result->payload['price_range_max'],
                'justification' => $result->payload['justification'],
            ],
        );

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'actor_type' => 'agent',
            'actor_name' => $result->agent,
            'event' => 'stage.completed',
            'event_data' => $result->toArray(),
            'occurred_at' => now(),
        ]);

        $lead->update([
            'latest_confidence' => $result->confidence,
            'processing_current_stage' => 'Hermes',
            'processing_stage_number' => 6,
        ]);

        $stateMachine->transition($lead, LeadStatus::OfferClassified, 'agent', $result->agent);

        app(AnankeOrchestrator::class)->dispatchNextStage($lead->id, LeadStatus::OfferClassified);
    }

    public function failed(Throwable $exception): void
    {
        LeadPipelineFailure::handle($this->leadId, 'Temis', $exception);
    }
}

<?php

namespace App\Jobs\Leads;

use App\Agents\DTOs\AgentContext;
use App\Agents\Orchestrators\AnankeOrchestrator;
use App\Agents\Prospecting\ArgosAgent;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadContact;
use App\Models\LeadFinding;
use App\Services\Leads\LeadStateMachine;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunArgosAnalysisJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 1800;

    public function __construct(public int $leadId) {}

    public function uniqueId(): string
    {
        return (string) $this->leadId;
    }

    public function handle(ArgosAgent $agent, LeadStateMachine $stateMachine): void
    {
        $lead = Lead::query()
            ->with(['campaign', 'campaignRun'])
            ->find($this->leadId);

        if (! $lead || ! $lead->campaign) {
            return;
        }

        $lead->update([
            'is_processing' => true,
            'processing_current_stage' => 'Argos',
            'processing_stage_number' => 1,
            'processing_total_stages' => 10,
        ]);

        $knowledgeFiles = $lead->campaign
            ->campaignKnowledge()
            ->pluck('file_name')
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $knowledgeContext = $lead->campaign
            ->campaignKnowledge()
            ->pluck('parsed_content')
            ->filter()
            ->implode("\n\n");

        $context = new AgentContext(
            campaign: $lead->campaign,
            campaignRun: $lead->campaignRun,
            lead: $lead,
            knowledgeContext: $knowledgeContext,
            previousArtifacts: [],
            triggeredByUserId: $lead->campaignRun?->triggered_by_user_id,
        );

        $result = $agent->handle($context);
        $result->payload['knowledge_files_used'] = $knowledgeFiles;

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

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'actor_type' => 'agent',
            'actor_name' => $result->agent,
            'event' => 'stage.completed',
            'event_data' => $result->toArray(),
            'occurred_at' => now(),
        ]);

        $mapsPayload = $result->payload['maps'] ?? null;

        if (is_array($mapsPayload) && blank($lead->website_url) && filled($mapsPayload['website'] ?? null)) {
            $lead->website_url = (string) $mapsPayload['website'];
        }

        if (is_array($mapsPayload) && (filled($mapsPayload['phone'] ?? null) || filled($mapsPayload['maps_url'] ?? null))) {
            LeadContact::query()->updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'phone' => filled($mapsPayload['phone'] ?? null) ? (string) $mapsPayload['phone'] : null,
                    'contact_form_url' => filled($mapsPayload['maps_url'] ?? null) ? (string) $mapsPayload['maps_url'] : null,
                    'contact_name' => $lead->company_name.' Contacto',
                    'source_confidence' => 0.78,
                ],
            );
        }

        if ($lead->status !== LeadStatus::Detected) {
            $stateMachine->transition($lead, LeadStatus::Detected, 'agent', $result->agent);
        }

        $lead->update([
            'latest_confidence' => $result->confidence,
            'is_processing' => true,
            'processing_current_stage' => 'Hefesto',
            'processing_stage_number' => 2,
            'processing_total_stages' => 10,
        ]);

        app(AnankeOrchestrator::class)->dispatchNextStage($lead->id, LeadStatus::Detected);
    }

    public function failed(Throwable $exception): void
    {
        LeadPipelineFailure::handle($this->leadId, 'Argos', $exception);
    }
}

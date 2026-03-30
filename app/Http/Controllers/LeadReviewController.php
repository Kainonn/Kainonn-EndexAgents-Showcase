<?php

namespace App\Http\Controllers;

use App\Agents\DTOs\PipelinePayload;
use App\Agents\Orchestrators\AnankeOrchestrator;
use App\Enums\CommercialLeadStatus;
use App\Enums\LeadFeedbackType;
use App\Enums\LeadStatus;
use App\Http\Requests\ReviewLeadRequest;
use App\Http\Requests\SendLeadMessageRequest;
use App\Http\Requests\StoreLeadFeedbackRequest;
use App\Http\Requests\UpdateLeadCommercialStatusRequest;
use App\Http\Requests\UpdateLeadMessageRequest;
use App\Jobs\Leads\RunManualOutreachJob;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFeedback;
use App\Models\LeadFinding;
use App\Models\LeadMessage;
use App\Models\LeadReview;
use App\Models\Prospecto;
use App\Services\Leads\LeadStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LeadReviewController extends Controller
{
    public function show(Lead $lead): Response
    {
        $currentCommercialStatus = $lead->commercial_status ?? CommercialLeadStatus::New;

        $lead->load([
            'campaign:id,name,solution_name',
            'findings:id,lead_id,agent_name,stage,summary,evidence,payload,confidence,created_at',
            'scores:id,lead_id,total_score,urgency_score,fit_score,payment_capacity_score,scored_by_agent',
            'offers:id,lead_id,offer_type,offer_summary,price_range_min,price_range_max,recommended_by_agent',
            'messages:id,lead_id,channel,subject,body,tone,generated_by_agent,version,updated_at',
            'contacts:id,lead_id,email,phone,whatsapp,contact_form_url,contact_name,source_confidence',
            'reviews:id,lead_id,reviewed_by_user_id,decision,review_notes,reviewed_at,created_at',
            'activities:id,lead_id,actor_type,actor_name,event,event_data,occurred_at',
            'feedback:id,lead_id,created_by_user_id,feedback_type,notes,created_at',
        ]);

        $leadId = (int) $lead->getKey();

        $nextPendingLeadId = Lead::query()
            ->where('status', LeadStatus::PendingHumanReview)
            ->where('id', '!=', $leadId)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->value('id');

        $argosFinding = $lead->findings->first(fn (LeadFinding $finding): bool => $finding->agent_name === 'Argos');
        $hefestoFinding = $lead->findings->first(fn (LeadFinding $finding): bool => $finding->agent_name === 'Hefesto');

        $mapsPayload = $this->extractNestedArray($argosFinding?->payload, 'maps');
        $seoPayload = $this->extractNestedArray($hefestoFinding?->payload, 'seo_ux');
        $contact = $lead->contacts->first();

        $leadData = $lead->toArray();
        $leadData['insights'] = [
            'maps_url' => is_string($mapsPayload['maps_url'] ?? null)
                ? (string) $mapsPayload['maps_url']
                : (is_string($contact?->contact_form_url ?? null)
                    ? (string) $contact?->contact_form_url
                    : $this->buildMapsUrl($lead->company_name, $lead->city)),
            'maps_place_id' => $mapsPayload['place_id'] ?? null,
            'rating' => $mapsPayload['rating'] ?? null,
            'reviews_count' => $mapsPayload['reviews_count'] ?? null,
            'website' => $mapsPayload['website'] ?? $lead->website_url,
            'phone' => $mapsPayload['phone'] ?? $contact?->phone,
            'email' => $contact?->email,
            'city' => $lead->city,
            'sector' => $lead->sector,
            'address' => $mapsPayload['address'] ?? null,
            'seo_ux' => $seoPayload,
        ];

        return Inertia::render('endex/lead-review', [
            'lead' => $leadData,
            'nextPendingLeadId' => $nextPendingLeadId,
            'sprintFiveReadiness' => [
                'ai_external_enabled' => (bool) config('endex.ai.external_enabled', false),
                'ai_provider' => (string) config('endex.ai.provider', 'none'),
                'outreach_enabled' => (bool) config('endex.outreach.enabled', false),
                'outreach_mode' => (string) config('endex.outreach.mode', 'manual_review_only'),
            ],
            'processingStatus' => [
                'is_processing' => $lead->is_processing,
                'current_stage' => $lead->processing_current_stage,
                'stage_number' => $lead->processing_stage_number,
                'total_stages' => $lead->processing_total_stages,
            ],
            'commercialFlow' => [
                'current_status' => $currentCommercialStatus->value,
                'status_options' => CommercialLeadStatus::values(),
                'allowed_next_statuses' => CommercialLeadStatus::transitionMap()[$currentCommercialStatus->value] ?? [],
            ],
            'feedbackOptions' => LeadFeedbackType::values(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function extractNestedArray(?array $payload, string $key): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $value = $payload[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    private function buildMapsUrl(string $companyName, ?string $city): string
    {
        $query = trim($companyName.' '.($city ?? ''));

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
    }

    public function store(ReviewLeadRequest $request, Lead $lead, LeadStateMachine $stateMachine): RedirectResponse
    {
        $validated = $request->validated();

        $targetStatus = match ($validated['decision']) {
            'approve' => LeadStatus::Approved,
            'needs_adjustment' => LeadStatus::NeedsAdjustment,
            default => LeadStatus::Discarded,
        };

        LeadReview::query()->create([
            'lead_id' => (int) $lead->getKey(),
            'reviewed_by_user_id' => $request->user()?->id,
            'decision' => $validated['decision'],
            'review_notes' => $validated['review_notes'] ?? null,
            'adjusted_offer' => $validated['adjusted_offer'] ?? null,
            'adjusted_message' => $validated['adjusted_message'] ?? null,
            'reviewed_at' => now(),
        ]);

        $targetCommercialStatus = match ($validated['decision']) {
            'approve' => CommercialLeadStatus::ReadyToContact,
            'needs_adjustment' => CommercialLeadStatus::Analyzed,
            default => CommercialLeadStatus::NotInterested,
        };

        $currentCommercialStatus = $lead->commercial_status ?? CommercialLeadStatus::New;

        if (! CommercialLeadStatus::canTransition($currentCommercialStatus, $targetCommercialStatus)) {
            throw ValidationException::withMessages([
                'decision' => 'No se puede aplicar esta decision en el flujo comercial actual.',
            ]);
        }

        $lead->update([
            'commercial_status' => $targetCommercialStatus,
        ]);

        $this->syncProspectoCommercialStatus($lead, $targetCommercialStatus);

        $didTransition = false;
        $currentStatus = $lead->status;

        if ($currentStatus !== $targetStatus) {
            if (! $currentStatus instanceof LeadStatus || ! $stateMachine->canTransition($currentStatus, $targetStatus)) {
                throw ValidationException::withMessages([
                    'decision' => 'No se puede aplicar esa decision desde el estado actual del lead.',
                ]);
            }

            $stateMachine->transition($lead, $targetStatus, 'user', (string) $request->user()?->name);
            $didTransition = true;
        }

        if (
            $didTransition
            &&
            $targetStatus === LeadStatus::Approved
            && (bool) config('endex.outreach.enabled', false)
            && (string) config('endex.outreach.mode', 'manual_review_only') === 'manual_review_only'
        ) {
            RunManualOutreachJob::dispatch((int) $lead->getKey());
        }

        return redirect()
            ->route('endex.leads.review.show', $lead)
            ->with('success', 'Revision aplicada correctamente.');
    }

    public function reprocess(Lead $lead): RedirectResponse
    {
        $wasMarkedForProcessing = Lead::query()
            ->whereKey($lead->getKey())
            ->where('is_processing', false)
            ->update([
                'is_processing' => true,
                'processing_current_stage' => 'Argos',
                'processing_stage_number' => 1,
                'processing_total_stages' => 10,
            ]) === 1;

        if ($wasMarkedForProcessing) {
            app(AnankeOrchestrator::class)->startLeadPipeline(new PipelinePayload(
                leadId: (int) $lead->getKey(),
                campaignRunId: $lead->campaign_run_id,
                triggeredByUserId: request()->user()?->id,
            ));
        }

        $message = $wasMarkedForProcessing
            ? 'Reproceso iniciado desde Argos. El lead se actualizara en unos momentos.'
            : 'El lead ya se encuentra en procesamiento.';

        return redirect()
            ->route('endex.leads.review.show', $lead)
            ->with('success', $message);
    }

    public function progress(Lead $lead): JsonResponse
    {
        return response()->json([
            'is_processing' => $lead->is_processing,
            'current_stage' => $lead->processing_current_stage,
            'stage_number' => $lead->processing_stage_number,
            'total_stages' => $lead->processing_total_stages,
        ]);
    }

    public function updateCommercialStatus(UpdateLeadCommercialStatusRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();

        $toStatus = CommercialLeadStatus::from((string) $validated['commercial_status']);
        $fromStatus = $lead->commercial_status ?? CommercialLeadStatus::New;

        if (! CommercialLeadStatus::canTransition($fromStatus, $toStatus)) {
            throw ValidationException::withMessages([
                'commercial_status' => 'Transicion comercial no permitida para este lead.',
            ]);
        }

        $lead->update([
            'commercial_status' => $toStatus,
        ]);

        $this->syncProspectoCommercialStatus($lead, $toStatus);

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'actor_type' => 'user',
            'actor_name' => (string) $request->user()?->name,
            'event' => 'commercial_status.transitioned',
            'event_data' => [
                'from' => $fromStatus->value,
                'to' => $toStatus->value,
            ],
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Estado comercial actualizado.');
    }

    public function storeFeedback(StoreLeadFeedbackRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();
        $feedbackType = LeadFeedbackType::from((string) $validated['feedback_type']);

        LeadFeedback::query()->create([
            'lead_id' => $lead->id,
            'created_by_user_id' => $request->user()?->id,
            'feedback_type' => $feedbackType,
            'notes' => $validated['notes'] ?? null,
        ]);

        $statusFromFeedback = match ($feedbackType) {
            LeadFeedbackType::Contacted => CommercialLeadStatus::InContact,
            LeadFeedbackType::BuenLead => CommercialLeadStatus::Analyzed,
            default => CommercialLeadStatus::NotInterested,
        };

        $currentCommercialStatus = $lead->commercial_status ?? CommercialLeadStatus::New;

        if (CommercialLeadStatus::canTransition($currentCommercialStatus, $statusFromFeedback)) {
            $lead->update(['commercial_status' => $statusFromFeedback]);
            $this->syncProspectoCommercialStatus($lead, $statusFromFeedback);
        }

        return back()->with('success', 'Feedback guardado para entrenamiento humano-en-el-loop.');
    }

    public function updateMessage(UpdateLeadMessageRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();

        $latestMessage = LeadMessage::query()
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->first();

        if ($latestMessage) {
            $latestMessage->update([
                'subject' => $validated['subject'] ?? null,
                'body' => (string) $validated['body'],
                'generated_by_agent' => 'HumanOverride',
                'version' => $latestMessage->version + 1,
            ]);
        } else {
            LeadMessage::query()->create([
                'lead_id' => $lead->id,
                'channel' => 'email',
                'subject' => $validated['subject'] ?? null,
                'body' => (string) $validated['body'],
                'generated_by_agent' => 'HumanOverride',
                'version' => 1,
            ]);
        }

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'actor_type' => 'user',
            'actor_name' => (string) $request->user()?->name,
            'event' => 'lead_message.updated',
            'event_data' => [
                'source' => 'human_review',
            ],
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Mensaje actualizado antes del contacto.');
    }

    public function sendMessage(SendLeadMessageRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();

        $lead->load([
            'contacts:id,lead_id,email,phone,contact_name',
            'messages' => fn ($query) => $query->latest('version')->latest('id')->limit(1),
        ]);

        $latestMessage = $lead->messages->first();

        $subject = isset($validated['subject']) ? trim((string) $validated['subject']) : null;
        $body = isset($validated['body']) ? trim((string) $validated['body']) : null;

        if ($body !== null && $body !== '') {
            if ($latestMessage) {
                $latestMessage->update([
                    'subject' => $subject,
                    'body' => $body,
                    'generated_by_agent' => 'HumanOverride',
                    'version' => $latestMessage->version + 1,
                ]);

                $messageToSend = $latestMessage->fresh();
            } else {
                $messageToSend = LeadMessage::query()->create([
                    'lead_id' => $lead->id,
                    'channel' => 'email',
                    'subject' => $subject,
                    'body' => $body,
                    'generated_by_agent' => 'HumanOverride',
                    'version' => 1,
                ]);
            }
        } else {
            $messageToSend = $latestMessage;
        }

        if (! $messageToSend || trim((string) $messageToSend->body) === '') {
            throw ValidationException::withMessages([
                'body' => 'No hay mensaje disponible para enviar.',
            ]);
        }

        $recipientEmail = $lead->contacts
            ->pluck('email')
            ->filter(fn (?string $email): bool => filled($email))
            ->values()
            ->first();

        if (! is_string($recipientEmail) || trim($recipientEmail) === '') {
            throw ValidationException::withMessages([
                'recipient' => 'El lead no tiene email para envio por SES.',
            ]);
        }

        try {
            Mail::mailer('ses')
                ->to($recipientEmail)
                ->send(new class($messageToSend->subject, $messageToSend->body) extends Mailable
                {
                    public function __construct(public ?string $subjectLine, public string $bodyText) {}

                    public function build(): self
                    {
                        return $this->subject($this->subjectLine ?? 'Propuesta Endex')
                            ->text('emails.outreach_plain', [
                                'bodyText' => $this->bodyText,
                            ]);
                    }
                });
        } catch (Throwable $exception) {
            LeadActivity::query()->create([
                'lead_id' => $lead->id,
                'actor_type' => 'system',
                'actor_name' => 'LeadReviewController',
                'event' => 'outreach.failed.send_exception',
                'event_data' => [
                    'provider' => 'aws_ses',
                    'error' => $exception->getMessage(),
                ],
                'occurred_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'send' => 'No se pudo enviar el mensaje por SES.',
            ]);
        }

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'actor_type' => 'user',
            'actor_name' => (string) $request->user()?->name,
            'event' => 'outreach.sent',
            'event_data' => [
                'provider' => 'aws_ses',
                'recipient' => $recipientEmail,
                'message_version' => $messageToSend->version,
            ],
            'occurred_at' => now(),
        ]);

        $currentCommercialStatus = $lead->commercial_status ?? CommercialLeadStatus::New;
        if (CommercialLeadStatus::canTransition($currentCommercialStatus, CommercialLeadStatus::InContact)) {
            $lead->update([
                'commercial_status' => CommercialLeadStatus::InContact,
            ]);
            $this->syncProspectoCommercialStatus($lead, CommercialLeadStatus::InContact);
        }

        return back()->with('success', 'Mensaje enviado por AWS SES.');
    }

    private function syncProspectoCommercialStatus(Lead $lead, CommercialLeadStatus $status): void
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
                    CommercialLeadStatus::Responded,
                    CommercialLeadStatus::Interested,
                    CommercialLeadStatus::Closed,
                ], true),
            ]);
    }
}

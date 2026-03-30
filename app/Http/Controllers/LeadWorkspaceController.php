<?php

namespace App\Http\Controllers;

use App\Actions\Commercial\GetLeadWorkspaceCardsAction;
use App\Actions\Commercial\GetTodayLeadsAction;
use App\Actions\Commercial\PrepareContactAction;
use App\Actions\Commercial\RecalculateLeadPriorityAction;
use App\Actions\Commercial\RegisterCommercialActionAction;
use App\Actions\Commercial\SaveQuickNoteAction;
use App\Actions\Commercial\ScheduleFollowUpAction;
use App\Actions\Commercial\UpdateCommercialStatusAction;
use App\Enums\CommercialActionType;
use App\Enums\CommercialLeadStatus;
use App\Enums\OperationalPriority;
use App\Http\Requests\RegisterCommercialActionRequest;
use App\Http\Requests\SaveWorkspaceContactsRequest;
use App\Http\Requests\SaveQuickNoteRequest;
use App\Http\Requests\ScheduleFollowUpRequest;
use App\Http\Requests\UpdateWorkspaceMessageRequest;
use App\Http\Requests\UpdateWorkspaceStatusRequest;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadContact;
use App\Models\LeadMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LeadWorkspaceController extends Controller
{
    /**
     * Vista principal: Centro de Ataque Comercial.
     */
    public function index(Request $request, GetLeadWorkspaceCardsAction $cardsAction): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'string', 'max:20'],
            'search' => ['nullable', 'string', 'max:120'],
            'quick_filter' => ['nullable', 'string', 'in:actionable,no_channel,overdue_followup,waiting'],
            'follow_up_window' => ['nullable', 'string', 'in:today,tomorrow,this_week,this_month,next_7_days,next_30_days,overdue,without_date'],
        ]);

        $payload = $cardsAction->execute(
            statusFilter: $validated['status'] ?? null,
            priorityFilter: $validated['priority'] ?? null,
            search: $validated['search'] ?? null,
            quickFilter: $validated['quick_filter'] ?? null,
            followUpWindow: $validated['follow_up_window'] ?? null,
        );

        return Inertia::render('endex/lead-workspace', [
            'cards' => $payload['cards'],
            'workQueue' => $payload['work_queue'] ?? [],
            'todayCount' => $payload['today_count'],
            'statusCounts' => $payload['status_counts'],
            'priorityCounts' => $payload['priority_counts'],
            'kpis' => $payload['kpis'],
            'filters' => [
                'status' => $validated['status'] ?? 'all',
                'priority' => $validated['priority'] ?? 'all',
                'search' => $validated['search'] ?? '',
                'quick_filter' => $validated['quick_filter'] ?? null,
                'follow_up_window' => $validated['follow_up_window'] ?? null,
            ],
            'statusOptions' => CommercialLeadStatus::commercialFlowOptions(),
            'priorityOptions' => collect(OperationalPriority::cases())
                ->mapWithKeys(fn (OperationalPriority $p): array => [
                    $p->value => [
                        'label' => $p->label(),
                        'emoji' => $p->emoji(),
                    ],
                ])->all(),
        ]);
    }

    /**
     * Detalle rápido para drawer lateral (JSON).
     */
    public function quickDetail(Lead $lead): JsonResponse
    {
        $relations = [
            'campaign:id,name,solution_name',
            'latestScore' => fn ($q) => $q->select([
                'lead_scores.id',
                'lead_scores.lead_id',
                'lead_scores.total_score',
                'lead_scores.urgency_score',
                'lead_scores.fit_score',
                'lead_scores.payment_capacity_score',
            ]),
            'primaryContact' => fn ($q) => $q->select([
                'lead_contacts.id',
                'lead_contacts.lead_id',
                'lead_contacts.email',
                'lead_contacts.phone',
                'lead_contacts.whatsapp',
                'lead_contacts.contact_name',
                'lead_contacts.contact_form_url',
            ]),
            'contacts' => fn ($q) => $q->select([
                'lead_contacts.id',
                'lead_contacts.lead_id',
                'lead_contacts.email',
                'lead_contacts.phone',
                'lead_contacts.whatsapp',
                'lead_contacts.contact_name',
                'lead_contacts.contact_form_url',
            ])->latest('id'),
            'messages:id,lead_id,channel,subject,body,tone,generated_by_agent,version,updated_at',
            'offers' => fn ($q) => $q->latest('id')->limit(1),
            'commercialActions' => fn ($q) => $q->latest('occurred_at')->limit(5),
            'findings' => fn ($q) => $q->latest('id')->limit(10),
            'latestCommercialAction',
        ];

        if (Schema::hasTable('lead_status_histories')) {
            $relations['statusHistory'] = fn ($q) => $q->latest('changed_at')->limit(5);
        }

        $lead->load($relations);

        $argosFinding = $lead->findings->firstWhere('agent_name', 'Argos');
        $hefestoFinding = $lead->findings->firstWhere('agent_name', 'Hefesto');
        $offer = $lead->offers->first();
        $effective = $this->resolveEffectiveMessage($lead);
        $latestAction = $lead->latestCommercialAction;

        return response()->json([
            'id' => $lead->id,
            'company_name' => $lead->company_name,
            'city' => $lead->city,
            'score_value' => $lead->latestScore?->total_score,
            'commercial_status' => $lead->commercial_status?->value,
            'operational_priority' => $lead->operational_priority?->value,
            'primary_problem' => $lead->primary_problem,
            'sales_angle' => $lead->sales_angle,
            'recommended_channel' => $lead->recommended_channel?->value,
            'next_step' => $this->resolveNextStepForDetail($lead, $effective['source']),
            'effective_message' => $effective,
            'contacts' => [
                'whatsapp' => $lead->primaryContact?->whatsapp,
                'phone' => $lead->primaryContact?->phone,
                'email' => $lead->primaryContact?->email,
                'name' => $lead->primaryContact?->contact_name,
            ],
            'findings' => $lead->findings->map(fn ($finding) => [
                'id' => $finding->id,
                'agent_name' => $finding->agent_name,
                'summary' => $finding->summary,
                'confidence' => $finding->confidence,
                'stage' => $finding->stage?->value,
            ])->values()->all(),
            'latest_commercial_action' => $latestAction ? [
                'type' => $latestAction->action_type?->value ?? (string) $latestAction->action_type,
                'label' => $latestAction->action_type?->label() ?? (string) $latestAction->action_type,
                'channel' => $latestAction->channel,
                'notes' => $latestAction->notes,
                'occurred_at' => $latestAction->occurred_at?->toIso8601String(),
            ] : null,
            'latest_note' => $lead->commercial_notes,
            'detail_ready' => true,
            'sector' => $lead->sector,
            'website_url' => $lead->website_url,
            'campaign_name' => $lead->campaign?->name,
            'score' => [
                'total' => $lead->latestScore?->total_score,
                'urgency' => $lead->latestScore?->urgency_score,
                'fit' => $lead->latestScore?->fit_score,
                'payment_capacity' => $lead->latestScore?->payment_capacity_score,
            ],
            'contact' => [
                'email' => $lead->primaryContact?->email,
                'phone' => $lead->primaryContact?->phone,
                'whatsapp' => $lead->primaryContact?->whatsapp,
                'contact_name' => $lead->primaryContact?->contact_name,
                'contact_form_url' => $lead->primaryContact?->contact_form_url,
            ],
            'contact_list' => $lead->contacts->map(fn (LeadContact $contact) => [
                'id' => $contact->id,
                'contact_name' => $contact->contact_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'whatsapp' => $contact->whatsapp,
                'contact_form_url' => $contact->contact_form_url,
            ])->values()->all(),
            'message' => [
                'subject' => $effective['subject'],
                'body' => $effective['body'],
                'tone' => null,
                'version' => null,
                'updated_at' => $effective['updated_at'],
                'source' => $effective['source'],
                'id' => $effective['id'],
            ],
            'offer' => $offer ? [
                'id' => $offer->id,
                'type' => $offer->offer_type,
                'summary' => $offer->offer_summary,
                'price_min' => $offer->price_range_min,
                'price_max' => $offer->price_range_max,
            ] : null,
            'commercial' => [
                'status' => $lead->commercial_status?->value,
                'status_label' => $lead->commercial_status?->label(),
                'operational_priority' => $lead->operational_priority?->value,
                'primary_problem' => $lead->primary_problem,
                'sales_angle' => $lead->sales_angle,
                'recommended_channel' => $lead->recommended_channel?->value,
                'quick_tip' => $lead->quick_tip,
                'commercial_notes' => $lead->commercial_notes,
                'last_contacted_at' => $lead->last_contacted_at?->toIso8601String(),
                'next_follow_up_at' => $lead->next_follow_up_at?->toIso8601String(),
            ],
            'insights' => [
                'argos_summary' => $argosFinding?->summary,
                'hefesto_summary' => $hefestoFinding?->summary,
                'maps_url' => $this->extractMapsUrl($argosFinding, $lead),
            ],
            'recent_actions' => $lead->commercialActions->map(fn ($a) => [
                'type' => $a->action_type->value ?? $a->action_type,
                'label' => $a->action_type instanceof CommercialActionType ? $a->action_type->label() : $a->action_type,
                'channel' => $a->channel,
                'notes' => $a->notes,
                'occurred_at' => $a->occurred_at?->toIso8601String(),
            ])->all(),
            'status_history' => Schema::hasTable('lead_status_histories')
                ? $lead->statusHistory->map(fn ($h) => [
                    'from' => $h->from_status->value ?? $h->from_status,
                    'to' => $h->to_status->value ?? $h->to_status,
                    'reason' => $h->reason,
                    'changed_at' => $h->changed_at?->toIso8601String(),
                ])->all()
                : [],
            'review_url' => route('endex.leads.review.show', $lead),
        ]);
    }

    /**
     * Actualizar estado comercial desde la tarjeta.
     */
    public function updateStatus(
        UpdateWorkspaceStatusRequest $request,
        Lead $lead,
        UpdateCommercialStatusAction $action,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $action->execute(
            lead: $lead,
            toStatus: CommercialLeadStatus::from($validated['commercial_status']),
            userId: $request->user()?->id,
            reason: $validated['reason'] ?? null,
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Estado actualizado.']);
        }

        return back()->with('success', 'Estado actualizado.');
    }

    /**
     * Guardar nota rápida comercial.
     */
    public function saveNote(
        SaveQuickNoteRequest $request,
        Lead $lead,
        SaveQuickNoteAction $action,
    ): RedirectResponse|JsonResponse {
        $action->execute(
            lead: $lead,
            notes: $request->validated('commercial_notes'),
            userId: $request->user()?->id,
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Nota guardada.']);
        }

        return back()->with('success', 'Nota guardada.');
    }

    /**
     * Registrar acción comercial manual.
     */
    public function registerAction(
        RegisterCommercialActionRequest $request,
        Lead $lead,
        RegisterCommercialActionAction $action,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $action->execute(
            lead: $lead,
            actionType: CommercialActionType::from($validated['action_type']),
            userId: $request->user()?->id,
            channel: $validated['channel'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Acción registrada.']);
        }

        return back()->with('success', 'Acción registrada.');
    }

    /**
     * Programar follow-up.
     */
    public function scheduleFollowUp(
        ScheduleFollowUpRequest $request,
        Lead $lead,
        ScheduleFollowUpAction $action,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $action->execute(
            lead: $lead,
            date: $validated['next_follow_up_at'],
            notes: $validated['notes'] ?? null,
            userId: $request->user()?->id,
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Seguimiento programado.']);
        }

        return back()->with('success', 'Seguimiento programado.');
    }

    /**
     * Preparar contacto inmediato (devuelve JSON con URLs y texto copiable).
     */
    public function prepareContact(Lead $lead, PrepareContactAction $action): JsonResponse
    {
        $result = $action->execute(
            lead: $lead,
            userId: request()->user()?->id,
        );

        return response()->json($result);
    }

    /**
     * Actualizar mensaje desde el workspace.
     */
    public function updateMessage(UpdateWorkspaceMessageRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();

        $latestMessage = $lead->messages()->latest('id')->first();

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

        app(RegisterCommercialActionAction::class)->execute(
            lead: $lead,
            actionType: CommercialActionType::MessageEdited,
            userId: $request->user()?->id,
        );

        return back()->with('success', 'Mensaje actualizado.');
    }

    /**
     * Guardar contactos del lead (telefonos/correos/web) desde el drawer.
     */
    public function saveContacts(
        SaveWorkspaceContactsRequest $request,
        Lead $lead,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $websiteUrl = isset($validated['website_url']) ? trim((string) $validated['website_url']) : null;

        $lead->update([
            'website_url' => $websiteUrl !== '' ? $websiteUrl : null,
        ]);

        $rows = collect($validated['contacts'] ?? []);

        foreach ($rows as $row) {
            $contactId = isset($row['id']) ? (int) $row['id'] : null;

            $payload = [
                'contact_name' => isset($row['contact_name']) && trim((string) $row['contact_name']) !== ''
                    ? trim((string) $row['contact_name'])
                    : null,
                'email' => isset($row['email']) && trim((string) $row['email']) !== ''
                    ? trim((string) $row['email'])
                    : null,
                'phone' => isset($row['phone']) && trim((string) $row['phone']) !== ''
                    ? trim((string) $row['phone'])
                    : null,
                'whatsapp' => isset($row['whatsapp']) && trim((string) $row['whatsapp']) !== ''
                    ? trim((string) $row['whatsapp'])
                    : null,
                'contact_form_url' => isset($row['contact_form_url']) && trim((string) $row['contact_form_url']) !== ''
                    ? trim((string) $row['contact_form_url'])
                    : null,
            ];

            $hasAnyData = collect($payload)->some(fn ($value) => $value !== null);

            if (! $hasAnyData) {
                continue;
            }

            if ($contactId !== null) {
                $existing = $lead->contacts()->whereKey($contactId)->first();
                if ($existing !== null) {
                    $existing->update($payload);
                    continue;
                }
            }

            $lead->contacts()->create($payload);
        }

        $contactList = $lead->contacts()
            ->latest('id')
            ->get([
                'id',
                'lead_id',
                'contact_name',
                'email',
                'phone',
                'whatsapp',
                'contact_form_url',
            ])
            ->map(fn (LeadContact $contact) => [
                'id' => $contact->id,
                'contact_name' => $contact->contact_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'whatsapp' => $contact->whatsapp,
                'contact_form_url' => $contact->contact_form_url,
            ])
            ->values()
            ->all();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Contactos actualizados.',
                'website_url' => $lead->website_url,
                'contact_list' => $contactList,
            ]);
        }

        return back()->with('success', 'Contactos actualizados.');
    }

    /**
     * @return string
     */
    private function extractMapsUrl(mixed $argosFinding, Lead $lead): string
    {
        $payload = $argosFinding?->payload;

        if (is_array($payload) && isset($payload['maps']['maps_url']) && is_string($payload['maps']['maps_url'])) {
            return $payload['maps']['maps_url'];
        }

        $query = trim($lead->company_name . ' ' . ($lead->city ?? ''));

        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($query);
    }

    private function resolveNextStepForDetail(Lead $lead, string $messageSource): string
    {
        if ($lead->next_follow_up_at?->isPast()) {
            return '⚠️ Follow-up vencido: contactar hoy';
        }

        if ($lead->recommended_channel?->value === 'none') {
            return 'Definir canal recomendado antes de contactar';
        }

        if ($messageSource === 'fallback') {
            return 'Editar mensaje base antes de enviar';
        }

        return 'Contactar y registrar resultado en acciones';
    }

    /**
     * @return array{
     *   id:int|null,
     *   subject:string,
     *   body:string,
     *   preview:string,
     *   source:string,
     *   updated_at:string|null,
     * }
     */
    private function resolveEffectiveMessage(Lead $lead): array
    {
        $messages = $lead->relationLoaded('messages')
            ? $lead->messages
            : $lead->messages()->latest('id')->get();

        $humanMessage = $messages
            ->filter(fn (LeadMessage $message): bool => $this->isHumanEdited($message))
            ->sortByDesc('id')
            ->first();

        if ($humanMessage !== null && trim((string) $humanMessage->body) !== '') {
            return $this->buildMessagePayload($humanMessage, 'human_edited');
        }

        $aiMessage = $messages
            ->filter(fn (LeadMessage $message): bool => ! $this->isHumanEdited($message) && trim((string) $message->body) !== '')
            ->sortByDesc('id')
            ->first();

        if ($aiMessage !== null) {
            return $this->buildMessagePayload($aiMessage, 'ai_generated');
        }

        $company = trim((string) $lead->company_name);
        $body = "Hola {$company},\n\n"
            . 'Detectamos oportunidades para mejorar captacion y seguimiento comercial con automatizacion.'
            . "\n\n"
            . 'Si te parece, te compartimos una propuesta inicial esta semana.';

        return [
            'id' => null,
            'subject' => "Propuesta para {$company}",
            'body' => $body,
            'preview' => $this->messagePreview($body),
            'source' => 'fallback',
            'updated_at' => null,
        ];
    }

    private function isHumanEdited(LeadMessage $message): bool
    {
        $agent = strtolower((string) $message->generated_by_agent);

        return in_array($agent, ['humanoverride', 'manual', 'human', 'workspace_human', 'user'], true);
    }

    /**
     * @return array{
     *   id:int|null,
     *   subject:string,
     *   body:string,
     *   preview:string,
     *   source:string,
     *   updated_at:string|null,
     * }
     */
    private function buildMessagePayload(LeadMessage $message, string $source): array
    {
        $subject = trim((string) ($message->subject ?? ''));
        $body = trim((string) $message->body);

        return [
            'id' => $message->id,
            'subject' => $subject !== '' ? $subject : 'Propuesta comercial',
            'body' => $body,
            'preview' => $this->messagePreview($body),
            'source' => $source,
            'updated_at' => $message->updated_at?->toIso8601String(),
        ];
    }

    private function messagePreview(string $body): string
    {
        $clean = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $clean);
        $preview = '';
        $lineCount = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $preview .= ($lineCount > 0 ? ' ' : '') . $trimmed;
            $lineCount++;

            if ($lineCount >= 2 || mb_strlen($preview) >= 140) {
                break;
            }
        }

        if (mb_strlen($preview) > 140) {
            return mb_substr($preview, 0, 137) . '...';
        }

        return $preview;
    }
}

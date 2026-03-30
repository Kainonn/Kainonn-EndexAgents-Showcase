<?php

namespace App\Actions\Commercial;

use App\Enums\CommercialLeadStatus;
use App\Enums\CommercialActionType;
use App\Enums\OperationalPriority;
use App\Enums\RecommendedChannel;
use App\Models\Lead;
use App\Models\LeadCommercialAction;
use App\Models\LeadMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class GetLeadWorkspaceCardsAction
{
    /**
     * Devuelve payload listo para Inertia con tarjetas de decisión comercial.
     *
     * @return array<string, mixed>
     */
    public function execute(
        ?string $statusFilter = null,
        ?string $priorityFilter = null,
        ?string $search = null,
        ?string $quickFilter = null,
        ?string $followUpWindow = null,
        int $limit = 100,
    ): array
    {
        $now = now();

        $query = Lead::query()
            ->with([
                'latestScore',
                'primaryContact',
                'messages' => fn ($q) => $q->latest('id')->limit(20),
                'offers' => fn ($q) => $q->latest('id')->limit(1),
                'findings' => fn ($q) => $q->latest('id')->limit(6),
                'latestCommercialAction',
            ])
            ->whereNotNull('commercial_status');

        if ($statusFilter !== null && $statusFilter !== 'all') {
            $query->where('commercial_status', $statusFilter);
        }

        if ($priorityFilter !== null && $priorityFilter !== 'all') {
            $query->where('operational_priority', $priorityFilter);
        }

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('company_name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('sector', 'like', "%{$term}%");
            });
        }

        if ($quickFilter !== null) {
            match ($quickFilter) {
                'actionable' => $query
                    ->whereNotIn('commercial_status', [
                        CommercialLeadStatus::Closed,
                        CommercialLeadStatus::NotInterested,
                    ])
                    ->where('recommended_channel', '!=', RecommendedChannel::None),
                'no_channel' => $query->where(function ($q) {
                    $q->whereNull('recommended_channel')
                        ->orWhere('recommended_channel', RecommendedChannel::None);
                }),
                'overdue_followup' => $query
                    ->whereNotNull('next_follow_up_at')
                    ->where('next_follow_up_at', '<', now()),
                'waiting' => $query->where('commercial_status', CommercialLeadStatus::WaitingResponse),
                default => null,
            };
        }

        if ($followUpWindow !== null && $followUpWindow !== 'all') {
            match ($followUpWindow) {
                'today' => $query->whereDate('next_follow_up_at', $now->toDateString()),
                'tomorrow' => $query->whereDate('next_follow_up_at', $now->copy()->addDay()->toDateString()),
                'this_week' => $query->whereBetween('next_follow_up_at', [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek(),
                ]),
                'this_month' => $query->whereBetween('next_follow_up_at', [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ]),
                'next_7_days' => $query->whereBetween('next_follow_up_at', [
                    $now,
                    $now->copy()->addDays(7),
                ]),
                'next_30_days' => $query->whereBetween('next_follow_up_at', [
                    $now,
                    $now->copy()->addDays(30),
                ]),
                'overdue' => $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', $now),
                'without_date' => $query->whereNull('next_follow_up_at'),
                default => null,
            };
        }

        // Ordenar: contact_today primero, luego this_week, luego por score
        $query->orderByRaw("FIELD(operational_priority, 'contact_today', 'this_week', 'low_priority')")
            ->orderByRaw("FIELD(commercial_status, 'responded', 'interested', 'waiting_response', 'new', 'in_contact', 'ready_to_contact', 'analyzed', 'not_interested', 'closed')")
            ->latest('id');

        /** @var Collection<int, Lead> $leads */
        $leads = $query->limit($limit)->get();

        $cards = $leads->map(fn (Lead $lead): array => $this->buildCard($lead))->values()->all();

        // Contadores sin filtros (para tabs)
        $statusCounts = Lead::query()
            ->whereNotNull('commercial_status')
            ->selectRaw('commercial_status, COUNT(*) as count')
            ->groupBy('commercial_status')
            ->pluck('count', 'commercial_status')
            ->all();

        $priorityCounts = Lead::query()
            ->whereNotNull('commercial_status')
            ->selectRaw('operational_priority, COUNT(*) as count')
            ->groupBy('operational_priority')
            ->pluck('count', 'operational_priority')
            ->all();

        $todayCount = Lead::query()
            ->where('operational_priority', OperationalPriority::ContactToday)
            ->whereNotIn('commercial_status', [
                CommercialLeadStatus::Closed,
                CommercialLeadStatus::NotInterested,
            ])
            ->count();

        $activeLeadQuery = Lead::query()
            ->whereNotNull('commercial_status')
            ->whereNotIn('commercial_status', [CommercialLeadStatus::Closed, CommercialLeadStatus::NotInterested]);

        $followUpsDueToday = (clone $activeLeadQuery)
            ->whereDate('next_follow_up_at', today())
            ->count();

        $waitingResponseStale48h = (clone $activeLeadQuery)
            ->where('commercial_status', CommercialLeadStatus::WaitingResponse)
            ->where(function ($q) {
                $q->whereNull('last_contacted_at')
                    ->orWhere('last_contacted_at', '<=', now()->subHours(48));
            })
            ->count();

        $workQueue = $this->buildWorkQueue(limit: 20);

        return [
            'cards' => $cards,
            'work_queue' => $workQueue,
            'today_count' => $todayCount,
            'status_counts' => $statusCounts,
            'priority_counts' => $priorityCounts,
            'kpis' => [
                'waiting_response' => (int) ($statusCounts[CommercialLeadStatus::WaitingResponse->value] ?? 0),
                'overdue_follow_ups' => Lead::query()
                    ->whereNotNull('commercial_status')
                    ->whereNotNull('next_follow_up_at')
                    ->where('next_follow_up_at', '<', now())
                    ->whereNotIn('commercial_status', [CommercialLeadStatus::Closed, CommercialLeadStatus::NotInterested])
                    ->count(),
                'closed_this_week' => Lead::query()
                    ->where('commercial_status', CommercialLeadStatus::Closed)
                    ->where('updated_at', '>=', now()->startOfWeek())
                    ->count(),
                'contacted_today' => LeadCommercialAction::query()
                    ->whereDate('occurred_at', today())
                    ->whereIn('action_type', [
                        CommercialActionType::ContactInitiated,
                        CommercialActionType::WhatsappOpened,
                        CommercialActionType::CallStarted,
                        CommercialActionType::EmailPrepared,
                    ])
                    ->distinct('lead_id')
                    ->count('lead_id'),
                'follow_ups_due_today' => $followUpsDueToday,
                'follow_ups_completed_today' => LeadCommercialAction::query()
                    ->whereDate('occurred_at', today())
                    ->where('action_type', CommercialActionType::FollowUpScheduled)
                    ->count(),
                'waiting_response_stale_48h' => $waitingResponseStale48h,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildWorkQueue(int $limit = 20): array
    {
        $candidates = Lead::query()
            ->with([
                'latestScore',
                'primaryContact',
                'messages' => fn ($q) => $q->latest('id')->limit(20),
                'offers' => fn ($q) => $q->latest('id')->limit(1),
                'findings' => fn ($q) => $q->latest('id')->limit(6),
                'latestCommercialAction',
            ])
            ->whereNotIn('commercial_status', [CommercialLeadStatus::Closed, CommercialLeadStatus::NotInterested])
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNotNull('next_follow_up_at')
                        ->where('next_follow_up_at', '<', now());
                })->orWhere(function ($sq) {
                    $sq->whereDate('next_follow_up_at', today());
                })->orWhere(function ($sq) {
                    $sq->where('commercial_status', CommercialLeadStatus::WaitingResponse)
                        ->where(function ($stale) {
                            $stale->whereNull('last_contacted_at')
                                ->orWhere('last_contacted_at', '<=', now()->subHours(48));
                        });
                })->orWhere(function ($sq) {
                    $sq->whereIn('commercial_status', [
                        CommercialLeadStatus::New,
                        CommercialLeadStatus::Analyzed,
                        CommercialLeadStatus::ReadyToContact,
                    ])->whereNull('last_contacted_at');
                });
            })
            ->orderByRaw("CASE
                WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < NOW() THEN 1
                WHEN DATE(next_follow_up_at) = CURDATE() THEN 2
                WHEN commercial_status = 'waiting_response' AND (last_contacted_at IS NULL OR last_contacted_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)) THEN 3
                WHEN commercial_status IN ('new', 'analyzed', 'ready_to_contact') AND last_contacted_at IS NULL THEN 4
                ELSE 5
            END")
            ->orderByRaw('COALESCE(next_follow_up_at, NOW() + INTERVAL 30 DAY) ASC')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $candidates
            ->map(function (Lead $lead): array {
                $queueMeta = $this->resolveQueueMeta($lead);

                return [
                    'card' => $this->buildCard($lead),
                    'queue_group' => $queueMeta['group'],
                    'queue_reason' => $queueMeta['reason'],
                    'sla_tone' => $queueMeta['tone'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{group:'overdue_follow_up'|'due_today_follow_up'|'stale_waiting_response'|'new_without_contact'|'default',reason:string,tone:'critical'|'warning'|'neutral'}
     */
    private function resolveQueueMeta(Lead $lead): array
    {
        $followUp = $lead->next_follow_up_at;
        $status = $lead->commercial_status ?? CommercialLeadStatus::New;
        $lastContacted = $lead->last_contacted_at;

        if ($followUp?->isPast()) {
            return [
                'group' => 'overdue_follow_up',
                'reason' => 'Seguimiento vencido: resolver hoy',
                'tone' => 'critical',
            ];
        }

        if ($followUp !== null && $followUp->isToday()) {
            return [
                'group' => 'due_today_follow_up',
                'reason' => 'Seguimiento programado para hoy',
                'tone' => 'warning',
            ];
        }

        if ($status === CommercialLeadStatus::WaitingResponse && ($lastContacted === null || $lastContacted->lte(now()->subHours(48)))) {
            return [
                'group' => 'stale_waiting_response',
                'reason' => 'Esperando respuesta > 48h',
                'tone' => 'warning',
            ];
        }

        if (in_array($status, [CommercialLeadStatus::New, CommercialLeadStatus::Analyzed, CommercialLeadStatus::ReadyToContact], true) && $lastContacted === null) {
            return [
                'group' => 'new_without_contact',
                'reason' => 'Sin primer contacto',
                'tone' => 'neutral',
            ];
        }

        return [
            'group' => 'default',
            'reason' => 'Pendiente comercial',
            'tone' => 'neutral',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCard(Lead $lead): array
    {
        $score = $lead->latestScore;
        $contact = $lead->primaryContact;
        $lastAction = $lead->latestCommercialAction;
        $offer = $lead->offers->first();
        $effectiveMessage = $this->resolveEffectiveMessage($lead);

        $priority = $lead->operational_priority ?? OperationalPriority::LowPriority;
        $status = $lead->commercial_status ?? CommercialLeadStatus::New;
        $channel = $lead->recommended_channel ?? RecommendedChannel::None;

        $contacts = [
            'whatsapp' => $contact?->whatsapp,
            'phone' => $contact?->phone,
            'email' => $contact?->email,
            'name' => $contact?->contact_name,
        ];

        $findings = $lead->findings
            ->map(fn ($finding): array => [
                'id' => $finding->id,
                'agent_name' => $finding->agent_name,
                'summary' => $finding->summary,
                'confidence' => $finding->confidence,
                'stage' => $finding->stage?->value ?? null,
            ])
            ->values()
            ->all();

        $latestCommercialAction = $lastAction ? [
            'type' => $lastAction->action_type?->value ?? (string) $lastAction->action_type,
            'label' => $lastAction->action_type?->label() ?? (string) $lastAction->action_type,
            'channel' => $lastAction->channel,
            'notes' => $lastAction->notes,
            'occurred_at' => $lastAction->occurred_at?->toIso8601String(),
        ] : null;

        $closingSignals = $this->resolveClosingSignals($lead, $score?->total_score);

        $nextStep = $this->computeNextStep($lead, $status, $channel, $effectiveMessage['source']);

        return [
            'id' => $lead->id,
            'company_name' => $lead->company_name,
            'city' => $lead->city,
            'score' => $score?->total_score,
            'commercial_status' => $status->value,
            'operational_priority' => $priority->value,
            'primary_problem' => $lead->primary_problem,
            'sales_angle' => $lead->sales_angle,
            'recommended_channel' => $channel->value,
            'next_step' => $nextStep,
            'effective_message' => $effectiveMessage,
            'contacts' => $contacts,
            'findings' => $findings,
            'offer' => $offer ? [
                'id' => $offer->id,
                'type' => $offer->offer_type,
                'summary' => $offer->offer_summary,
                'price_min' => $offer->price_range_min,
                'price_max' => $offer->price_range_max,
            ] : null,
            'latest_commercial_action' => $latestCommercialAction,
            'latest_note' => $lead->commercial_notes,
            'next_follow_up_at' => $lead->next_follow_up_at?->toIso8601String(),
            'detail_ready' => true,

            // Campos de compatibilidad UI actual
            'sector' => $lead->sector,
            'operational_priority' => $priority->value,
            'operational_priority_label' => $priority->label(),
            'operational_priority_emoji' => $priority->emoji(),
            'commercial_status' => $status->value,
            'commercial_status_label' => $status->label(),
            'commercial_status_color' => $status->color(),
            'primary_problem' => $lead->primary_problem,
            'sales_angle' => $lead->sales_angle,
            'recommended_channel' => $channel->value,
            'recommended_channel_label' => $channel->label(),
            'recommended_channel_emoji' => $channel->emoji(),
            'ready_message_preview' => $effectiveMessage['preview'],
            'has_full_message' => $effectiveMessage['source'] !== 'fallback',
            'effective_message_id' => $effectiveMessage['id'],
            'effective_message_subject' => $effectiveMessage['subject'],
            'effective_message_body' => $effectiveMessage['body'],
            'effective_message_preview' => $effectiveMessage['preview'],
            'effective_message_source' => $effectiveMessage['source'],
            'effective_message_updated_at' => $effectiveMessage['updated_at'],
            'quick_tip' => $lead->quick_tip,
            'closing_priority' => $closingSignals['priority'],
            'closing_priority_label' => $closingSignals['label'],
            'closing_priority_source' => $closingSignals['source'],
            'contact_recommendation' => $closingSignals['contact_recommendation'],
            'commercial_notes' => $lead->commercial_notes,
            'last_action_summary' => $this->actionSummary($lastAction),
            'last_contacted_at' => $lead->last_contacted_at?->toIso8601String(),
            'next_follow_up_at' => $lead->next_follow_up_at?->toIso8601String(),
            'follow_up_overdue' => $lead->next_follow_up_at?->isPast() ?? false,
            'contact' => [
                'whatsapp' => $contacts['whatsapp'],
                'phone' => $contacts['phone'],
                'email' => $contacts['email'],
                'contact_name' => $contacts['name'],
            ],
            'quick_actions' => [
                'can_contact_now' => $channel !== RecommendedChannel::None
                    && trim($effectiveMessage['body']) !== '',
                'can_edit_message' => true,
                'can_open_detail' => true,
            ],
            'review_url' => route('endex.leads.review.show', $lead),
        ];
    }

    /**
     * @return array{priority:string,label:string,source:string,contact_recommendation:string|null}
     */
    private function resolveClosingSignals(Lead $lead, int|float|null $score): array
    {
        $minosFinding = $lead->findings
            ->first(fn ($finding): bool => strtolower((string) $finding->agent_name) === 'minos');

        $payload = is_array($minosFinding?->payload) ? $minosFinding->payload : [];
        $priority = strtoupper((string) ($payload['commercial_priority'] ?? ''));
        $contactRecommendation = isset($payload['contact_recommendation']) && is_string($payload['contact_recommendation'])
            ? $payload['contact_recommendation']
            : null;

        if ($priority === '') {
            $numericScore = $score !== null ? (float) $score : null;

            if ($numericScore === null) {
                $priority = 'INCOMPLETO';
            } elseif ($numericScore >= 78) {
                $priority = 'ALTO';
            } elseif ($numericScore >= 60) {
                $priority = 'MEDIO';
            } else {
                $priority = 'BAJO';
            }
        }

        $label = match ($priority) {
            'ALTO' => 'Alto cierre',
            'MEDIO' => 'Cierre medio',
            'BAJO' => 'Bajo cierre',
            default => 'Cierre incompleto',
        };

        return [
            'priority' => $priority,
            'label' => $label,
            'source' => $minosFinding !== null ? 'minos' : 'score_fallback',
            'contact_recommendation' => $contactRecommendation,
        ];
    }

    private function computeNextStep(Lead $lead, CommercialLeadStatus $status, RecommendedChannel $channel, string $messageSource): string
    {
        if ($lead->next_follow_up_at?->isPast()) {
            return '⚠️ Follow-up vencido: contactar hoy';
        }

        if ($status === CommercialLeadStatus::Closed) {
            return 'Lead cerrado: monitorear postventa';
        }

        if ($status === CommercialLeadStatus::NotInterested) {
            return 'Lead perdido: documentar motivo y pasar al siguiente';
        }

        if ($channel === RecommendedChannel::None) {
            return 'Definir canal recomendado antes de contactar';
        }

        if ($messageSource === 'fallback') {
            return 'Editar mensaje base antes de enviar';
        }

        if ($status === CommercialLeadStatus::WaitingResponse) {
            return 'Esperar respuesta o programar seguimiento';
        }

        if ($status === CommercialLeadStatus::Interested) {
            return 'Enviar propuesta y agendar llamada';
        }

        return 'Contactar y registrar resultado en acciones';
    }

    private function actionSummary(?LeadCommercialAction $action): ?string
    {
        if ($action === null) {
            return null;
        }

        $type = $action->action_type;
        $date = $action->occurred_at;

        $label = $type instanceof \App\Enums\CommercialActionType
            ? $type->emoji() . ' ' . $type->label()
            : (string) $type;

        $dateStr = $date instanceof Carbon
            ? $date->format('d M Y H:i')
            : '';

        $summary = $label . ' — ' . $dateStr;

        if ($action->notes) {
            $summary .= ' · ' . mb_substr($action->notes, 0, 60);
        }

        return $summary;
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
        /** @var Collection<int, LeadMessage> $messages */
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

<?php

namespace App\Http\Controllers;

use App\Enums\AgentStage;
use App\Enums\CommercialLeadStatus;
use App\Http\Requests\AnalyzeProspectosRequest;
use App\Http\Requests\UpdateProspectoStatusRequest;
use App\Jobs\Leads\RunHefestoWebAuditJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadContact;
use App\Models\LeadFinding;
use App\Models\Prospecto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProspectoController extends Controller
{
    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            CommercialLeadStatus::New->value => 'nuevo',
            CommercialLeadStatus::Analyzed->value => 'analizado',
            CommercialLeadStatus::ReadyToContact->value => 'listo para contactar',
            CommercialLeadStatus::InContact->value => 'en contacto',
            CommercialLeadStatus::Responded->value => 'respondio',
            CommercialLeadStatus::Interested->value => 'interesado',
            CommercialLeadStatus::NotInterested->value => 'no interesado',
            CommercialLeadStatus::Closed->value => 'cerrado',
        ];
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'estatus' => ['nullable', 'string', 'max:40'],
            'sort' => ['nullable', 'in:newest,rating_desc,reviews_desc'],
        ]);

        $sort = $filters['sort'] ?? 'newest';

        $query = Prospecto::query()
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = trim((string) $filters['search']);
                $q->where(function ($inner) use ($search) {
                    $inner->where('nombre', 'like', "%{$search}%")
                        ->orWhere('ciudad', 'like', "%{$search}%")
                        ->orWhere('giro', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['campaign_id']), fn ($q) => $q->where('campaign_id', $filters['campaign_id']))
            ->when(! empty($filters['estatus']), fn ($q) => $q->where('estatus', $filters['estatus']));

        if ($sort === 'rating_desc') {
            $query->orderByDesc('calificacion')->orderByDesc('id');
        } elseif ($sort === 'reviews_desc') {
            $query->orderByDesc('num_resenas')->orderByDesc('id');
        } else {
            $query->latest('id');
        }

        $filteredCount = (clone $query)->count();

        $prospectos = $query->limit(100)->get();

        $campaignOptions = Campaign::query()
            ->latest('id')
            ->limit(40)
            ->get(['id', 'name']);

        return Inertia::render('endex/prospectos', [
            'prospectos' => $prospectos,
            'campaignOptions' => $campaignOptions,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'campaign_id' => isset($filters['campaign_id']) ? (string) $filters['campaign_id'] : '',
                'estatus' => $filters['estatus'] ?? '',
                'sort' => $sort,
            ],
            'summary' => [
                'total' => Prospecto::query()->count(),
                'nuevos' => Prospecto::query()->whereIn('estatus', [
                    CommercialLeadStatus::New->value,
                    'nuevo',
                    'nuevo_lead',
                ])->count(),
                'contactados' => Prospecto::query()->whereIn('estatus', [
                    CommercialLeadStatus::ReadyToContact->value,
                    CommercialLeadStatus::InContact->value,
                    CommercialLeadStatus::Responded->value,
                    CommercialLeadStatus::Interested->value,
                    CommercialLeadStatus::Closed->value,
                    'contactado',
                    'respondio',
                    'en_conversacion',
                    'propuesta_enviada',
                    'cerrado',
                ])->count(),
                'en_analisis' => Prospecto::query()->whereIn('estatus', [CommercialLeadStatus::Analyzed->value, 'en_analisis', 'analizado', 'calificado'])->count(),
                'analizados' => Prospecto::query()->whereIn('estatus', [CommercialLeadStatus::Analyzed->value, 'en_analisis', 'analizado', 'calificado'])->count(),
                'filtered' => $filteredCount,
            ],
        ]);
    }

    public function analyze(AnalyzeProspectosRequest $request): RedirectResponse
    {
        $selectedIds = (array) $request->validated('prospecto_ids');

        /** @var Collection<int, Prospecto> $prospectos */
        $prospectos = Prospecto::query()
            ->with('campaign')
            ->whereIn('id', $selectedIds)
            ->whereNotNull('campaign_id')
            ->whereIn('estatus', [CommercialLeadStatus::New->value, 'nuevo', 'nuevo_lead'])
            ->get();

        $created = 0;

        foreach ($prospectos as $prospecto) {
            $lead = Lead::query()->create([
                'campaign_id' => $prospecto->campaign_id,
                'campaign_run_id' => $prospecto->campaign_run_id,
                'company_name' => $prospecto->nombre,
                'website_url' => $prospecto->sitio_web,
                'city' => $prospecto->ciudad,
                'sector' => $prospecto->giro ?? $prospecto->categoria,
                'source' => $prospecto->fuente ?? 'Google Maps',
                'is_processing' => true,
                'processing_current_stage' => 'Hefesto',
                'processing_stage_number' => 2,
                'processing_total_stages' => 10,
            ]);

            $this->seedScraperData($lead, $prospecto);

            $prospecto->update(['estatus' => CommercialLeadStatus::Analyzed->value]);

            RunHefestoWebAuditJob::dispatch($lead->id);

            $created++;
        }

        $skipped = max(0, count($selectedIds) - $prospectos->count());

        if ($created === 0) {
            return redirect()->route('endex.prospectos.index')
                ->with('success', 'No se enviaron prospectos al pipeline. Verifica que esten en estatus nuevo y con campana asociada.');
        }

        $message = "{$created} prospectos enviados al pipeline de agentes.";

        if ($skipped > 0) {
            $message .= " {$skipped} omitidos por no tener campana asociada o por ya estar en analisis.";
        }

        return redirect()->route('endex.prospectos.index')
            ->with('success', $message);
    }

    public function statusList(): Response
    {
        $prospectos = Prospecto::query()
            ->latest('id')
            ->limit(300)
            ->get();

        $candidateCampaignIds = $prospectos->pluck('campaign_id')->filter()->unique()->values();
        $candidateCompanyNames = $prospectos->pluck('nombre')->filter()->unique()->values();

        $leadMap = Lead::query()
            ->with([
                'scores:id,lead_id,total_score',
                'contacts:id,lead_id,email,phone',
                'messages:id,lead_id,subject,body,updated_at',
            ])
            ->when(
                $candidateCampaignIds->isNotEmpty(),
                fn ($query) => $query->whereIn('campaign_id', $candidateCampaignIds->all()),
            )
            ->when(
                $candidateCompanyNames->isNotEmpty(),
                fn ($query) => $query->whereIn('company_name', $candidateCompanyNames->all()),
            )
            ->latest('id')
            ->get(['id', 'campaign_id', 'company_name', 'commercial_status'])
            ->mapWithKeys(function (Lead $lead): array {
                $key = $this->prospectLeadMapKey($lead->campaign_id, $lead->company_name);

                return [$key => $lead];
            });

        $statusListRows = $prospectos->map(function (Prospecto $prospecto) use ($leadMap): array {
            $lead = $leadMap->get($this->prospectLeadMapKey($prospecto->campaign_id, $prospecto->nombre));
            $score = $lead?->scores->first()?->total_score;
            $message = $lead?->messages->sortByDesc('id')->first();
            $contact = $lead?->contacts->first();
            $hasContact = filled($contact?->email)
                || filled($contact?->phone)
                || filled($prospecto->telefono);
            $priority = $this->priorityFromSignals($score, $hasContact);

            return [
                ...$prospecto->toArray(),
                'lead_id' => $lead?->id,
                'commercial_status' => $lead?->commercial_status?->value
                    ?? CommercialLeadStatus::fromProspectoStatus($prospecto->estatus)->value,
                'score' => $score,
                'has_contact' => $hasContact,
                'priority' => $priority,
                'review_url' => $lead ? route('endex.leads.review.show', $lead) : null,
                'generated_message' => $message ? [
                    'subject' => $message->subject,
                    'body' => $message->body,
                    'updated_at' => $message->updated_at?->toIso8601String(),
                ] : null,
                'email' => $contact?->email,
            ];
        })->values();

        return Inertia::render('endex/prospectos-status-list', [
            'prospectos' => $statusListRows,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function updateStatus(UpdateProspectoStatusRequest $request, Prospecto $prospecto): RedirectResponse
    {
        $validated = $request->validated();

        $estatus = (string) $validated['estatus'];

        $commercialStatus = CommercialLeadStatus::from($estatus);

        $prospecto->update([
            'estatus' => $commercialStatus->value,
            'notas' => $validated['notas'] ?? null,
            'contactado' => in_array($commercialStatus, [
                CommercialLeadStatus::ReadyToContact,
                CommercialLeadStatus::InContact,
                CommercialLeadStatus::Responded,
                CommercialLeadStatus::Interested,
                CommercialLeadStatus::Closed,
            ], true),
        ]);

        Lead::query()
            ->where('campaign_id', $prospecto->campaign_id)
            ->where('company_name', $prospecto->nombre)
            ->latest('id')
            ->limit(1)
            ->update(['commercial_status' => $commercialStatus->value]);

        return back()->with('success', 'Prospecto actualizado.');
    }

    /**
     * @return array{value:string,label:string,color:string}
     */
    private function priorityFromSignals(mixed $score, bool $hasContact): array
    {
        if (! is_numeric($score)) {
            return [
                'value' => 'unknown',
                'label' => 'Datos insuficientes',
                'color' => 'slate',
            ];
        }

        $numericScore = (float) $score;

        if ($numericScore >= 70 && $hasContact) {
            return [
                'value' => 'high',
                'label' => 'Alto potencial',
                'color' => 'rose',
            ];
        }

        if ($numericScore >= 40 && $numericScore <= 69) {
            return [
                'value' => 'medium',
                'label' => 'Medio',
                'color' => 'amber',
            ];
        }

        if ($numericScore < 40) {
            return [
                'value' => 'low',
                'label' => 'Bajo',
                'color' => 'zinc',
            ];
        }

        return [
            'value' => 'unknown',
            'label' => 'Datos insuficientes',
            'color' => 'slate',
        ];
    }

    private function prospectLeadMapKey(?int $campaignId, ?string $companyName): string
    {
        return (string) $campaignId.'|'.mb_strtolower(trim((string) $companyName));
    }

    private function seedScraperData(Lead $lead, Prospecto $prospecto): void
    {
        $mapsPayload = array_filter([
            'maps_url' => $prospecto->url_maps,
            'rating' => $prospecto->calificacion ? (float) $prospecto->calificacion : null,
            'reviews_count' => $prospecto->num_resenas,
            'phone' => $prospecto->telefono,
            'website' => $prospecto->sitio_web,
            'address' => $prospecto->direccion,
            'latitude' => null,
            'longitude' => null,
            'place_id' => null,
            'query' => trim($prospecto->nombre.' '.($prospecto->ciudad ?? '')),
            'source_url' => 'scraper.py',
            'maps_signals' => array_values(array_filter([
                $prospecto->url_maps ? 'maps_place_found' : null,
                $prospecto->calificacion ? 'maps_rating_found' : null,
                $prospecto->num_resenas ? 'maps_reviews_found' : null,
                $prospecto->telefono ? 'maps_phone_found' : null,
                $prospecto->sitio_web ? 'maps_website_found' : null,
                $prospecto->direccion ? 'maps_address_found' : null,
            ])),
        ], fn ($v) => $v !== null);

        $signals = $mapsPayload['maps_signals'] ?? [];

        LeadFinding::query()->create([
            'lead_id' => $lead->id,
            'agent_name' => 'Argos',
            'stage' => AgentStage::LeadDetection,
            'summary' => 'Datos pre-cargados del scraper de Google Maps.',
            'evidence' => [
                'Datos extraidos por scraper.py de Google Maps.',
                $prospecto->calificacion ? sprintf('Rating: %.1f con %d reseñas.', $prospecto->calificacion, $prospecto->num_resenas ?? 0) : null,
                $prospecto->telefono ? sprintf('Telefono: %s', $prospecto->telefono) : null,
                $prospecto->sitio_web ? sprintf('Sitio: %s', $prospecto->sitio_web) : null,
            ],
            'payload' => [
                'has_website' => filled($prospecto->sitio_web),
                'website_url' => $prospecto->sitio_web,
                'detected_signals' => $signals,
                'maps' => $mapsPayload,
                'analysis_source' => 'scraper_preseed',
            ],
            'confidence' => $prospecto->calificacion ? 0.88 : 0.75,
        ]);

        if (filled($prospecto->telefono) || filled($prospecto->url_maps)) {
            LeadContact::query()->create([
                'lead_id' => $lead->id,
                'phone' => $prospecto->telefono,
                'contact_form_url' => $prospecto->url_maps,
                'contact_name' => $prospecto->nombre.' Contacto',
                'source_confidence' => 0.90,
            ]);
        }
    }
}

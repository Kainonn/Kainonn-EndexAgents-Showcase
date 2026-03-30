<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Http\Requests\LeadInboxRequest;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFinding;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const AGENT_ORDER = ['Argos', 'Hefesto', 'Tique', 'Minos', 'Temis', 'Hermes', 'Caliope', 'Nestor', 'Hestia', 'Mnemosine'];

    public function inbox(LeadInboxRequest $request): Response
    {
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'newest';

        $leadQuery = $this->buildInboxQuery($filters);

        if ($sort === 'priority_desc') {
            $leadQuery->orderByDesc('priority')->orderByDesc('id');
        } elseif ($sort === 'score_desc') {
            $leadQuery->orderByDesc('top_score')->orderByDesc('priority')->orderByDesc('id');
        } else {
            $leadQuery->latest();
        }

        $filteredResults = (clone $leadQuery)->count();

        $leads = $leadQuery
            ->limit(80)
            ->get([
                'id',
                'campaign_id',
                'company_name',
                'city',
                'sector',
                'status',
                'priority',
                'latest_confidence',
                'created_at',
            ])
            ->map(fn (Lead $lead): array => $this->transformInboxLead($lead))
            ->values();

        $campaignOptions = Campaign::query()
            ->latest('id')
            ->limit(40)
            ->get(['id', 'name']);

        return Inertia::render('endex/lead-inbox', [
            'leads' => $leads,
            'campaignOptions' => $campaignOptions,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'campaign_id' => isset($filters['campaign_id']) ? (string) $filters['campaign_id'] : '',
                'agent_gap' => $filters['agent_gap'] ?? '',
                'min_score' => isset($filters['min_score']) ? (string) $filters['min_score'] : '',
                'priority_min' => isset($filters['priority_min']) ? (string) $filters['priority_min'] : '',
                'priority_max' => isset($filters['priority_max']) ? (string) $filters['priority_max'] : '',
                'sort' => $sort,
            ],
            'summary' => [
                'total_leads' => Lead::query()->count(),
                'pending_review' => Lead::query()->where('status', LeadStatus::PendingHumanReview)->count(),
                'approved_today' => Lead::query()
                    ->where('status', LeadStatus::Approved)
                    ->whereDate('updated_at', today())
                    ->count(),
                'filtered_results' => $filteredResults,
            ],
        ]);
    }

    public function exportCsv(LeadInboxRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $leads = $this->buildInboxQuery($filters)
            ->latest('id')
            ->limit(500)
            ->get([
                'id',
                'campaign_id',
                'company_name',
                'city',
                'sector',
                'status',
                'priority',
                'latest_confidence',
                'created_at',
            ])
            ->map(fn (Lead $lead): array => $this->transformInboxLead($lead))
            ->values()
            ->all();

        return response()->streamDownload(function () use ($leads): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            $headers = [
                'lead_id',
                'campaign',
                'company_name',
                'status',
                'priority',
                'score',
                'offer',
                'city',
                'sector',
                'maps_url',
                'website',
                'phone',
                'Argos',
                'Hefesto',
                'Tique',
                'Minos',
                'Temis',
                'Hermes',
                'Caliope',
                'Nestor',
                'Hestia',
                'Mnemosine',
            ];

            fputcsv($output, $headers);

            foreach ($leads as $lead) {
                fputcsv($output, [
                    $lead['id'],
                    $lead['campaign']['name'] ?? '',
                    $lead['company_name'],
                    $lead['status'],
                    $lead['priority'],
                    $lead['scores'][0]['total_score'] ?? '',
                    $lead['offers'][0]['offer_type'] ?? '',
                    $lead['city'] ?? '',
                    $lead['sector'] ?? '',
                    $lead['insights']['maps_url'] ?? '',
                    $lead['insights']['website'] ?? '',
                    $lead['insights']['phone'] ?? '',
                    ($lead['agent_progress']['Argos'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Hefesto'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Tique'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Minos'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Temis'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Hermes'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Caliope'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Nestor'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Hestia'] ?? false) ? 'yes' : 'no',
                    ($lead['agent_progress']['Mnemosine'] ?? false) ? 'yes' : 'no',
                ]);
            }

            fclose($output);
        }, 'lead-inbox-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildInboxQuery(array $filters): Builder
    {
        return Lead::query()
            ->with([
                'campaign:id,name,solution_name',
                'scores:id,lead_id,total_score',
                'offers:id,lead_id,offer_type',
                'contacts:id,lead_id,email,phone,whatsapp,contact_form_url,contact_name',
                'findings' => fn ($query) => $query
                    ->select('id', 'lead_id', 'agent_name', 'payload')
                    ->whereIn('agent_name', self::AGENT_ORDER)
                    ->latest('id'),
            ])
            ->withMax('scores as top_score', 'total_score')
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('company_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('sector', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['campaign_id']), fn ($query) => $query->where('campaign_id', $filters['campaign_id']))
            ->when(! empty($filters['agent_gap']), fn ($query) => $query->whereDoesntHave('findings', fn ($findingQuery) => $findingQuery->where('agent_name', $filters['agent_gap'])))
            ->when(isset($filters['priority_min']), fn ($query) => $query->where('priority', '>=', $filters['priority_min']))
            ->when(isset($filters['priority_max']), fn ($query) => $query->where('priority', '<=', $filters['priority_max']))
            ->when(isset($filters['min_score']), function ($query) use ($filters) {
                $query->whereHas('scores', fn ($scoreQuery) => $scoreQuery->where('total_score', '>=', $filters['min_score']));
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function transformInboxLead(Lead $lead): array
    {
        $argosFinding = $lead->findings->first(fn (LeadFinding $finding): bool => $finding->agent_name === 'Argos');
        $hefestoFinding = $lead->findings->first(fn (LeadFinding $finding): bool => $finding->agent_name === 'Hefesto');
        $mapsPayload = $this->extractNestedArray($argosFinding?->payload, 'maps');
        $seoPayload = $this->extractNestedArray($hefestoFinding?->payload, 'seo_ux');
        $contact = $lead->contacts->first();
        $agentProgress = collect(self::AGENT_ORDER)
            ->mapWithKeys(fn (string $agent): array => [$agent => $lead->findings->contains(fn (LeadFinding $finding): bool => $finding->agent_name === $agent)])
            ->all();

        return [
            'id' => (int) $lead->getKey(),
            'company_name' => $lead->company_name,
            'city' => $lead->city,
            'sector' => $lead->sector,
            'status' => $lead->status->value,
            'priority' => $lead->priority,
            'latest_confidence' => $lead->latest_confidence,
            'campaign' => $lead->campaign,
            'scores' => $lead->scores,
            'offers' => $lead->offers,
            'agent_progress' => $agentProgress,
            'insights' => [
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
                'address' => $mapsPayload['address'] ?? null,
                'http_status' => $seoPayload['http_status'] ?? null,
            ],
        ];
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
}

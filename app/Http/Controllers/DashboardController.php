<?php

namespace App\Http\Controllers;

use App\Enums\CommercialLeadStatus;
use App\Models\Lead;
use App\Models\LeadFinding;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $leads = Lead::query()
            ->with([
                'campaign:id,name',
                'activities:id,lead_id,event',
                'messages:id,lead_id,subject,body,version',
                'offers:id,lead_id,offer_type',
                'findings' => fn ($query) => $query
                    ->select('id', 'lead_id', 'agent_name', 'payload')
                    ->whereIn('agent_name', ['Argos', 'Minos', 'Hermes', 'Caliope', 'Hestia'])
                    ->latest('id'),
            ])
            ->get(['id', 'campaign_id', 'sector', 'status', 'commercial_status']);

        $campaignSummary = $this->buildCampaignSummary($leads);
        $agentMetrics = $this->buildAgentMetrics($leads);
        $globalSummary = $this->buildGlobalSummary($leads, $campaignSummary);

        return Inertia::render('dashboard', [
            'salesMatrix' => [
                'global' => $globalSummary,
                'by_campaign' => $campaignSummary,
                'by_agent' => $agentMetrics,
            ],
        ]);
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<int, array<string, mixed>>
     */
    private function buildCampaignSummary(Collection $leads): array
    {
        return $leads
            ->groupBy('campaign_id')
            ->map(function (Collection $campaignLeads): array {
                $generated = $campaignLeads->count();
                $accepted = $campaignLeads->filter(fn (Lead $lead): bool => $this->isArgosAccepted($lead))->count();
                $contacted = $campaignLeads->filter(fn (Lead $lead): bool => $this->isContacted($lead))->count();
                $responded = $campaignLeads->filter(fn (Lead $lead): bool => $this->isResponded($lead))->count();
                $converted = $campaignLeads->filter(fn (Lead $lead): bool => $this->isConverted($lead))->count();

                $channelResponseRates = collect(['whatsapp', 'phone', 'email'])
                    ->mapWithKeys(function (string $channel) use ($campaignLeads): array {
                        $channelLeads = $campaignLeads->filter(function (Lead $lead) use ($channel): bool {
                            return $this->extractHermesChannel($lead) === $channel && $this->isContacted($lead);
                        });

                        $channelContacted = $channelLeads->count();
                        $channelResponded = $channelLeads->filter(fn (Lead $lead): bool => $this->isResponded($lead))->count();

                        return [
                            $channel => $this->percent($channelResponded, $channelContacted),
                        ];
                    })
                    ->all();

                $hestiaReviewed = $campaignLeads->filter(fn (Lead $lead): bool => $this->hestiaFinding($lead) !== null)->count();
                $hestiaCorrected = $campaignLeads->filter(fn (Lead $lead): bool => $this->isHestiaCorrected($lead))->count();

                $offerWithNoResponse = $campaignLeads->filter(function (Lead $lead): bool {
                    $offerType = optional($lead->offers->first())->offer_type;

                    return is_string($offerType) && $offerType !== '' && ! $this->isResponded($lead);
                })->count();

                $lossMap = [
                    'leads_malos' => $this->percent(max(0, $generated - $accepted), $generated),
                    'mensajes_malos' => $this->percent($hestiaCorrected, max(1, $hestiaReviewed)),
                    'canal_incorrecto' => max(0.0, 100.0 - max($channelResponseRates['whatsapp'], $channelResponseRates['phone'], $channelResponseRates['email'])),
                    'oferta_incorrecta' => $this->percent($offerWithNoResponse, max(1, $contacted)),
                ];

                arsort($lossMap);

                return [
                    'campaign_id' => $campaignLeads->first()?->campaign_id,
                    'campaign_name' => $campaignLeads->first()?->campaign?->name ?? 'Sin campana',
                    'leads_generated' => $generated,
                    'leads_contacted' => $contacted,
                    'responses_received' => $responded,
                    'conversions' => $converted,
                    'lead_useful_count' => $accepted,
                    'ratios' => [
                        'response_rate' => $this->percent($responded, $contacted),
                        'conversion_rate' => $this->percent($converted, $contacted),
                        'lead_useful_rate' => $this->percent($accepted, $generated),
                    ],
                    'money_leak' => [
                        'by_source' => $lossMap,
                        'top_source' => array_key_first($lossMap),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<string, mixed>
     */
    private function buildAgentMetrics(Collection $leads): array
    {
        $argosAccepted = $leads->filter(fn (Lead $lead): bool => $this->isArgosAccepted($lead));
        $argosDiscarded = $leads->filter(fn (Lead $lead): bool => ! $this->isArgosAccepted($lead));

        $minosTierStats = collect(['ALTO', 'MEDIO', 'BAJO'])
            ->mapWithKeys(function (string $tier) use ($leads): array {
                $tierLeads = $leads->filter(fn (Lead $lead): bool => $this->extractMinosTier($lead) === $tier);
                $tierTotal = $tierLeads->count();
                $tierResponded = $tierLeads->filter(fn (Lead $lead): bool => $this->isResponded($lead))->count();

                return [
                    strtolower($tier) => [
                        'total' => $tierTotal,
                        'responded' => $tierResponded,
                        'response_rate' => $this->percent($tierResponded, $tierTotal),
                    ],
                ];
            })
            ->all();

        $lowTierTotal = $minosTierStats['bajo']['total'] ?? 0;
        $lowTierResponded = $minosTierStats['bajo']['responded'] ?? 0;

        $hermesByChannel = collect(['whatsapp', 'phone', 'email'])
            ->map(function (string $channel) use ($leads): array {
                $channelContacted = $leads->filter(function (Lead $lead) use ($channel): bool {
                    return $this->extractHermesChannel($lead) === $channel && $this->isContacted($lead);
                });

                $contactedCount = $channelContacted->count();
                $respondedCount = $channelContacted->filter(fn (Lead $lead): bool => $this->isResponded($lead))->count();

                return [
                    'channel' => $channel,
                    'contacted' => $contactedCount,
                    'responded' => $respondedCount,
                    'response_rate' => $this->percent($respondedCount, $contactedCount),
                ];
            })
            ->all();

        $caliopeByVariant = collect(['A', 'B'])
            ->map(function (string $variant) use ($leads): array {
                $variantContacted = $leads->filter(function (Lead $lead) use ($variant): bool {
                    return $this->extractCaliopeVariant($lead) === $variant && $this->isContacted($lead);
                });

                $contactedCount = $variantContacted->count();
                $respondedCount = $variantContacted->filter(fn (Lead $lead): bool => $this->isResponded($lead))->count();

                return [
                    'variant' => $variant,
                    'contacted' => $contactedCount,
                    'responded' => $respondedCount,
                    'response_rate' => $this->percent($respondedCount, $contactedCount),
                ];
            })
            ->all();

        $messageLeads = $leads->filter(fn (Lead $lead): bool => $lead->messages->isNotEmpty());
        $interestedAfterMessage = $messageLeads->filter(fn (Lead $lead): bool => $this->isConverted($lead))->count();

        $hestiaReviewed = $leads->filter(fn (Lead $lead): bool => $this->hestiaFinding($lead) !== null);
        $hestiaCorrected = $hestiaReviewed->filter(fn (Lead $lead): bool => $this->isHestiaCorrected($lead));

        $convertedLeads = $leads->filter(fn (Lead $lead): bool => $this->isConverted($lead));

        $byBusinessType = $convertedLeads
            ->groupBy(fn (Lead $lead): string => trim((string) ($lead->sector ?? 'desconocido')) ?: 'desconocido')
            ->map(fn (Collection $group, string $sector): array => [
                'sector' => $sector,
                'converted_count' => $group->count(),
            ])
            ->sortByDesc('converted_count')
            ->take(5)
            ->values()
            ->all();

        $signals = [];
        foreach ($convertedLeads as $lead) {
            $finding = $this->argosFinding($lead);
            $activitySignals = is_array($finding?->payload['activity_signals'] ?? null)
                ? $finding->payload['activity_signals']
                : [];

            foreach ($activitySignals as $signal) {
                if (! is_string($signal) || trim($signal) === '') {
                    continue;
                }

                $signals[$signal] = ($signals[$signal] ?? 0) + 1;
            }
        }

        arsort($signals);

        return [
            'argos' => [
                'accepted' => $argosAccepted->count(),
                'discarded' => $argosDiscarded->count(),
                'discarded_rate' => $this->percent($argosDiscarded->count(), $leads->count()),
                'accepted_to_contact_rate' => $this->percent(
                    $argosAccepted->filter(fn (Lead $lead): bool => $this->isContacted($lead))->count(),
                    $argosAccepted->count(),
                ),
            ],
            'minos' => [
                'alto_response_rate' => $minosTierStats['alto']['response_rate'] ?? 0.0,
                'medio_response_rate' => $minosTierStats['medio']['response_rate'] ?? 0.0,
                'bajo_no_response_rate' => $this->percent(max(0, $lowTierTotal - $lowTierResponded), $lowTierTotal),
                'tiers' => $minosTierStats,
            ],
            'hermes' => [
                'channels' => $hermesByChannel,
            ],
            'caliope' => [
                'variants' => $caliopeByVariant,
            ],
            'nestor' => [
                'message_to_interest_rate' => $this->percent($interestedAfterMessage, $messageLeads->count()),
                'messages_total' => $messageLeads->count(),
                'interested_after_message' => $interestedAfterMessage,
            ],
            'hestia' => [
                'reviewed_total' => $hestiaReviewed->count(),
                'corrected_total' => $hestiaCorrected->count(),
                'passed_without_changes_total' => max(0, $hestiaReviewed->count() - $hestiaCorrected->count()),
                'corrected_rate' => $this->percent($hestiaCorrected->count(), $hestiaReviewed->count()),
                'pass_without_changes_rate' => $this->percent(max(0, $hestiaReviewed->count() - $hestiaCorrected->count()), $hestiaReviewed->count()),
            ],
            'mnemosine' => [
                'patterns' => [
                    'business_types_that_convert' => $byBusinessType,
                    'top_conversion_signals' => collect($signals)
                        ->map(fn (int $count, string $signal): array => ['signal' => $signal, 'count' => $count])
                        ->take(6)
                        ->values()
                        ->all(),
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<int, array<string, mixed>>  $campaignSummary
     * @return array<string, mixed>
     */
    private function buildGlobalSummary(Collection $leads, array $campaignSummary): array
    {
        $generated = $leads->count();
        $accepted = $leads->filter(fn (Lead $lead): bool => $this->isArgosAccepted($lead))->count();
        $contacted = $leads->filter(fn (Lead $lead): bool => $this->isContacted($lead))->count();
        $responded = $leads->filter(fn (Lead $lead): bool => $this->isResponded($lead))->count();
        $converted = $leads->filter(fn (Lead $lead): bool => $this->isConverted($lead))->count();

        $globalLoss = collect($campaignSummary)
            ->pluck('money_leak.by_source')
            ->filter(fn (mixed $value): bool => is_array($value))
            ->reduce(function (array $carry, array $losses): array {
                foreach ($losses as $source => $value) {
                    $carry[$source] = ($carry[$source] ?? 0) + (float) $value;
                }

                return $carry;
            }, []);

        arsort($globalLoss);

        return [
            'leads_generated' => $generated,
            'leads_contacted' => $contacted,
            'responses_received' => $responded,
            'conversions' => $converted,
            'lead_useful_count' => $accepted,
            'ratios' => [
                'response_rate' => $this->percent($responded, $contacted),
                'conversion_rate' => $this->percent($converted, $contacted),
                'lead_useful_rate' => $this->percent($accepted, $generated),
            ],
            'money_leak' => [
                'by_source' => $globalLoss,
                'top_source' => array_key_first($globalLoss),
            ],
        ];
    }

    private function isResponded(Lead $lead): bool
    {
        $status = $lead->commercial_status;

        return in_array($status, [
            CommercialLeadStatus::Responded,
            CommercialLeadStatus::Interested,
            CommercialLeadStatus::Closed,
        ], true);
    }

    private function isConverted(Lead $lead): bool
    {
        $status = $lead->commercial_status;

        return in_array($status, [
            CommercialLeadStatus::Interested,
            CommercialLeadStatus::Closed,
        ], true);
    }

    private function isContacted(Lead $lead): bool
    {
        $status = $lead->commercial_status;
        $statusContacted = in_array($status, [
            CommercialLeadStatus::InContact,
            CommercialLeadStatus::Responded,
            CommercialLeadStatus::Interested,
            CommercialLeadStatus::Closed,
        ], true);

        if ($statusContacted) {
            return true;
        }

        return $lead->activities->contains(fn ($activity): bool => $activity->event === 'outreach.sent');
    }

    private function isArgosAccepted(Lead $lead): bool
    {
        $finding = $this->argosFinding($lead);
        $value = $finding?->payload['should_continue_pipeline'] ?? null;

        if (is_bool($value)) {
            return $value;
        }

        return true;
    }

    private function extractMinosTier(Lead $lead): ?string
    {
        $finding = $lead->findings->first(fn (LeadFinding $finding): bool => $finding->agent_name === 'Minos');
        $tier = $finding?->payload['commercial_priority'] ?? null;

        return is_string($tier) ? strtoupper($tier) : null;
    }

    private function extractHermesChannel(Lead $lead): ?string
    {
        $finding = $lead->findings->first(fn (LeadFinding $finding): bool => $finding->agent_name === 'Hermes');
        $channel = strtolower((string) ($finding?->payload['channel'] ?? ''));

        return in_array($channel, ['whatsapp', 'phone', 'email'], true) ? $channel : null;
    }

    private function extractCaliopeVariant(Lead $lead): ?string
    {
        $finding = $lead->findings->first(fn (LeadFinding $finding): bool => $finding->agent_name === 'Caliope');

        if (! $finding) {
            return null;
        }

        $variants = is_array($finding->payload['ab_variants'] ?? null)
            ? $finding->payload['ab_variants']
            : [];

        $message = $lead->messages->sortByDesc('version')->first();

        if (! $message) {
            return is_array($variants[0] ?? null) ? strtoupper((string) ($variants[0]['label'] ?? 'A')) : 'A';
        }

        foreach ($variants as $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $sameSubject = trim((string) ($variant['subject'] ?? '')) === trim((string) $message->subject);
            $sameBody = trim((string) ($variant['body'] ?? '')) === trim((string) $message->body);

            if ($sameSubject || $sameBody) {
                return strtoupper((string) ($variant['label'] ?? 'A'));
            }
        }

        return 'A';
    }

    private function isHestiaCorrected(Lead $lead): bool
    {
        $finding = $this->hestiaFinding($lead);

        if (! $finding) {
            return false;
        }

        $corrections = $finding->payload['corrections'] ?? [];
        $salesQualityPassed = $finding->payload['sales_quality_passed'] ?? null;

        return (is_array($corrections) && $corrections !== []) || $salesQualityPassed === false;
    }

    private function argosFinding(Lead $lead): ?LeadFinding
    {
        $finding = $lead->findings->first(fn (LeadFinding $item): bool => $item->agent_name === 'Argos');

        return $finding instanceof LeadFinding ? $finding : null;
    }

    private function hestiaFinding(Lead $lead): ?LeadFinding
    {
        $finding = $lead->findings->first(fn (LeadFinding $item): bool => $item->agent_name === 'Hestia');

        return $finding instanceof LeadFinding ? $finding : null;
    }

    private function percent(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 2);
    }
}

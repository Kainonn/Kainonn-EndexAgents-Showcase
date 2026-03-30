<?php

namespace App\Http\Controllers;

use App\Enums\CampaignRunStatus;
use App\Http\Requests\StartCampaignRunRequest;
use App\Models\Campaign;
use App\Models\CampaignRun;
use App\Services\Leads\ScraperBridgeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampaignRunController extends Controller
{
    public function show(Campaign $campaign): Response
    {
        $campaign->loadCount('leads');

        $runs = $campaign->campaignRuns()
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'status',
                'started_at',
                'finished_at',
                'total_leads_analyzed',
                'total_leads_generated',
                'error_count',
                'created_at',
            ]);

        return Inertia::render('endex/campaign-run', [
            'campaign' => $campaign,
            'runs' => $runs,
        ]);
    }

    public function start(StartCampaignRunRequest $request, Campaign $campaign, ScraperBridgeService $scraper): RedirectResponse
    {
        $validated = $request->validated();
        $targetLeads = $validated['target_leads'];
        $searchState = isset($validated['search_state']) ? trim((string) $validated['search_state']) : null;
        $searchMunicipality = isset($validated['search_municipality']) ? trim((string) $validated['search_municipality']) : null;
        $searchPostalCode = isset($validated['search_postal_code']) ? trim((string) $validated['search_postal_code']) : null;

        $searchState = $searchState !== '' ? $searchState : null;
        $searchMunicipality = $searchMunicipality !== '' ? $searchMunicipality : null;
        $searchPostalCode = $searchPostalCode !== '' ? $searchPostalCode : null;

        $targetSegment = (array) $campaign->target_segments;
        $category = isset($targetSegment[0]) && is_string($targetSegment[0])
            ? $targetSegment[0]
            : $campaign->solution_name;

        $locationParts = array_filter([
            $searchMunicipality,
            $searchState,
            $searchPostalCode ? "CP $searchPostalCode" : null,
        ]);
        $location = implode(', ', $locationParts) ?: 'México';
        $campaignId = (int) $campaign->getKey();

        $campaignRun = CampaignRun::query()->create([
            'campaign_id' => $campaignId,
            'triggered_by_user_id' => $request->user()?->id,
            'status' => CampaignRunStatus::Running,
            'started_at' => now(),
            'total_leads_analyzed' => $targetLeads,
            'total_leads_generated' => 0,
        ]);

        try {
            $campaignRunId = (int) $campaignRun->getKey();

            $prospectos = $scraper->scrape(
                category: $category,
                location: $location,
                limit: $targetLeads,
                campaignId: $campaignId,
                campaignRunId: $campaignRunId,
            );

            $generatedCount = count($prospectos);

            $campaignRun->update([
                'status' => CampaignRunStatus::Completed,
                'finished_at' => now(),
                'total_leads_analyzed' => $generatedCount,
                'total_leads_generated' => $generatedCount,
            ]);
        } catch (\Throwable $e) {
            $campaignRun->update([
                'status' => CampaignRunStatus::Failed,
                'finished_at' => now(),
                'error_count' => 1,
            ]);

            return redirect()
                ->route('endex.campaigns.run.show', $campaign)
                ->with('error', 'Scraper falló: '.$e->getMessage());
        }

        $message = $generatedCount > 0
            ? "Corrida completada: $generatedCount prospectos guardados."
            : 'No se encontraron prospectos para esa zona. Ajusta filtros y vuelve a intentar.';

        return redirect()
            ->route('endex.campaigns.run.show', $campaign)
            ->with($generatedCount > 0 ? 'success' : 'warning', $message);
    }
}

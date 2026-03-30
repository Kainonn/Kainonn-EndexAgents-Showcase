<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Enums\KnowledgeFileType;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function builder(): Response
    {
        $campaigns = Campaign::query()
            ->latest()
            ->limit(12)
            ->get([
                'id',
                'name',
                'slug',
                'solution_name',
                'status',
                'created_at',
            ]);

        return Inertia::render('endex/campaign-builder', [
            'campaigns' => $campaigns,
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $suffix = 1;

        while (Campaign::query()->where('slug', $slug)->exists()) {
            $slug = sprintf('%s-%d', $baseSlug, $suffix++);
        }

        $campaign = Campaign::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'solution_name' => $validated['solution_name'],
            'description' => $validated['description'] ?? null,
            'target_segments' => $validated['target_segments'] ?? [],
            'target_regions' => $validated['target_regions'] ?? [],
            'pain_points' => $validated['pain_points'] ?? [],
            'opportunity_signals' => $validated['opportunity_signals'] ?? [],
            'allowed_offers' => $validated['allowed_offers'] ?? [],
            'commercial_tone' => $validated['commercial_tone'] ?? 'consultivo',
            'status' => CampaignStatus::Active,
            'operational_limits' => [
                'max_leads_per_run' => $validated['max_leads_per_run'] ?? 10,
                'auto_outreach_enabled' => false,
            ],
        ]);

        $knowledgeFiles = [];

        if ($request->hasFile('system_context_file')) {
            $singleFile = $request->file('system_context_file');

            if ($singleFile instanceof UploadedFile) {
                $knowledgeFiles[] = $singleFile;
            }
        }

        if ($request->hasFile('system_context_files')) {
            $multipleFiles = $request->file('system_context_files');

            if (is_array($multipleFiles)) {
                foreach ($multipleFiles as $file) {
                    if ($file instanceof UploadedFile) {
                        $knowledgeFiles[] = $file;
                    }
                }
            }
        }

        foreach ($knowledgeFiles as $file) {
            $content = (string) file_get_contents($file->getRealPath());
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $fileType = $extension === 'md' ? KnowledgeFileType::Md : KnowledgeFileType::Txt;
            $storedPath = $file->storeAs(
                sprintf('campaign-knowledge/%d', $campaign->id),
                Str::uuid().'.'.$extension,
            );

            $campaign->campaignKnowledge()->create([
                'title' => 'Contexto base de la oferta',
                'file_name' => (string) $file->getClientOriginalName(),
                'file_type' => $fileType,
                'storage_path' => $storedPath,
                'raw_content' => $content,
                'parsed_content' => trim($content),
                'status' => 'active',
            ]);
        }

        return redirect()
            ->route('endex.campaigns.run.show', $campaign)
            ->with('success', 'Campana creada correctamente.');
    }
}

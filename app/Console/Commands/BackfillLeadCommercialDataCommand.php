<?php

namespace App\Console\Commands;

use App\Actions\Commercial\RecalculateLeadPriorityAction;
use App\Actions\Commercial\ResolveRecommendedChannelAction;
use App\Models\Lead;
use Illuminate\Console\Command;

class BackfillLeadCommercialDataCommand extends Command
{
    protected $signature = 'leads:backfill-commercial {--dry-run : Mostrar qué se haría sin aplicar cambios}';

    protected $description = 'Recalcula operational_priority y recommended_channel para leads existentes';

    public function handle(
        RecalculateLeadPriorityAction $priorityAction,
        ResolveRecommendedChannelAction $channelAction,
    ): int {
        $dryRun = $this->option('dry-run');

        $leads = Lead::query()
            ->with(['latestScore', 'primaryContact'])
            ->whereNotNull('commercial_status')
            ->lazyById(50);

        $total = 0;
        $updated = 0;

        foreach ($leads as $lead) {
            $total++;

            $oldPriority = $lead->operational_priority?->value ?? 'null';
            $oldChannel = $lead->recommended_channel?->value ?? 'null';

            if ($dryRun) {
                $contact = $lead->primaryContact;
                $score = $lead->latestScore?->total_score;
                $this->line("[DRY] {$lead->company_name} | score={$score} | channel_data: wha=" . ($contact?->whatsapp ?? '-') . " ph=" . ($contact?->phone ?? '-') . " em=" . ($contact?->email ?? '-'));

                continue;
            }

            $newChannel = $channelAction->execute($lead);
            $lead->refresh();
            $lead->load(['latestScore', 'primaryContact']);
            $newPriority = $priorityAction->execute($lead);

            if ($oldPriority !== $newPriority->value || $oldChannel !== $newChannel->value) {
                $updated++;
                $this->line("✓ {$lead->company_name}: priority {$oldPriority}→{$newPriority->value}, channel {$oldChannel}→{$newChannel->value}");
            }
        }

        if ($dryRun) {
            $this->info("Dry run completado. {$total} leads encontrados.");
        } else {
            $this->info("Backfill completado. {$total} procesados, {$updated} actualizados.");
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs\Leads;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeadPipelineFailure
{
    public static function handle(int $leadId, string $stage, Throwable $exception): void
    {
        $lead = Lead::query()->find($leadId);

        if (! $lead) {
            return;
        }

        $lead->update([
            'is_processing' => false,
            'processing_current_stage' => null,
            'processing_stage_number' => null,
            'processing_total_stages' => null,
        ]);

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'actor_type' => 'system',
            'actor_name' => 'pipeline',
            'event' => 'stage.failed',
            'event_data' => [
                'stage' => $stage,
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ],
            'occurred_at' => now(),
        ]);

        Log::error('lead.pipeline.stage_failed', [
            'lead_id' => $lead->id,
            'stage' => $stage,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}

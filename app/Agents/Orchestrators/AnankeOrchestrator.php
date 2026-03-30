<?php

namespace App\Agents\Orchestrators;

use App\Agents\DTOs\PipelinePayload;
use App\Enums\LeadStatus;
use App\Jobs\Leads\RunArgosAnalysisJob;
use App\Jobs\Leads\RunCaliopeMessageJob;
use App\Jobs\Leads\RunHefestoWebAuditJob;
use App\Jobs\Leads\RunHermesContactExtractionJob;
use App\Jobs\Leads\RunHestiaComplianceJob;
use App\Jobs\Leads\RunMinosScoringJob;
use App\Jobs\Leads\RunMnemosineMemoryJob;
use App\Jobs\Leads\RunNestorProposalJob;
use App\Jobs\Leads\RunTemisClassificationJob;
use App\Jobs\Leads\RunTiqueOpportunityJob;

class AnankeOrchestrator
{
    public function startLeadPipeline(PipelinePayload $payload): void
    {
        RunArgosAnalysisJob::dispatch($payload->leadId);
    }

    public function dispatchNextStage(int $leadId, LeadStatus $status): void
    {
        match ($status) {
            LeadStatus::Detected => RunHefestoWebAuditJob::dispatch($leadId),
            LeadStatus::WebAudited => RunTiqueOpportunityJob::dispatch($leadId),
            LeadStatus::Enriched => RunMinosScoringJob::dispatch($leadId),
            LeadStatus::Scored => RunTemisClassificationJob::dispatch($leadId),
            LeadStatus::OfferClassified => RunHermesContactExtractionJob::dispatch($leadId),
            LeadStatus::ContactExtracted => RunCaliopeMessageJob::dispatch($leadId),
            LeadStatus::MessageGenerated => RunNestorProposalJob::dispatch($leadId),
            LeadStatus::ProposalGenerated => RunHestiaComplianceJob::dispatch($leadId),
            LeadStatus::ComplianceChecked => RunMnemosineMemoryJob::dispatch($leadId),
            default => null,
        };
    }
}

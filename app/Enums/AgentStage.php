<?php

namespace App\Enums;

enum AgentStage: string
{
    case LeadDetection = 'lead_detection';
    case LeadWebAudit = 'lead_web_audit';
    case LeadEnrichment = 'lead_enrichment';
    case LeadScoring = 'lead_scoring';
    case OfferClassification = 'offer_classification';
    case LeadContactExtraction = 'lead_contact_extraction';
    case LeadMessageGeneration = 'lead_message_generation';
    case LeadProposalGeneration = 'lead_proposal_generation';
    case LeadComplianceCheck = 'lead_compliance_check';
    case LeadMemoryUpdate = 'lead_memory_update';
    case LeadHumanReview = 'lead_human_review';
}

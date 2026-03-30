<?php

namespace App\Enums;

enum LeadStatus: string
{
    case Detected = 'detected';
    case WebAudited = 'web_audited';
    case Enriched = 'enriched';
    case Scored = 'scored';
    case OfferClassified = 'offer_classified';
    case ContactExtracted = 'contact_extracted';
    case MessageGenerated = 'message_generated';
    case ProposalGenerated = 'proposal_generated';
    case ComplianceChecked = 'compliance_checked';
    case PendingHumanReview = 'pending_human_review';
    case Approved = 'approved';
    case NeedsAdjustment = 'needs_adjustment';
    case Discarded = 'discarded';
}

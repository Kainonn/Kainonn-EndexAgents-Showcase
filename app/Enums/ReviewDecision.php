<?php

namespace App\Enums;

enum ReviewDecision: string
{
    case Approve = 'approve';
    case NeedsAdjustment = 'needs_adjustment';
    case Discard = 'discard';
}

<?php

namespace App\Enums;

enum LeadFeedbackType: string
{
    case BuenLead = 'buen_lead';
    case NoGoodLead = 'no_good_lead';
    case BadData = 'bad_data';
    case NoContact = 'no_contact';
    case Contacted = 'contacted';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}

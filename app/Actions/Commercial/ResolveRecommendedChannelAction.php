<?php

namespace App\Actions\Commercial;

use App\Enums\RecommendedChannel;
use App\Models\Lead;

class ResolveRecommendedChannelAction
{
    public function execute(Lead $lead): RecommendedChannel
    {
        $contact = $lead->primaryContact ?? $lead->contacts()->latest('id')->first();

        if ($contact === null) {
            $lead->update(['recommended_channel' => RecommendedChannel::None]);

            return RecommendedChannel::None;
        }

        $channel = RecommendedChannel::fromContactData(
            $contact->whatsapp,
            $contact->phone,
            $contact->email,
        );

        $lead->update(['recommended_channel' => $channel]);

        return $channel;
    }
}

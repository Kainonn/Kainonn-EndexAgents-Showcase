<?php

namespace App\Actions\Commercial;

use App\Enums\CommercialActionType;
use App\Models\Lead;
use App\Models\LeadCommercialAction;

class RegisterCommercialActionAction
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function execute(
        Lead $lead,
        CommercialActionType $actionType,
        ?int $userId = null,
        ?string $channel = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): LeadCommercialAction {
        $action = LeadCommercialAction::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'action_type' => $actionType,
            'channel' => $channel,
            'notes' => $notes,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);

        // Actualizar last_contacted_at para acciones de contacto
        $contactActions = [
            CommercialActionType::ContactInitiated,
            CommercialActionType::WhatsappOpened,
            CommercialActionType::CallStarted,
            CommercialActionType::EmailPrepared,
        ];

        if (in_array($actionType, $contactActions, true)) {
            $lead->update(['last_contacted_at' => now()]);
        }

        return $action;
    }
}

<?php

namespace App\Actions\Commercial;

use App\Enums\CommercialActionType;
use App\Models\Lead;

class SaveQuickNoteAction
{
    public function __construct(
        private readonly RegisterCommercialActionAction $registerAction,
    ) {}

    public function execute(Lead $lead, string $notes, ?int $userId = null): void
    {
        $lead->update(['commercial_notes' => $notes]);

        $this->registerAction->execute(
            lead: $lead,
            actionType: CommercialActionType::NoteAdded,
            userId: $userId,
            notes: $notes,
        );
    }
}

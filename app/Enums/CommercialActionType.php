<?php

namespace App\Enums;

enum CommercialActionType: string
{
    case ContactInitiated = 'contact_initiated';
    case WhatsappOpened = 'whatsapp_opened';
    case CallStarted = 'call_started';
    case EmailPrepared = 'email_prepared';
    case NoteAdded = 'note_added';
    case StatusUpdated = 'status_updated';
    case MessageEdited = 'message_edited';
    case FollowUpScheduled = 'follow_up_scheduled';

    public function label(): string
    {
        return match ($this) {
            self::ContactInitiated => 'Contacto iniciado',
            self::WhatsappOpened => 'WhatsApp abierto',
            self::CallStarted => 'Llamada iniciada',
            self::EmailPrepared => 'Correo preparado',
            self::NoteAdded => 'Nota agregada',
            self::StatusUpdated => 'Estado actualizado',
            self::MessageEdited => 'Mensaje editado',
            self::FollowUpScheduled => 'Seguimiento programado',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::ContactInitiated => '🚀',
            self::WhatsappOpened => '💬',
            self::CallStarted => '📞',
            self::EmailPrepared => '📧',
            self::NoteAdded => '📝',
            self::StatusUpdated => '🔄',
            self::MessageEdited => '✏️',
            self::FollowUpScheduled => '📅',
        };
    }

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

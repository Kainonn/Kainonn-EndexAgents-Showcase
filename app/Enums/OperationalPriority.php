<?php

namespace App\Enums;

enum OperationalPriority: string
{
    case ContactToday = 'contact_today';
    case ThisWeek = 'this_week';
    case LowPriority = 'low_priority';

    public function label(): string
    {
        return match ($this) {
            self::ContactToday => 'Contactar hoy',
            self::ThisWeek => 'Esta semana',
            self::LowPriority => 'Baja prioridad',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::ContactToday => '🔥',
            self::ThisWeek => '📅',
            self::LowPriority => '⏳',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $priority): string => $priority->value,
            self::cases(),
        );
    }

    /**
     * Determina la prioridad operativa a partir de señales del lead.
     */
    public static function fromSignals(
        ?float $score,
        bool $hasChannel,
        ?string $commercialStatus,
    ): self {
        $isActionable = in_array($commercialStatus, [
            CommercialLeadStatus::New->value,
            CommercialLeadStatus::WaitingResponse->value,
            CommercialLeadStatus::ReadyToContact->value,
        ], true);

        if ($score !== null && $score >= 70 && $hasChannel && $isActionable) {
            return self::ContactToday;
        }

        if ($score !== null && $score >= 40 && $hasChannel) {
            return self::ThisWeek;
        }

        return self::LowPriority;
    }
}

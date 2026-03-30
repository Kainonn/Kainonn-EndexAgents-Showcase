<?php

namespace App\Enums;

enum RecommendedChannel: string
{
    case Whatsapp = 'whatsapp';
    case Phone = 'phone';
    case Email = 'email';
    case Visit = 'visit';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Phone => 'Llamada',
            self::Email => 'Email',
            self::Visit => 'Visita',
            self::None => 'Sin canal',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Whatsapp => '💬',
            self::Phone => '📞',
            self::Email => '📧',
            self::Visit => '🏢',
            self::None => '❌',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $channel): string => $channel->value,
            self::cases(),
        );
    }

    /**
     * Determina el mejor canal de contacto a partir de los datos disponibles.
     */
    public static function fromContactData(
        ?string $whatsapp,
        ?string $phone,
        ?string $email,
    ): self {
        if (filled($whatsapp)) {
            return self::Whatsapp;
        }

        if (filled($phone)) {
            return self::Phone;
        }

        if (filled($email)) {
            return self::Email;
        }

        return self::None;
    }
}

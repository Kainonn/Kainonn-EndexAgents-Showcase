<?php

namespace App\Enums;

enum CommercialLeadStatus: string
{
    case New = 'new';
    case Analyzed = 'analyzed';
    case ReadyToContact = 'ready_to_contact';
    case InContact = 'in_contact';
    case WaitingResponse = 'waiting_response';
    case Responded = 'responded';
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Analyzed => 'Analizado',
            self::ReadyToContact => 'Listo para contactar',
            self::InContact => 'Contactado',
            self::WaitingResponse => 'Esperando respuesta',
            self::Responded => 'Respondió',
            self::Interested => 'Interesado',
            self::NotInterested => 'Perdido',
            self::Closed => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Analyzed => 'indigo',
            self::ReadyToContact => 'cyan',
            self::InContact => 'amber',
            self::WaitingResponse => 'orange',
            self::Responded => 'emerald',
            self::Interested => 'green',
            self::NotInterested => 'red',
            self::Closed => 'zinc',
        };
    }

    /**
     * Estados visibles en el flujo comercial (Centro de Ataque).
     * Excluye "analyzed" y "ready_to_contact" que son estados internos del pipeline.
     *
     * @return list<self>
     */
    public static function commercialFlow(): array
    {
        return [
            self::New,
            self::InContact,
            self::WaitingResponse,
            self::Responded,
            self::Interested,
            self::Closed,
            self::NotInterested,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public static function transitionMap(): array
    {
        return [
            self::New->value => [
                self::Analyzed->value,
                self::ReadyToContact->value,
                self::InContact->value,
                self::WaitingResponse->value,
                self::NotInterested->value,
            ],
            self::Analyzed->value => [
                self::ReadyToContact->value,
                self::InContact->value,
                self::WaitingResponse->value,
                self::NotInterested->value,
            ],
            self::ReadyToContact->value => [
                self::InContact->value,
                self::WaitingResponse->value,
                self::NotInterested->value,
            ],
            self::InContact->value => [
                self::WaitingResponse->value,
                self::Responded->value,
                self::NotInterested->value,
            ],
            self::WaitingResponse->value => [
                self::Responded->value,
                self::InContact->value,
                self::NotInterested->value,
            ],
            self::Responded->value => [
                self::Interested->value,
                self::WaitingResponse->value,
                self::NotInterested->value,
            ],
            self::Interested->value => [
                self::Closed->value,
                self::NotInterested->value,
            ],
            self::NotInterested->value => [
                self::New->value,
            ],
            self::Closed->value => [],
        ];
    }

    public static function canTransition(self $from, self $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to->value, self::transitionMap()[$from->value] ?? [], true);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function commercialFlowOptions(): array
    {
        return collect(self::commercialFlow())
            ->mapWithKeys(fn (self $status): array => [
                $status->value => [
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
            ])
            ->all();
    }

    public static function fromProspectoStatus(?string $value): self
    {
        return match ($value) {
            'new', 'nuevo', 'nuevo_lead' => self::New,
            'analyzed', 'analizado', 'en_analisis', 'calificado' => self::Analyzed,
            'ready_to_contact' => self::ReadyToContact,
            'in_contact', 'en_conversacion', 'contactado', 'propuesta_enviada' => self::InContact,
            'waiting_response', 'esperando_respuesta' => self::WaitingResponse,
            'responded', 'respondio' => self::Responded,
            'interested', 'interesado' => self::Interested,
            'closed', 'cerrado' => self::Closed,
            'not_interested', 'perdido', 'descartado' => self::NotInterested,
            default => self::New,
        };
    }
}

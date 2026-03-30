<?php

namespace App\Actions\Commercial;

use App\Enums\CommercialActionType;
use App\Enums\RecommendedChannel;
use App\Models\Lead;
use App\Models\LeadMessage;

class PrepareContactAction
{
    public function __construct(
        private readonly RegisterCommercialActionAction $registerAction,
    ) {}

    /**
     * Prepara todo lo necesario para contacto sin fricción y registra la acción.
     *
     * @return array{
     *     channel: string,
     *     channel_label: string,
     *     phone_clean: string|null,
     *     whatsapp_url: string|null,
     *     tel_url: string|null,
     *     mailto_url: string|null,
     *     subject: string|null,
     *     body: string|null,
     *     copied_text: string|null,
     *     contact_name: string|null,
     * }
     */
    public function execute(Lead $lead, ?int $userId = null): array
    {
        $lead->load(['primaryContact', 'messages']);

        $contact = $lead->primaryContact;
        $effective = $this->resolveEffectiveMessage($lead);
        $channel = $lead->recommended_channel ?? RecommendedChannel::None;

        $phone = $this->cleanPhone($contact?->whatsapp ?? $contact?->phone);
        $email = $contact?->email;
        $subject = $effective['subject'];
        $body = $effective['body'];

        $result = [
            'channel' => $channel->value,
            'channel_label' => $channel->label(),
            'phone_clean' => null,
            'whatsapp_url' => null,
            'tel_url' => null,
            'mailto_url' => null,
            'subject' => $subject,
            'body' => $body,
            'copied_text' => null,
            'contact_name' => $contact?->contact_name,
        ];

        $actionType = CommercialActionType::ContactInitiated;

        if ($channel === RecommendedChannel::Whatsapp && $phone !== null) {
            $result['phone_clean'] = $phone;
            $result['whatsapp_url'] = 'https://wa.me/' . $phone . ($body ? '?text=' . rawurlencode($this->truncateForWhatsApp($body)) : '');
            $result['copied_text'] = $body;
            $actionType = CommercialActionType::WhatsappOpened;
        } elseif ($channel === RecommendedChannel::Phone && $phone !== null) {
            $result['phone_clean'] = $phone;
            $result['tel_url'] = 'tel:+' . $phone;
            $result['copied_text'] = $this->buildSpeechSnippet($lead, $body);
            $actionType = CommercialActionType::CallStarted;
        } elseif ($channel === RecommendedChannel::Email && $email !== null) {
            $emailSubject = $subject ?? 'Propuesta para ' . $lead->company_name;
            $result['mailto_url'] = 'mailto:' . rawurlencode($email)
                . '?subject=' . rawurlencode($emailSubject)
                . ($body ? '&body=' . rawurlencode($body) : '');
            $result['copied_text'] = $body;
            $actionType = CommercialActionType::EmailPrepared;
        } else {
            $result['copied_text'] = $body;
        }

        $this->registerAction->execute(
            lead: $lead,
            actionType: $actionType,
            userId: $userId,
            channel: $channel->value,
            metadata: [
                'phone' => $result['phone_clean'],
                'email' => $email,
                'has_message' => $body !== null,
                'message_source' => $effective['source'],
            ],
        );

        return $result;
    }

    private function cleanPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        // Si empieza con 52 y tiene 12 dígitos => México
        if (strlen($digits) === 10) {
            return '52' . $digits;
        }

        return $digits;
    }

    private function truncateForWhatsApp(string $text): string
    {
        // WhatsApp URL tiene un límite práctico, truncar a ~1000 chars
        if (mb_strlen($text) <= 1000) {
            return $text;
        }

        return mb_substr($text, 0, 997) . '...';
    }

    private function buildSpeechSnippet(Lead $lead, ?string $body): string
    {
        $name = $lead->company_name;
        $angle = $lead->sales_angle;

        $speech = "Hola, buenas tardes. Le llamo de Endex Technologies respecto a {$name}.";

        if ($angle) {
            $speech .= " {$angle}";
        }

        if ($body && mb_strlen($body) <= 200) {
            $speech .= "\n\n" . $body;
        }

        return $speech;
    }

    /**
     * @return array{
     *   id:int|null,
     *   subject:string,
     *   body:string,
     *   preview:string,
     *   source:string,
     *   updated_at:string|null,
     * }
     */
    private function resolveEffectiveMessage(Lead $lead): array
    {
        $messages = $lead->relationLoaded('messages')
            ? $lead->messages
            : $lead->messages()->latest('id')->get();

        $humanMessage = $messages
            ->filter(fn (LeadMessage $message): bool => $this->isHumanEdited($message))
            ->sortByDesc('id')
            ->first();

        if ($humanMessage !== null && trim((string) $humanMessage->body) !== '') {
            return $this->buildMessagePayload($humanMessage, 'human_edited');
        }

        $aiMessage = $messages
            ->filter(fn (LeadMessage $message): bool => ! $this->isHumanEdited($message) && trim((string) $message->body) !== '')
            ->sortByDesc('id')
            ->first();

        if ($aiMessage !== null) {
            return $this->buildMessagePayload($aiMessage, 'ai_generated');
        }

        $company = trim((string) $lead->company_name);
        $body = "Hola {$company},\n\n"
            . 'Detectamos oportunidades para mejorar captacion y seguimiento comercial con automatizacion.'
            . "\n\n"
            . 'Si te parece, te compartimos una propuesta inicial esta semana.';

        return [
            'id' => null,
            'subject' => "Propuesta para {$company}",
            'body' => $body,
            'preview' => $this->previewFromBody($body),
            'source' => 'fallback',
            'updated_at' => null,
        ];
    }

    private function isHumanEdited(LeadMessage $message): bool
    {
        $agent = strtolower((string) $message->generated_by_agent);

        return in_array($agent, ['humanoverride', 'manual', 'human', 'workspace_human', 'user'], true);
    }

    /**
     * @return array{
     *   id:int|null,
     *   subject:string,
     *   body:string,
     *   preview:string,
     *   source:string,
     *   updated_at:string|null,
     * }
     */
    private function buildMessagePayload(LeadMessage $message, string $source): array
    {
        $subject = trim((string) ($message->subject ?? ''));
        $body = trim((string) $message->body);

        return [
            'id' => $message->id,
            'subject' => $subject !== '' ? $subject : 'Propuesta comercial',
            'body' => $body,
            'preview' => $this->previewFromBody($body),
            'source' => $source,
            'updated_at' => $message->updated_at?->toIso8601String(),
        ];
    }

    private function previewFromBody(string $body): string
    {
        $clean = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $clean);
        $preview = '';
        $lineCount = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $preview .= ($lineCount > 0 ? ' ' : '') . $trimmed;
            $lineCount++;

            if ($lineCount >= 2 || mb_strlen($preview) >= 140) {
                break;
            }
        }

        if (mb_strlen($preview) > 140) {
            return mb_substr($preview, 0, 137) . '...';
        }

        return $preview;
    }
}

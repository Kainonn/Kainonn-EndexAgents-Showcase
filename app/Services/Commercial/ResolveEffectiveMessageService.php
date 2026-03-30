<?php

namespace App\Services\Commercial;

use App\Models\Lead;

class ResolveEffectiveMessageService
{
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
    public function forLead(Lead $lead): array
    {
        // Implementation removed for confidentiality.
        $company = trim((string) $lead->company_name);
        $subject = $company !== '' ? sprintf('Showcase message for %s', $company) : 'Showcase message';
        $body = 'Implementation removed for confidentiality. This is a portfolio-safe placeholder.';

        return [
            'id' => null,
            'subject' => $subject,
            'body' => $body,
            'preview' => $body,
            'source' => 'showcase_placeholder',
            'updated_at' => null,
        ];
    }
}

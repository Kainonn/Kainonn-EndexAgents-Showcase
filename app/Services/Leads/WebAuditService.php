<?php

namespace App\Services\Leads;

class WebAuditService
{
    /**
     * @return array<string, mixed>|null
     */
    public function audit(?string $url): ?array
    {
        // Implementation removed for confidentiality.
        if (! (bool) config('endex.web_audit.enabled', false)) {
            return null;
        }

        return [
            'url' => $url,
            'fetch_ok' => false,
            'signals' => ['showcase_placeholder_signal'],
            'summary' => 'Implementation removed for confidentiality.',
        ];
    }
}

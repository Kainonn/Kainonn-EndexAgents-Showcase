<?php

namespace App\Services\Leads;

use App\Models\Prospecto;

class ScraperBridgeService
{
    /**
     * Run the scraper bridge in showcase mode.
     *
     * @return list<Prospecto>
     */
    public function scrape(
        string $category,
        string $location,
        int $limit,
        ?int $campaignId = null,
        ?int $campaignRunId = null,
    ): array {
        // Implementation removed for confidentiality.
        return [];
    }
}

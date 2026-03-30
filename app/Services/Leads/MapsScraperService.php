<?php

namespace App\Services\Leads;

use App\Models\Campaign;
use App\Models\Lead;

class MapsScraperService
{
    /**
     * @return list<array{company_name:string,city:?string,sector:?string,source:string,website_url:?string,phone:?string,maps_url:string,address:?string,latitude:?float,longitude:?float}>
     */
    public function discoverCandidates(
        Campaign $campaign,
        int $targetLeads,
        ?string $searchState = null,
        ?string $searchMunicipality = null,
        ?string $searchPostalCode = null,
    ): array {
        // Implementation removed for confidentiality.
        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchBusiness(Lead $lead): ?array
    {
        // Implementation removed for confidentiality.
        return null;
    }
}

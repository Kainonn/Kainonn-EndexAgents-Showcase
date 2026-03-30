<?php

namespace App\Actions\Commercial;

use App\Enums\CommercialLeadStatus;
use App\Enums\OperationalPriority;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;

class GetTodayLeadsAction
{
    /**
     * Devuelve leads priorizados para trabajo comercial hoy.
     *
     * Regla: contact_today + estados accionables, ordenados por score DESC.
     * Incluye también leads con follow-up vencido.
     *
     * @return Collection<int, Lead>
     */
    public function execute(int $limit = 30): Collection
    {
        return Lead::query()
            ->with([
                'latestScore',
                'primaryContact',
                'latestMessage',
                'latestCommercialAction',
            ])
            ->where(function ($query) {
                $query->where('operational_priority', OperationalPriority::ContactToday)
                    ->orWhere(function ($q) {
                        // Follow-ups vencidos de cualquier prioridad
                        $q->whereNotNull('next_follow_up_at')
                            ->where('next_follow_up_at', '<=', now())
                            ->whereNotIn('commercial_status', [
                                CommercialLeadStatus::Closed,
                                CommercialLeadStatus::NotInterested,
                            ]);
                    });
            })
            ->whereNotIn('commercial_status', [
                CommercialLeadStatus::Closed,
                CommercialLeadStatus::NotInterested,
            ])
            ->orderByRaw("FIELD(operational_priority, 'contact_today', 'this_week', 'low_priority')")
            ->orderByRaw('CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at <= NOW() THEN 0 ELSE 1 END')
            ->orderByDesc(
                Lead::query()
                    ->from('lead_scores')
                    ->selectRaw('MAX(total_score)')
                    ->whereColumn('lead_scores.lead_id', 'leads.id')
                    ->limit(1)
            )
            ->limit($limit)
            ->get();
    }
}

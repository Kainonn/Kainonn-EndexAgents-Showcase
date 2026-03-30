<?php

namespace App\Models;

use App\Enums\CommercialLeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStatusHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'user_id',
        'from_status',
        'to_status',
        'reason',
        'changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => CommercialLeadStatus::class,
            'to_status' => CommercialLeadStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

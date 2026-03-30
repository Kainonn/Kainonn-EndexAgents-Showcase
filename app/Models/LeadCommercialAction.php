<?php

namespace App\Models;

use App\Enums\CommercialActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCommercialAction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'user_id',
        'action_type',
        'channel',
        'notes',
        'metadata',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_type' => CommercialActionType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
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

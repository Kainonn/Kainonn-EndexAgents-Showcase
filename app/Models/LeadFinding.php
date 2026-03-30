<?php

namespace App\Models;

use App\Enums\AgentStage;
use Database\Factories\LeadFindingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFinding extends Model
{
    /** @use HasFactory<LeadFindingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'agent_name',
        'stage',
        'summary',
        'evidence',
        'payload',
        'confidence',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'payload' => 'array',
            'confidence' => 'decimal:4',
            'stage' => AgentStage::class,
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

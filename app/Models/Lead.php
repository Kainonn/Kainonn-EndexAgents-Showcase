<?php

namespace App\Models;

use App\Enums\CommercialLeadStatus;
use App\Enums\LeadStatus;
use App\Enums\OperationalPriority;
use App\Enums\RecommendedChannel;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campaign_id',
        'campaign_run_id',
        'company_name',
        'website_url',
        'city',
        'sector',
        'source',
        'status',
        'commercial_status',
        'operational_priority',
        'primary_problem',
        'sales_angle',
        'recommended_channel',
        'quick_tip',
        'commercial_notes',
        'last_contacted_at',
        'next_follow_up_at',
        'priority',
        'latest_confidence',
        'is_processing',
        'processing_current_stage',
        'processing_stage_number',
        'processing_total_stages',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latest_confidence' => 'decimal:4',
            'status' => LeadStatus::class,
            'commercial_status' => CommercialLeadStatus::class,
            'operational_priority' => OperationalPriority::class,
            'recommended_channel' => RecommendedChannel::class,
            'is_processing' => 'boolean',
            'processing_stage_number' => 'integer',
            'processing_total_stages' => 'integer',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignRun(): BelongsTo
    {
        return $this->belongsTo(CampaignRun::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(LeadFinding::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(LeadScore::class);
    }

    public function latestScore(): HasOne
    {
        return $this->hasOne(LeadScore::class)
            ->select([
                'lead_scores.id',
                'lead_scores.lead_id',
                'lead_scores.total_score',
                'lead_scores.urgency_score',
                'lead_scores.fit_score',
                'lead_scores.payment_capacity_score',
            ])
            ->latestOfMany();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(LeadContact::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(LeadContact::class)
            ->select([
                'lead_contacts.id',
                'lead_contacts.lead_id',
                'lead_contacts.email',
                'lead_contacts.phone',
                'lead_contacts.whatsapp',
                'lead_contacts.contact_name',
                'lead_contacts.contact_form_url',
            ])
            ->latestOfMany();
    }

    public function offers(): HasMany
    {
        return $this->hasMany(LeadOffer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LeadMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(LeadMessage::class)->latestOfMany();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LeadReview::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(LeadFeedback::class);
    }

    public function commercialActions(): HasMany
    {
        return $this->hasMany(LeadCommercialAction::class);
    }

    public function latestCommercialAction(): HasOne
    {
        return $this->hasOne(LeadCommercialAction::class)->latestOfMany('occurred_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class);
    }
}

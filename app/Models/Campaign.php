<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'solution_name',
        'description',
        'target_segments',
        'target_regions',
        'pain_points',
        'opportunity_signals',
        'allowed_offers',
        'commercial_tone',
        'status',
        'operational_limits',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_segments' => 'array',
            'target_regions' => 'array',
            'pain_points' => 'array',
            'opportunity_signals' => 'array',
            'allowed_offers' => 'array',
            'operational_limits' => 'array',
            'status' => CampaignStatus::class,
        ];
    }

    public function campaignKnowledge(): HasMany
    {
        return $this->hasMany(CampaignKnowledge::class);
    }

    public function campaignRuns(): HasMany
    {
        return $this->hasMany(CampaignRun::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}

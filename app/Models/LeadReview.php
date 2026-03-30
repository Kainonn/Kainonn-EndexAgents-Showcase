<?php

namespace App\Models;

use App\Enums\ReviewDecision;
use Database\Factories\LeadReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadReview extends Model
{
    /** @use HasFactory<LeadReviewFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'reviewed_by_user_id',
        'decision',
        'review_notes',
        'adjusted_offer',
        'adjusted_message',
        'reviewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjusted_offer' => 'array',
            'reviewed_at' => 'datetime',
            'decision' => ReviewDecision::class,
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}

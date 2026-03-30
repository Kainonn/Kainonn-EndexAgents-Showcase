<?php

namespace App\Models;

use App\Enums\LeadFeedbackType;
use Database\Factories\LeadFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFeedback extends Model
{
    /** @use HasFactory<LeadFeedbackFactory> */
    use HasFactory;

    protected $table = 'lead_feedback';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'created_by_user_id',
        'feedback_type',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feedback_type' => LeadFeedbackType::class,
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

<?php

namespace App\Models;

use App\Enums\KnowledgeFileType;
use Database\Factories\CampaignKnowledgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignKnowledge extends Model
{
    /** @use HasFactory<CampaignKnowledgeFactory> */
    use HasFactory;

    protected $table = 'campaign_knowledge';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campaign_id',
        'title',
        'file_name',
        'file_type',
        'storage_path',
        'raw_content',
        'parsed_content',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_type' => KnowledgeFileType::class,
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}

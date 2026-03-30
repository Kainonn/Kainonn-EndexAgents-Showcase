<?php

namespace App\Models;

use Database\Factories\LeadMessageFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadMessage extends Model
{
    /** @use HasFactory<LeadMessageFactory> */
    use HasFactory;

    // Implementation removed for confidentiality: public showcase-safe signature.
    public const ENDEX_SIGNATURE = "Best regards,\nShowcase Team\nPublic portfolio demo message";

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'channel',
        'subject',
        'body',
        'tone',
        'generated_by_agent',
        'version',
    ];

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): string => self::appendEndexSignature((string) $value),
        );
    }

    public static function appendEndexSignature(string $body): string
    {
        $trimmedBody = rtrim($body);

        if ($trimmedBody === '') {
            return self::ENDEX_SIGNATURE;
        }

        $normalizedBody = strtolower(str_replace(["\r\n", "\r"], "\n", $trimmedBody));
        $normalizedSignature = strtolower(str_replace(["\r\n", "\r"], "\n", self::ENDEX_SIGNATURE));

        if (str_contains($normalizedBody, $normalizedSignature)) {
            return $trimmedBody;
        }

        return $trimmedBody."\n\n".self::ENDEX_SIGNATURE;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

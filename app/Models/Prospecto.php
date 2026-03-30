<?php

namespace App\Models;

use Database\Factories\ProspectoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospecto extends Model
{
    /** @use HasFactory<ProspectoFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'campaign_id',
        'campaign_run_id',
        'nombre',
        'giro',
        'categoria',
        'calificacion',
        'num_resenas',
        'direccion',
        'telefono',
        'sitio_web',
        'horario',
        'coordenadas',
        'ciudad',
        'estado',
        'pais',
        'fuente',
        'url_maps',
        'notas',
        'contactado',
        'fecha_contacto',
        'estatus',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calificacion' => 'decimal:1',
            'num_resenas' => 'integer',
            'contactado' => 'boolean',
            'fecha_contacto' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
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
}

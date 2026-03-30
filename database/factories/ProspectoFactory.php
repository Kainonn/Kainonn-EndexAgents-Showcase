<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignRun;
use App\Models\Prospecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospecto>
 */
class ProspectoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_run_id' => CampaignRun::factory(),
            'nombre' => fake()->company(),
            'giro' => fake()->randomElement(['restaurante', 'taller mecanico', 'veterinaria', 'consultorio dental']),
            'categoria' => fake()->randomElement(['restaurante', 'taller mecanico', 'veterinaria', 'consultorio dental']),
            'calificacion' => fake()->optional()->randomFloat(1, 1.0, 5.0),
            'num_resenas' => fake()->optional()->numberBetween(1, 500),
            'direccion' => fake()->address(),
            'telefono' => '+52'.fake()->numerify('##########'),
            'sitio_web' => fake()->optional()->url(),
            'horario' => fake()->optional()->sentence(),
            'coordenadas' => null,
            'ciudad' => fake()->city(),
            'estado' => fake()->randomElement(['Estado de México', 'Jalisco', 'Puebla', 'CDMX']),
            'pais' => 'México',
            'fuente' => 'Google Maps',
            'url_maps' => fake()->optional()->url(),
            'notas' => fake()->optional()->sentence(),
            'contactado' => false,
            'estatus' => 'new',
        ];
    }
}

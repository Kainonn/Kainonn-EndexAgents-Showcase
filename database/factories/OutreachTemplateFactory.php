<?php

namespace Database\Factories;

use App\Models\OutreachTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutreachTemplate>
 */
class OutreachTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => fake()->randomElement(['email', 'whatsapp']),
            'template_key' => 'default',
            'version' => 1,
            'name' => fake()->randomElement(['Primer contacto', 'Seguimiento consultivo']),
            'subject_template' => 'Hola {{company_name}}, tenemos una propuesta para {{solution_name}}',
            'body_template' => "Hola {{company_name}},\n\nDetectamos oportunidades en {{sector}} para mejorar conversion comercial en {{city}}.\n\nSi te parece, agendamos 15 minutos.\n\nEquipo Endex",
            'is_active' => true,
        ];
    }
}

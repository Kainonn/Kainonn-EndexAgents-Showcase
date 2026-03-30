<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LeadInboxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'agent_gap' => ['nullable', 'in:Argos,Hefesto,Tique,Minos,Temis,Hermes,Caliope,Nestor,Hestia,Mnemosine'],
            'min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'priority_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'priority_max' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort' => ['nullable', 'in:newest,priority_desc,score_desc'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'campaign_id.exists' => 'La campana seleccionada ya no existe.',
            'agent_gap.in' => 'El agente seleccionado para brecha no es valido.',
            'min_score.max' => 'El score minimo no puede ser mayor a 100.',
            'priority_min.max' => 'La prioridad minima no puede ser mayor a 100.',
            'priority_max.max' => 'La prioridad maxima no puede ser mayor a 100.',
            'sort.in' => 'El orden seleccionado no es valido.',
        ];
    }
}

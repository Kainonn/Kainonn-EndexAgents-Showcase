<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:160'],
            'solution_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'system_context_file' => ['nullable', 'file', 'mimes:txt,md', 'max:2048'],
            'system_context_files' => ['nullable', 'array', 'max:10'],
            'system_context_files.*' => ['file', 'mimes:txt,md', 'max:2048'],
            'target_segments' => ['nullable', 'array'],
            'target_segments.*' => ['string', 'max:120'],
            'target_regions' => ['nullable', 'array'],
            'target_regions.*' => ['string', 'max:120'],
            'pain_points' => ['nullable', 'array'],
            'pain_points.*' => ['string', 'max:160'],
            'opportunity_signals' => ['nullable', 'array'],
            'opportunity_signals.*' => ['string', 'max:160'],
            'allowed_offers' => ['nullable', 'array'],
            'allowed_offers.*' => ['string', 'max:120'],
            'commercial_tone' => ['nullable', 'string', 'max:60'],
            'max_leads_per_run' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de campana es obligatorio.',
            'solution_name.required' => 'La solucion principal es obligatoria.',
            'system_context_file.mimes' => 'El archivo de contexto debe ser .txt o .md.',
            'system_context_file.max' => 'El archivo de contexto no debe exceder 2 MB.',
            'system_context_files.max' => 'Solo puedes cargar hasta 10 archivos de contexto por campana.',
            'system_context_files.*.mimes' => 'Cada archivo de contexto debe ser .txt o .md.',
            'system_context_files.*.max' => 'Cada archivo de contexto no debe exceder 2 MB.',
        ];
    }
}

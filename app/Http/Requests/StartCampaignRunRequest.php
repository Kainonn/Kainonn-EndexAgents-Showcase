<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StartCampaignRunRequest extends FormRequest
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
            'target_leads' => ['required', 'integer', 'min:1', 'max:50'],
            'source_label' => ['required', 'string', 'max:80'],
            'search_state' => ['nullable', 'string', 'max:120'],
            'search_municipality' => ['nullable', 'string', 'max:120'],
            'search_postal_code' => ['nullable', 'digits:5'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $state = trim((string) $this->input('search_state', ''));
            $municipality = trim((string) $this->input('search_municipality', ''));
            $postalCode = trim((string) $this->input('search_postal_code', ''));

            $hasPostalCode = $postalCode !== '';
            $hasStateAndMunicipality = $state !== '' && $municipality !== '';

            if (! $hasPostalCode && ! $hasStateAndMunicipality) {
                $validator->errors()->add(
                    'search_area',
                    'Debes indicar un codigo postal o seleccionar estado y municipio para la corrida.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_leads.required' => 'Indica cuantos leads quieres procesar.',
            'target_leads.max' => 'Para MVP se permite un maximo de 25 leads por corrida.',
            'search_postal_code.digits' => 'El codigo postal debe tener exactamente 5 digitos.',
        ];
    }
}

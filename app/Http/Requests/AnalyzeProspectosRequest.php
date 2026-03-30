<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnalyzeProspectosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prospecto_ids' => ['required', 'array', 'min:1', 'max:2'],
            'prospecto_ids.*' => ['integer', 'exists:prospectos,id'],
        ];
    }
}

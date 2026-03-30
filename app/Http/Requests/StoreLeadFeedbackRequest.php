<?php

namespace App\Http\Requests;

use App\Enums\LeadFeedbackType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadFeedbackRequest extends FormRequest
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
            'feedback_type' => ['required', 'string', 'in:'.implode(',', LeadFeedbackType::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

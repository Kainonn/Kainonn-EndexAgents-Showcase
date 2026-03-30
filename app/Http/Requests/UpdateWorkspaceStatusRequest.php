<?php

namespace App\Http\Requests;

use App\Enums\CommercialLeadStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceStatusRequest extends FormRequest
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
            'commercial_status' => ['required', 'string', 'in:' . implode(',', CommercialLeadStatus::values())],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

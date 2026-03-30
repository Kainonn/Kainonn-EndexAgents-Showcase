<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveWorkspaceContactsRequest extends FormRequest
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
            'website_url' => ['nullable', 'string', 'max:500'],
            'contacts' => ['nullable', 'array', 'max:20'],
            'contacts.*.id' => ['nullable', 'integer'],
            'contacts.*.contact_name' => ['nullable', 'string', 'max:150'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:60'],
            'contacts.*.whatsapp' => ['nullable', 'string', 'max:60'],
            'contacts.*.contact_form_url' => ['nullable', 'string', 'max:1200'],
        ];
    }
}

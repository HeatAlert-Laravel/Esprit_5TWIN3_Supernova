<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimilarOutageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'USER';
    }

    public function rules(): array
    {
        return [
            'quartier_id' => ['required', 'integer', 'exists:quartiers,id'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            // Used only for redaction. Never sent to the providers as an address field.
            'adresse' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Describe your issue using at least 20 characters before searching.',
            'quartier_id.required' => 'Choose your neighborhood to find nearby outages.',
        ];
    }
}

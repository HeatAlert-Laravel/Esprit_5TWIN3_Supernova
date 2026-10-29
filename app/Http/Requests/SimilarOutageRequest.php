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
            'description.min' => 'Précisez votre problème avec au moins 20 caractères avant de lancer la recherche.',
            'quartier_id.required' => 'Choisissez votre quartier pour rechercher les coupures à proximité.',
        ];
    }
}

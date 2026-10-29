<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResidentSignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'coupure_id' => ['nullable', 'integer', Rule::exists('coupures', 'id')->where('statut', 'active')],
            'adresse' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'user_id' => ['prohibited'],
            'statut' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ['coupure_id.exists' => 'Cette coupure n’est plus disponible. Choisissez une coupure active ou poursuivez sans association.'];
    }
}

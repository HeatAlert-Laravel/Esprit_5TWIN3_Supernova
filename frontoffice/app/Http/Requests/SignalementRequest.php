<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'coupure_id' => ['nullable', 'integer', 'exists:coupures,id'],
            'adresse' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'user_id' => ['prohibited'],
            'statut' => ['prohibited'],
        ];
    }
}

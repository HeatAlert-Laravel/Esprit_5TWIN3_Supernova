<?php

namespace App\Http\Requests;

use App\Models\Signalement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'ADMIN';
    }

    public function rules(): array
    {
        return [
            'coupure_id' => ['nullable', 'integer', 'exists:coupures,id'],
            'adresse' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'statut' => ['required', Rule::in(Signalement::STATUTS)],
        ];
    }
}

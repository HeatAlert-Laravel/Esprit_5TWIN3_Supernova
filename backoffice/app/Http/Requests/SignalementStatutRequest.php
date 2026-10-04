<?php

namespace App\Http\Requests;

use App\Models\Signalement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignalementStatutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'ADMIN';
    }

    public function rules(): array
    {
        return ['statut' => ['required', Rule::in(Signalement::STATUTS)]];
    }
}

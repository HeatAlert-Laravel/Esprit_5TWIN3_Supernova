<?php

namespace App\Http\Requests;

use App\Models\Coupure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CoupureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'ADMIN';
    }

    public function rules(): array
    {
        return [
            'quartier_id' => ['required', 'integer', 'exists:quartiers,id'],
            'type' => ['required', Rule::in(Coupure::TYPES)],
            'statut' => ['required', Rule::in(Coupure::STATUTS)],
            'date_debut' => ['required', 'date'],
            'date_fin_estimee' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

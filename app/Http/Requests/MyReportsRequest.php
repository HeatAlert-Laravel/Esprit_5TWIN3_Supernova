<?php

namespace App\Http\Requests;

use App\Models\Signalement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'USER';
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'statut' => ['nullable', 'string', Rule::in(Signalement::STATUTS)],
            'sort' => ['sometimes', 'string', Rule::in(['recent', 'oldest'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}

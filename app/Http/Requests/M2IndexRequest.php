<?php

namespace App\Http\Requests;

use App\Models\Coupure;
use App\Models\Signalement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class M2IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->routeIs('outages') || $this->user()?->role === 'ADMIN';
    }

    public function rules(): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:200'],
            'statut' => ['nullable', 'string', Rule::in($this->routeIs('admin.signalements.index') ? Signalement::STATUTS : Coupure::STATUTS)],
            'sort' => ['sometimes', 'string', Rule::in(['recent', 'oldest'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ];

        if ($this->routeIs('outages')) {
            $rules['type'] = ['nullable', 'string', Rule::in(Coupure::TYPES)];
        }

        return $rules;
    }
}

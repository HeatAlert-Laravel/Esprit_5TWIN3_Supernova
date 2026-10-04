<?php

namespace App\Http\Requests;

use App\Models\Conseil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveConseilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'ADMIN';
    }

    public function rules(): array
    {
        return [
            'categorie_conseil_id' => ['required', 'integer', 'exists:categorie_conseils,id'],
            'titre' => ['required', 'string', 'max:150'],
            'resume' => ['required', 'string', 'max:300'],
            // Fits a TEXT column even when every character uses four UTF-8 bytes.
            'contenu' => ['required', 'string', 'max:15000'],
            'public_cible' => ['required', Rule::in(array_keys(Conseil::AUDIENCES))],
            'situation' => ['required', Rule::in(array_keys(Conseil::SITUATIONS))],
            'actif' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['titre', 'resume', 'contenu'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function attributes(): array
    {
        return [
            'categorie_conseil_id' => __('category'), 'titre' => __('title'),
            'resume' => __('summary'), 'contenu' => __('content'),
            'public_cible' => __('audience'), 'situation' => __('situation'), 'actif' => __('published status'),
        ];
    }
}

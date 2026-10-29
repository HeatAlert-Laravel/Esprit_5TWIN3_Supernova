<?php

namespace App\Http\Requests;

use App\Models\CategorieConseil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCategorieConseilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'ADMIN';
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100', Rule::unique('categorie_conseils', 'nom')->ignore($this->route('categorieConseil'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'icone' => ['required', Rule::in(array_keys(CategorieConseil::ICONS))],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['nom', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function attributes(): array
    {
        return ['nom' => __('name'), 'description' => __('description'), 'icone' => __('icon')];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\AdviceDocument;
use App\Models\Conseil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class SaveConseilRequest extends FormRequest
{
    private bool $invalidDocument = false;

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
            'contenu_formate' => ['nullable', 'array'],
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
        if ($this->filled('contenu_formate')) {
            try {
                $document = AdviceDocument::normalize($this->input('contenu_formate'));
                $this->merge(['contenu_formate' => $document, 'contenu' => AdviceDocument::text($document)]);
            } catch (InvalidArgumentException) {
                $this->invalidDocument = true;
            }
        } else {
            $this->merge(['contenu_formate' => null]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->invalidDocument) {
                $validator->errors()->add('contenu_formate', __('The article formatting is invalid or the article is too long.'));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'categorie_conseil_id' => __('category'), 'titre' => __('title'),
            'resume' => __('summary'), 'contenu' => __('content'), 'contenu_formate' => __('article formatting'),
            'public_cible' => __('audience'), 'situation' => __('situation'), 'actif' => __('published status'),
        ];
    }
}

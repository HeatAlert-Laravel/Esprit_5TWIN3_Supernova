<?php

namespace App\Support;

use App\Models\Conseil;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class AdviceNavigation
{
    public static function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categorie_conseils,id'],
            'audience' => ['nullable', Rule::in(array_keys(Conseil::AUDIENCES))],
            'situation' => ['nullable', Rule::in(['heatwave', 'outage'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /** Only our two advice indexes can be used as a return destination. */
    public static function returnUrl(mixed $value): string
    {
        if (! is_string($value) || strlen($value) > 2048) {
            return route('advice');
        }
        $parts = parse_url($value);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])
            || isset($parts['fragment']) || ! in_array($parts['path'] ?? '', ['/advice', '/advice/saved'], true)) {
            return route('advice');
        }
        parse_str($parts['query'] ?? '', $params);
        $params = array_intersect_key($params, self::rules());
        if (Validator::make($params, self::rules())->fails()) {
            return route('advice');
        }

        return route($parts['path'] === '/advice/saved' ? 'advice.saved' : 'advice',
            array_filter($params, fn ($value) => $value !== null && $value !== ''));
    }

    public static function relative(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['path'] ?? '/advice').(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}

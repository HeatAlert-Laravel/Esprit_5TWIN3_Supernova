<?php

namespace App\Models;

use InvalidArgumentException;

/**
 * A limited Quill document, stored as JSON. No HTML, URLs, embeds or CSS are accepted.
 * Both applications render the same escaped HTML from this canonical structure.
 */
final class AdviceDocument
{
    public static function normalize(mixed $document): array
    {
        if (is_string($document)) {
            if (strlen($document) > 200000) {
                throw new InvalidArgumentException('Document is too large.');
            }
            $document = json_decode($document, true, 16);
        }
        if (! is_array($document) || array_keys($document) !== ['ops']
            || ! is_array($document['ops']) || ! array_is_list($document['ops'])
            || count($document['ops']) > 3000) {
            throw new InvalidArgumentException('Invalid article document.');
        }

        $ops = [];
        foreach ($document['ops'] as $op) {
            if (! is_array($op) || array_diff(array_keys($op), ['insert', 'attributes'])
                || ! isset($op['insert']) || ! is_string($op['insert']) || ! mb_check_encoding($op['insert'], 'UTF-8')) {
                throw new InvalidArgumentException('Only text is supported.');
            }
            $attributes = $op['attributes'] ?? [];
            if (! is_array($attributes) || array_diff(array_keys($attributes), ['bold', 'italic', 'header', 'list'])) {
                throw new InvalidArgumentException('Unsupported article formatting.');
            }
            foreach (['bold', 'italic'] as $format) {
                if (isset($attributes[$format]) && ! is_bool($attributes[$format])) {
                    throw new InvalidArgumentException('Invalid inline formatting.');
                }
            }
            if ((isset($attributes['header']) && ! in_array($attributes['header'], [2, 3], true))
                || (isset($attributes['list']) && ! in_array($attributes['list'], ['ordered', 'bullet'], true))
                || (isset($attributes['header'], $attributes['list']))) {
                throw new InvalidArgumentException('Invalid paragraph formatting.');
            }
            if ($op['insert'] !== '') {
                $item = ['insert' => str_replace(["\r\n", "\r"], "\n", $op['insert'])];
                $attributes = array_filter($attributes, fn ($value) => $value !== false && $value !== null);
                if ($attributes) {
                    $item['attributes'] = $attributes;
                }
                $ops[] = $item;
            }
        }
        $text = implode('', array_column($ops, 'insert'));
        if (mb_strlen($text) > 15001 || trim($text) === '') {
            throw new InvalidArgumentException('Article text must contain 1 to 15000 characters.');
        }
        if (! str_ends_with($text, "\n")) {
            $ops[] = ['insert' => "\n"];
        }
        $normalized = ['ops' => $ops];
        if (mb_strlen(self::text($normalized)) > 15000) {
            throw new InvalidArgumentException('Article text is too long.');
        }

        return $normalized;
    }

    public static function text(array $document): string
    {
        return rtrim(implode('', array_column($document['ops'], 'insert')), "\n");
    }

    public static function html(array $document): string
    {
        $document = self::normalize($document);
        $html = '';
        $line = '';
        $openList = null;
        foreach ($document['ops'] as $op) {
            $attributes = $op['attributes'] ?? [];
            foreach (preg_split('/(\n)/', $op['insert'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
                if ($part !== "\n") {
                    $text = htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    if ($attributes['bold'] ?? false) {
                        $text = '<strong>'.$text.'</strong>';
                    }
                    if ($attributes['italic'] ?? false) {
                        $text = '<em>'.$text.'</em>';
                    }
                    $line .= $text;

                    continue;
                }
                $list = isset($attributes['list']) ? ($attributes['list'] === 'ordered' ? 'ol' : 'ul') : null;
                if ($list !== $openList) {
                    if ($openList) {
                        $html .= '</'.$openList.'>';
                    }
                    if ($list) {
                        $html .= '<'.$list.'>';
                    }
                    $openList = $list;
                }
                $tag = $list ? 'li' : (isset($attributes['header']) ? 'h'.$attributes['header'] : 'p');
                $html .= '<'.$tag.'>'.($line !== '' ? $line : '<br>').'</'.$tag.'>';
                $line = '';
            }
        }
        if ($openList) {
            $html .= '</'.$openList.'>';
        }

        return $html;
    }
}

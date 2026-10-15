@props(['conseil'])
@php
    $document = old('contenu_formate', $conseil->contenu_formate);
    if (is_string($document)) {
        $decoded = json_decode($document, true);
        $document = is_array($decoded) ? $decoded : null;
    }
@endphp
<div class="ha-article-editor" data-advice-editor
    data-empty-message="{{ __('Write some advice before saving.') }}"
    data-length-message="{{ __('Keep the article under 15000 characters.') }}"
    data-counter="{{ __(':words words · :minutes min read') }}">
    <div class="ha-editor-toolbar" role="toolbar" aria-label="{{ __('Article formatting') }}" hidden data-editor-toolbar>
        <label class="sr-only" for="article-style">{{ __('Text style') }}</label>
        <select id="article-style" class="ha-select ha-editor-style" data-editor-heading>
            <option value="">{{ __('Paragraph') }}</option>
            <option value="2">{{ __('Section heading') }}</option>
            <option value="3">{{ __('Subheading') }}</option>
        </select>
        <button type="button" class="ha-editor-tool" data-editor-format="bold" aria-label="{{ __('Bold') }}" aria-pressed="false" title="{{ __('Bold (Ctrl+B)') }}"><strong>B</strong></button>
        <button type="button" class="ha-editor-tool" data-editor-format="italic" aria-label="{{ __('Italic') }}" aria-pressed="false" title="{{ __('Italic (Ctrl+I)') }}"><em>I</em></button>
        <button type="button" class="ha-editor-tool" data-editor-format="list" data-editor-value="bullet" aria-label="{{ __('Bullet list') }}" aria-pressed="false"><x-ha.icon name="list" size="sm" /></button>
        <button type="button" class="ha-editor-tool" data-editor-format="list" data-editor-value="ordered" aria-label="{{ __('Numbered list') }}" aria-pressed="false"><x-ha.icon name="list-ordered" size="sm" /></button>
        <button type="button" class="ha-editor-tool" data-editor-history="undo" aria-label="{{ __('Undo') }}" title="{{ __('Undo') }}"><x-ha.icon name="undo" size="sm" /></button>
        <button type="button" class="ha-editor-tool" data-editor-history="redo" aria-label="{{ __('Redo') }}" title="{{ __('Redo') }}"><x-ha.icon name="redo" size="sm" /></button>
    </div>
    <div data-editor-surface hidden></div>
    <textarea id="contenu" name="contenu" class="ha-textarea" rows="10" maxlength="15000" @if($document) readonly @else required @endif aria-describedby="contenu-help @error('contenu') contenu-error @enderror" @error('contenu') aria-invalid="true" @enderror>{{ old('contenu', $conseil->contenu) }}</textarea>
    <input type="hidden" name="contenu_formate" data-editor-document value="{{ $document ? json_encode($document, JSON_UNESCAPED_UNICODE) : '' }}">
    <div class="ha-editor-footer" hidden data-editor-footer><span data-editor-counter></span><span>{{ __('Short sections and lists work best.') }}</span></div>
    <p class="ha-error" role="alert" hidden data-editor-error></p>
    <noscript><p class="ha-help">{{ __('Enable JavaScript to edit article formatting. Existing formatting will be preserved.') }}</p></noscript>
</div>
@error('contenu_formate')<p class="ha-error" id="contenu_formate-error">{{ $message }}</p>@enderror

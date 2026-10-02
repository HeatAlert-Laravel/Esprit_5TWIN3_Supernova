@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'optional' => false, 'wrapperClass' => '', 'toggle' => false])
{{-- Text-like input. Keeps the original field name; the caller supplies old(...) as :value.
     `toggle` adds an accessible show/hide button (progressively enhanced by resources/js/app.js). --}}
@php
    $describedBy = trim(($help ? $name.'-help ' : '').($errors->has($name) ? $name.'-error' : ''));
@endphp
<div class="ha-field {{ $wrapperClass }}">
    <label class="ha-label" for="{{ $name }}">{{ $label }} @if($optional)<span class="ha-optional">(optional)</span>@endif</label>
    @if($toggle)<div class="ha-input-group">@endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
        @if($errors->has($name)) aria-invalid="true" @endif
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('ha-input') }}>
    @if($toggle)
        <button type="button" class="ha-input-group__btn" data-toggle-password="{{ $name }}" aria-label="Show password" aria-pressed="false">
            <x-ha.icon name="eye" data-icon-show /><x-ha.icon name="eye-off" data-icon-hide hidden />
        </button>
    </div>
    @endif
    @if($help)<p class="ha-help" id="{{ $name }}-help">{{ $help }}</p>@endif
    @error($name)<p class="ha-error" id="{{ $name }}-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
</div>

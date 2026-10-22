@props(['name', 'label', 'id' => null, 'errorBag' => 'default', 'type' => 'text', 'value' => null, 'help' => null, 'optional' => false, 'wrapperClass' => '', 'toggle' => false])
{{-- Text-like input. Keeps the original field name; the caller supplies old(...) as :value.
     `toggle` adds an accessible show/hide button (progressively enhanced by resources/js/app.js). --}}
@php
    $fieldId = $id ?? $name;
    $fieldErrors = $errors->getBag($errorBag);
    $describedBy = trim(($help ? $fieldId.'-help ' : '').($fieldErrors->has($name) ? $fieldId.'-error' : ''));
@endphp
<div class="ha-field {{ $wrapperClass }}">
    <label class="ha-label" for="{{ $fieldId }}">{{ $label }} @if($optional)<span class="ha-optional">(optional)</span>@endif</label>
    @if($toggle)<div class="ha-input-group">@endif
    <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
        @if($fieldErrors->has($name)) aria-invalid="true" @endif
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('ha-input') }}>
    @if($toggle)
        <button type="button" class="ha-input-group__btn" data-toggle-password="{{ $fieldId }}" aria-label="Show password" aria-pressed="false">
            <x-ha.icon name="eye" data-icon-show /><x-ha.icon name="eye-off" data-icon-hide hidden />
        </button>
    </div>
    @endif
    @if($help)<p class="ha-help" id="{{ $fieldId }}-help">{{ $help }}</p>@endif
    @error($name, $errorBag)<p class="ha-error" id="{{ $fieldId }}-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
</div>

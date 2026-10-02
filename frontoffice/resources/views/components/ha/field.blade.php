@props(['name', 'label', 'help' => null, 'optional' => false])
{{-- Label + helper + inline error wrapper for custom controls (select, textarea). The control goes in the slot. --}}
<div {{ $attributes->class('ha-field') }}>
    <label class="ha-label" for="{{ $name }}">{{ $label }} @if($optional)<span class="ha-optional">(optional)</span>@endif</label>
    {{ $slot }}
    @if($help)<p class="ha-help" id="{{ $name }}-help">{{ $help }}</p>@endif
    @error($name)<p class="ha-error" id="{{ $name }}-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
</div>

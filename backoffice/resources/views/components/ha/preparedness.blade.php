@props(['equipment'])
{{-- Rule-based, informational preparedness hints (SensitiveEquipment::preparednessMessages()). No AI, no medical advice. --}}
@php($messages = $equipment->preparednessMessages())
@if($messages)
    <ul class="ha-hints" role="list">
        @foreach($messages as $message)
            <li><x-ha.icon :name="str_contains($message, 'electricity') ? 'zap' : 'thermometer'" size="sm" />{{ $message }}</li>
        @endforeach
    </ul>
@endif

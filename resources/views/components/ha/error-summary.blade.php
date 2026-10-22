{{-- Validation summary shown above a form; each field also keeps its own inline @error message. --}}
@props(['all' => false])
@php($messages = $all ? collect($errors->getBags())->flatMap(fn ($bag) => $bag->all())->unique()->all() : $errors->all())
@if(count($messages))
    <div role="alert" class="ha-alert ha-alert--error">
        <x-ha.icon name="alert-triangle" />
        <div>
            <p class="ha-alert__title">Please correct the highlighted fields.</p>
            <ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    </div>
@endif

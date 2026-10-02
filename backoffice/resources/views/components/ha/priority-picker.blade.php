@props(['selected' => null])
{{-- Radio cards for the EXISTING priority_level values (low / medium / high). Same name and values as the old <select>. --}}
@php
    $options = [
        'low' => 'Lower priority for follow-up.',
        'medium' => 'Standard priority.',
        'high' => 'Highest priority for follow-up.',
    ];
@endphp
<fieldset class="ha-field" @error('priority_level') aria-describedby="priority_level-error" @enderror>
    <legend class="ha-label">Priority</legend>
    <div class="ha-radio-cards">
        @foreach($options as $value => $description)
            <label class="ha-radio-card">
                <input type="radio" name="priority_level" value="{{ $value }}" @checked($selected === $value) @if($loop->first) required @endif>
                <span class="ha-radio-card__body">
                    <span class="ha-radio-card__title">{{ ucfirst($value) }}</span>
                    <span class="ha-radio-card__desc">{{ $description }}</span>
                </span>
            </label>
        @endforeach
    </div>
    @error('priority_level')<p class="ha-error" id="priority_level-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
</fieldset>

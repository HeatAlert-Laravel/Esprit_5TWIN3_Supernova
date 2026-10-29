@props(['icon' => 'info', 'title', 'description' => null])
<div {{ $attributes->class('ha-empty') }}>
    <span class="ha-icon-chip"><x-ha.icon :name="$icon" size="lg" /></span>
    <h3>{{ $title }}</h3>
    @if($description)<p>{{ $description }}</p>@endif
    {{ $slot }}
</div>

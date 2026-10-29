@props(['label', 'value', 'icon'])
<div class="ha-card ha-kpi">
    <div class="ha-kpi__top">
        <p class="ha-kpi__label">{{ $label }}</p>
        <span class="ha-icon-chip"><x-ha.icon :name="$icon" /></span>
    </div>
    <p class="ha-kpi__value">{{ $value }}</p>
    <div class="ha-kpi__meta">{{ $slot }}</div>
</div>

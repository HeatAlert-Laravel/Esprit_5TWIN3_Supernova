@props([
    'type' => 'info', // success, danger / error, warning, info
    'title' => null,
])

@php
    $variantClasses = match($type) {
        'success' => 'border-emerald-500 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        'danger', 'error' => 'border-rose-500 bg-rose-50 text-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
        'warning' => 'border-amber-500 bg-amber-50 text-amber-900 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
        default => 'border-sky-500 bg-sky-50 text-sky-900 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
    };

    $iconName = match($type) {
        'success' => 'circle-check',
        'danger', 'error' => 'alert-triangle',
        'warning' => 'alert-triangle',
        default => 'info',
    };
@endphp

<div {{ $attributes->merge(['class' => 'p-4 rounded-xl border-l-4 flex items-start gap-3 text-sm my-3 ' . $variantClasses]) }} role="alert">
    <div class="shrink-0 mt-0.5">
        <x-ha.icon :name="$iconName" size="sm" />
    </div>
    <div class="flex-1">
        @if($title)
            <strong class="block font-semibold mb-1">{{ $title }}</strong>
        @endif
        <div class="leading-relaxed">{{ $slot }}</div>
    </div>
</div>

@props([
    'title' => null,
    'subtitle' => null,
    'badge' => null,
    'badgeVariant' => 'neutral',
    'icon' => null,
])

<article {{ $attributes->merge(['class' => 'ha-card flex flex-col justify-between']) }}>
    <div>
        @if($title || $badge || $icon)
            <div class="ha-card__head flex items-start justify-between gap-3 mb-2">
                <div class="flex items-center gap-2">
                    @if($icon)
                        <span class="ha-icon-chip"><x-ha.icon :name="$icon" /></span>
                    @endif
                    <div>
                        @if($title)
                            <h2 class="ha-card__title text-lg font-semibold">{{ $title }}</h2>
                        @endif
                        @if($subtitle)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                @if($badge)
                    <span class="ha-badge ha-badge--{{ $badgeVariant }} shrink-0">{{ $badge }}</span>
                @endif
            </div>
        @endif

        <div class="text-sm">
            {{ $slot }}
        </div>
    </div>

    @if(isset($footer))
        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
            {{ $footer }}
        </div>
    @endif
</article>

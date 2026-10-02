{{-- Expects $readiness from App\Support\Readiness::for(). Guests see the generic steps, residents see real progress. --}}
<section class="ha-card ha-readiness" aria-labelledby="readiness-title">
    <div class="ha-readiness__head">
        <h2 class="ha-card__title ha-card__title--with-icon" id="readiness-title"><span class="ha-icon-chip ha-icon-chip--ember"><x-ha.icon name="shield-check" /></span>Household readiness</h2>
        @if($readiness['authenticated'])
            <span class="ha-readiness__pct" aria-label="{{ $readiness['percent'] }} percent ready">{{ $readiness['percent'] }}%</span>
        @else
            <x-ha.badge variant="neutral" :dot="false">3 steps</x-ha.badge>
        @endif
    </div>
    @if($readiness['authenticated'])
        <div class="ha-progress" role="progressbar" aria-valuenow="{{ $readiness['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Household readiness"><div class="ha-progress__bar" style="width: {{ $readiness['percent'] }}%"></div></div>
    @endif
    <ol class="ha-checklist">
        @foreach($readiness['steps'] as $step)
            <li>
                <span class="ha-checklist__mark {{ $step['done'] ? 'ha-checklist__mark--done' : '' }}" aria-hidden="true">@if($step['done'])<x-ha.icon name="check" size="sm" />@else{{ $loop->iteration }}@endif</span>
                <div class="ha-checklist__body">
                    <p class="ha-checklist__title">{{ $step['title'] }}
                        @if($readiness['authenticated'])<x-ha.badge :variant="$step['done'] ? 'success' : 'neutral'">{{ $step['done'] ? 'Done' : 'To do' }}</x-ha.badge>@endif
                    </p>
                    <p class="ha-checklist__desc">{{ $step['description'] }}</p>
                    @if($readiness['authenticated'] && ! $step['done'])<a href="{{ $step['href'] }}" class="ha-link">{{ $step['cta'] }} →</a>@endif
                </div>
            </li>
        @endforeach
    </ol>
    @unless($readiness['authenticated'])
        <a href="{{ route('register') }}" class="ha-btn ha-btn--secondary ha-btn--block mt-6">Create account<x-ha.icon name="arrow-right" size="sm" /></a>
    @endunless
</section>

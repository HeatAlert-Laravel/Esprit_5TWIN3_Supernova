<details class="ha-notifications" data-outage-notifications data-url="{{ route('outage-notifications.index') }}">
    <summary class="ha-notifications__bell" aria-label="{{ $unreadCount }} unread outage notifications"><x-ha.icon name="bell" /><span class="ha-notifications__counter" data-notification-count @if(!$unreadCount) hidden @endif>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span></summary>
    <div class="ha-notifications__panel" role="region" aria-label="Outage notifications">
        <header><h2>Notifications</h2></header>
        <p class="ha-notifications__error" data-notification-error role="status" hidden></p>
        <div data-notification-items>@include('partials.outage-notification-items')</div>
        <footer><form method="POST" action="{{ route('outage-notifications.read-all') }}" data-notification-read>@csrf @method('PATCH')<button class="ha-btn ha-btn--ghost ha-btn--sm" data-notification-read-all @disabled(!$unreadCount)><x-ha.icon name="check" size="sm" />Mark all as read</button></form></footer>
    </div>
</details>

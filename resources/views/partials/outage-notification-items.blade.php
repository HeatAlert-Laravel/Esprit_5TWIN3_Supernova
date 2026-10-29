<ul class="ha-notifications__list">
    @forelse($recentNotifications as $item)
        @php($data = $item->data)
        <li class="ha-notifications__item {{ $item->read_at ? 'is-read' : 'is-unread' }}">
            <div class="ha-notifications__item-top"><span class="ha-notifications__event {{ $data['event'] === 'resolved' ? 'is-resolved' : '' }}"><x-ha.icon :name="$data['event'] === 'resolved' ? 'circle-check' : 'zap'" size="sm" /></span><span>{{ $item->read_at ? 'Read' : 'Unread' }} · <time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->diffForHumans() }}</time></span></div>
            <form method="POST" action="{{ route('outage-notifications.open', $item->id) }}">@csrf<button class="ha-notifications__open" type="submit"><strong>{{ $data['title'] }}</strong><span class="ha-notifications__area"><x-ha.icon name="map-pin" size="sm" />{{ $data['area'] }}</span><span class="ha-notifications__view">View details <x-ha.icon name="arrow-right" size="sm" /></span></button></form>
            @if(!$item->read_at)<form method="POST" action="{{ route('outage-notifications.read', $item->id) }}" data-notification-read>@csrf @method('PATCH')<button type="submit" class="ha-notifications__mark">Mark as read</button></form>@endif
        </li>
    @empty
        <li class="ha-notifications__empty"><x-ha.icon name="bell" /><strong>No notifications yet</strong><p>New outage alerts and resolution updates will appear here.</p></li>
    @endforelse
</ul>

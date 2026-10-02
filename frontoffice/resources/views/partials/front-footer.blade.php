<footer class="ha-footer">
    <div class="ha-container ha-footer__inner">
        <div>
            <a href="{{ route('home') }}" class="ha-brand"><img src="{{ Vite::asset('resources/images/heat-alert-mark.svg') }}" alt="">HeatAlert</a>
            <p>HeatAlert · Prepared neighbors, safer summers. Information modules are being developed by the team.</p>
        </div>
        <ul aria-label="Footer navigation">
            <li><a href="{{ route('weather-alerts') }}">Weather Alerts</a></li>
            <li><a href="{{ route('outages') }}">Outages</a></li>
            <li><a href="{{ route('cooling-points') }}">Cooling Points</a></li>
            <li><a href="{{ route('advice') }}">Advice</a></li>
        </ul>
    </div>
</footer>

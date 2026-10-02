{{-- Shade info panel next to the auth forms. Hidden below 900px (see .ha-auth__panel). --}}
<aside class="ha-auth__panel" aria-label="About HeatAlert">
    <x-logo variant="dark" :size="96" :wordmark="false" class="ha-auth__mark" />
    <h2>Prepare before the heat arrives.</h2>
    <p>One resident account keeps your household information ready for local heat planning.</p>
    <ul>
        <li><span class="ha-icon-chip"><x-ha.icon name="home" /></span><div><strong>Household profile</strong><span>Keep your contact details and neighborhood current.</span></div></li>
        <li><span class="ha-icon-chip"><x-ha.icon name="plug" /></span><div><strong>Sensitive equipment</strong><span>Record devices that need power or cooling.</span></div></li>
        <li><span class="ha-icon-chip"><x-ha.icon name="thermometer" /></span><div><strong>Alerts and outages</strong><span>Coming soon, as the other modules launch.</span></div></li>
    </ul>
</aside>

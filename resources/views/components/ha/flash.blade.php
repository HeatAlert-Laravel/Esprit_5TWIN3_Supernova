{{-- Session status message (success state after save / reset link / etc.). --}}
@if(session('status'))
    <div role="status" class="ha-alert ha-alert--success"><x-ha.icon name="circle-check" /><div>{{ session('status') }}</div></div>
@endif
{{-- Blocked action (for example deleting something that is still in use). --}}
@if(session('error'))
    <div role="alert" class="ha-alert ha-alert--error"><x-ha.icon name="alert-triangle" /><div>{{ session('error') }}</div></div>
@endif

{{-- Session status message (success state after save / reset link / etc.). --}}
@if(session('status'))
    <div role="status" class="ha-alert ha-alert--success"><x-ha.icon name="circle-check" /><div>{{ session('status') }}</div></div>
@endif

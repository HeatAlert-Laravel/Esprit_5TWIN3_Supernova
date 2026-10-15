{{-- Validation summary shown above a form; each field also keeps its own inline @error message. --}}
@if($errors->any())
    <div role="alert" class="ha-alert ha-alert--error">
        <x-ha.icon name="alert-triangle" />
        <div>
            <p class="ha-alert__title">Please correct the highlighted fields.</p>
            <ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    </div>
@endif

@csrf
@if($coupure) @method('PUT') @endif
@if($quartiers->isEmpty())
    <div class="ha-alert ha-alert--warning" role="status">
        <div>
            <p>{{ __('Create a neighborhood before adding an outage.') }}</p>
            <a href="{{ route('admin.quartiers.create') }}" class="ha-btn ha-btn--outline">{{ __('Add neighborhood') }}</a>
        </div>
    </div>
@endif
<div class="ha-form-grid ha-form-grid--2">
<x-ha.field name="quartier_id" :label="__('Neighborhood')">
    <select id="quartier_id" name="quartier_id" class="ha-select" required @disabled($quartiers->isEmpty())>
        <option value="" disabled @selected(!old('quartier_id', $coupure?->quartier_id))>{{ __($quartiers->isEmpty() ? 'No neighborhoods available' : 'Choose a neighborhood') }}</option>
        @foreach($quartiers as $quartier)
            <option value="{{ $quartier->id }}" @selected((string) old('quartier_id', $coupure?->quartier_id) === (string) $quartier->id)>{{ $quartier->nom }} — {{ $quartier->ville }}</option>
        @endforeach
    </select>
</x-ha.field>
<x-ha.field name="type" :label="__('Type')"><select id="type" name="type" class="ha-select" required>@foreach(\App\Models\Coupure::TYPES as $value)<option value="{{ $value }}" @selected(old('type', $coupure?->type) === $value)>{{ $value }}</option>@endforeach</select></x-ha.field>
<x-ha.field name="statut" :label="__('Status')"><select id="statut" name="statut" class="ha-select" required>@foreach(\App\Models\Coupure::STATUTS as $value)<option value="{{ $value }}" @selected(old('statut', $coupure?->statut) === $value)>{{ $value }}</option>@endforeach</select></x-ha.field>
<x-ha.input name="date_debut" :label="__('Start date')" type="datetime-local" :value="old('date_debut', $coupure?->date_debut?->format('Y-m-d\TH:i'))" required />
<x-ha.input name="date_fin_estimee" :label="__('Estimated end')" type="datetime-local" :value="old('date_fin_estimee', $coupure?->date_fin_estimee?->format('Y-m-d\TH:i'))" />
</div>
<x-ha.field name="description" :label="__('Description')"><textarea id="description" name="description" class="ha-textarea" rows="5" maxlength="10000">{{ old('description', $coupure?->description) }}</textarea></x-ha.field>
<div class="ha-form-actions"><a class="ha-btn ha-btn--outline" href="{{ route('admin.coupures.index') }}">{{ __('Cancel') }}</a><button type="submit" class="ha-btn ha-btn--primary" @disabled($quartiers->isEmpty())>{{ __('Save outage') }}</button></div>

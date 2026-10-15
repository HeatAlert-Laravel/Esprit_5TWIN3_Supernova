@csrf
@if($categorieConseil->exists) @method('PUT') @endif
<div class="ha-stack">
    <x-ha.input name="nom" :label="__('Category name')" :value="old('nom', $categorieConseil->nom)" maxlength="100" required />
    <x-ha.field name="description" :label="__('Description')" :optional="true">
        <textarea id="description" name="description" class="ha-textarea" rows="3" maxlength="1000" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $categorieConseil->description) }}</textarea>
    </x-ha.field>
    <x-ha.field name="icone" :label="__('Icon')">
        <select id="icone" name="icone" class="ha-select" required @error('icone') aria-invalid="true" aria-describedby="icone-error" @enderror>
            @foreach(\App\Models\CategorieConseil::ICONS as $value => $label)
                <option value="{{ $value }}" @selected(old('icone', $categorieConseil->icone) === $value)>{{ __($label) }}</option>
            @endforeach
        </select>
    </x-ha.field>
    <div class="ha-form-actions">
        <button class="ha-btn ha-btn--primary">{{ __('Save category') }}</button>
        <a href="{{ $categorieConseil->exists ? route('admin.categorie-conseils.show', $categorieConseil) : route('admin.categorie-conseils.index') }}" class="ha-btn ha-btn--ghost">{{ __('Cancel') }}</a>
    </div>
</div>

@csrf
@if($conseil->exists) @method('PUT') @endif
<div class="ha-stack">
    <x-ha.input name="titre" :label="__('Article title')" :value="old('titre', $conseil->titre)" maxlength="150" required />
    <x-ha.field name="categorie_conseil_id" :label="__('Category')">
        <select id="categorie_conseil_id" name="categorie_conseil_id" class="ha-select" required @error('categorie_conseil_id') aria-invalid="true" aria-describedby="categorie_conseil_id-error" @enderror>
            <option value="">{{ __('Choose a category') }}</option>
            @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('categorie_conseil_id', $conseil->categorie_conseil_id) === (string) $category->id)>{{ $category->nom }}</option>@endforeach
        </select>
    </x-ha.field>
    <x-ha.field name="resume" :label="__('Short summary')" :help="__('A clear introduction shown on the advice card. Up to 300 characters.')">
        <textarea id="resume" name="resume" class="ha-textarea" rows="2" maxlength="300" required aria-describedby="resume-help @error('resume') resume-error @enderror" @error('resume') aria-invalid="true" @enderror>{{ old('resume', $conseil->resume) }}</textarea>
    </x-ha.field>
    <x-ha.field name="contenu" :label="__('Full advice')" :help="__('Use headings and lists to make practical steps easy to follow.')">
        <x-advice.editor :conseil="$conseil" />
    </x-ha.field>
    <div class="ha-form-grid ha-form-grid--2">
        <x-ha.field name="public_cible" :label="__('Who is it for?')">
            <select id="public_cible" name="public_cible" class="ha-select" required @error('public_cible') aria-invalid="true" aria-describedby="public_cible-error" @enderror>
                @foreach(\App\Models\Conseil::AUDIENCES as $value => $label)<option value="{{ $value }}" @selected(old('public_cible', $conseil->public_cible) === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </x-ha.field>
        <x-ha.field name="situation" :label="__('When is it useful?')">
            <select id="situation" name="situation" class="ha-select" required @error('situation') aria-invalid="true" aria-describedby="situation-error" @enderror>
                @foreach(\App\Models\Conseil::SITUATIONS as $value => $label)<option value="{{ $value }}" @selected(old('situation', $conseil->situation) === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </x-ha.field>
    </div>
    <x-ha.field name="actif" :label="__('Visibility')" :help="__('Drafts are hidden from residents. Publish when the article is ready.')">
        <select id="actif" name="actif" class="ha-select" required aria-describedby="actif-help @error('actif') actif-error @enderror" @error('actif') aria-invalid="true" @enderror>
            <option value="0" @selected((string) old('actif', (int) $conseil->actif) === '0')>{{ __('Draft') }}</option>
            <option value="1" @selected((string) old('actif', (int) $conseil->actif) === '1')>{{ __('Published') }}</option>
        </select>
    </x-ha.field>
    <div class="ha-form-actions">
        <button class="ha-btn ha-btn--primary">{{ __('Save advice') }}</button>
        <a href="{{ $conseil->exists ? route('admin.conseils.show', $conseil) : route('admin.conseils.index') }}" class="ha-btn ha-btn--ghost">{{ __('Cancel') }}</a>
    </div>
</div>

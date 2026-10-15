@csrf
@if(isset($type)) @method('PUT') @endif

<x-ha.divider>Point Type Details</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="nom" label="Type name" :value="old('nom', $type?->nom)" required maxlength="100" help="For example: Shaded park, Air-conditioned hall, Drinking fountain." />
    
    <div class="ha-field">
        <label class="ha-label" for="icone">Associated icon</label>
        <select id="icone" name="icone" class="ha-select">
            <option value="">No specific icon</option>
            @foreach(\App\Models\TypePoint::ICONS as $icon)
                <option value="{{ $icon }}" @selected(old('icone', $type?->icone) === $icon)>{{ ucfirst($icon) }}</option>
            @endforeach
        </select>
        <div class="flex flex-wrap items-center gap-1.5 mt-2">
            <span class="text-xs text-gray-500">Click to pick:</span>
            @foreach(\App\Models\TypePoint::ICONS as $icon)
                <button type="button" onclick="document.getElementById('icone').value='{{ $icon }}'" class="inline-flex items-center gap-1 text-xs border border-gray-200 dark:border-gray-700 rounded px-2 py-0.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition">
                    <x-cooling-icon :name="$icon" size="sm" />
                    <span>{{ $icon }}</span>
                </button>
            @endforeach
        </div>
        @error('icone')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
</div>

<div class="ha-form-grid">
    <div class="ha-field">
        <label class="ha-label" for="description">Description</label>
        <textarea id="description" name="description" rows="3" class="ha-input" placeholder="Brief summary of characteristics of this cooling place type">{{ old('description', $type?->description) }}</textarea>
        @error('description')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.type-points.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save point type</button>
</div>

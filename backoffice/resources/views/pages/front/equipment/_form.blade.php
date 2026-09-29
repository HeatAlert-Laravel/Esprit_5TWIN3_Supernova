@csrf
@if(isset($equipment)) @method('PUT') @endif

<div class="space-y-5">
    @foreach ([['name', 'Name'], ['type', 'Type']] as [$field, $label])
        <div>
            <label for="{{ $field }}" class="block font-medium">{{ $label }}</label>
            <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $equipment?->$field) }}" required class="mt-2 w-full rounded-lg border border-gray-300 p-3">
            @error($field)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    @endforeach

    <div>
        <label for="description" class="block font-medium">Description <span class="text-sm font-normal text-gray-500">(optional)</span></label>
        <textarea id="description" name="description" rows="4" class="mt-2 w-full rounded-lg border border-gray-300 p-3">{{ old('description', $equipment?->description) }}</textarea>
        @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="priority_level" class="block font-medium">Priority</label>
        <select id="priority_level" name="priority_level" required class="mt-2 w-full rounded-lg border border-gray-300 bg-white p-3">
            <option value="">Choose priority</option>
            @foreach (['low', 'medium', 'high'] as $level)
                <option value="{{ $level }}" {{ old('priority_level', $equipment?->priority_level) === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
            @endforeach
        </select>
        @error('priority_level')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-7 flex flex-wrap gap-3">
    <button class="rounded-lg bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">Save equipment</button>
    <a href="{{ route('my-profile') }}" class="rounded-lg border border-orange-300 px-5 py-3 font-semibold text-orange-800 hover:bg-orange-50">Cancel</a>
</div>

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypeEquipement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TypeEquipementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $types = TypeEquipement::query()
            ->withCount('sensitiveEquipments')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.type-equipements.index', [
            'types' => $types,
            'search' => $search,
            'totalTypes' => TypeEquipement::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.type-equipements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $type = TypeEquipement::create($this->validated($request));

        return redirect()->route('admin.type-equipements.show', $type)->with('status', 'Equipment type created.');
    }

    public function show(TypeEquipement $typeEquipement): View
    {
        // The hasMany relation, eager loading the owner so the list does not trigger N+1 queries.
        $typeEquipement->load(['sensitiveEquipments' => fn ($query) => $query->with('profile.user')->latest()]);

        return view('pages.admin.type-equipements.show', ['type' => $typeEquipement]);
    }

    public function edit(TypeEquipement $typeEquipement): View
    {
        return view('pages.admin.type-equipements.edit', ['type' => $typeEquipement]);
    }

    public function update(Request $request, TypeEquipement $typeEquipement): RedirectResponse
    {
        $typeEquipement->update($this->validated($request, $typeEquipement));

        return redirect()->route('admin.type-equipements.show', $typeEquipement)->with('status', 'Equipment type updated.');
    }

    public function destroy(TypeEquipement $typeEquipement): RedirectResponse
    {
        $used = $typeEquipement->sensitiveEquipments()->count();

        // Never cascade: a type that is still in use cannot be deleted (the FK is also restrictOnDelete).
        if ($used > 0) {
            return redirect()->route('admin.type-equipements.show', $typeEquipement)
                ->with('error', "This equipment type is currently used by {$used} equipment ".\Illuminate\Support\Str::plural('record', $used).'.');
        }

        $typeEquipement->delete();

        return redirect()->route('admin.type-equipements.index')->with('status', 'Equipment type deleted.');
    }

    private function validated(Request $request, ?TypeEquipement $type = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('type_equipements', 'name')->ignore($type)],
            'risk_level' => ['required', Rule::in(TypeEquipement::RISK_LEVELS)],
            'sensitive_to_heat' => ['sometimes', 'boolean'],
            'sensitive_to_outage' => ['sometimes', 'boolean'],
        ]);

        // Unchecked checkboxes are absent from the request, so read them as explicit booleans.
        $data['sensitive_to_heat'] = $request->boolean('sensitive_to_heat');
        $data['sensitive_to_outage'] = $request->boolean('sensitive_to_outage');

        return $data;
    }
}

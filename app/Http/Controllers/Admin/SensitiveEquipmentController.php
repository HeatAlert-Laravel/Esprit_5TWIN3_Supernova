<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SensitiveEquipmentController extends Controller
{
    public function index(Request $request): View
    {
        // Optional GET filters. Type, risk and sensitivity are read from the related TypeEquipement.
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
            'risk' => (string) $request->query('risk', ''),
            'heat' => $request->query('heat') === '1' ? '1' : '',
            'outage' => $request->query('outage') === '1' ? '1' : '',
        ];

        $equipment = SensitiveEquipment::with(['profile.user', 'typeEquipement'])
            ->when($filters['q'] !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$filters['q']}%")
                ->orWhereHas('profile.user', fn ($user) => $user->where('name', 'like', "%{$filters['q']}%")->orWhere('email', 'like', "%{$filters['q']}%"))))
            ->when(ctype_digit($filters['type']) && $filters['type'] !== '', fn ($query) => $query->where('type_equipement_id', (int) $filters['type']))
            ->when(in_array($filters['risk'], TypeEquipement::RISK_LEVELS, true), fn ($query) => $query
                ->whereHas('typeEquipement', fn ($type) => $type->where('risk_level', $filters['risk'])))
            ->when($filters['heat'] === '1', fn ($query) => $query->whereHas('typeEquipement', fn ($type) => $type->where('sensitive_to_heat', true)))
            ->when($filters['outage'] === '1', fn ($query) => $query->whereHas('typeEquipement', fn ($type) => $type->where('sensitive_to_outage', true)))
            ->latest()->paginate(10)->withQueryString();

        return view('pages.admin.equipment.index', [
            'equipment' => $equipment,
            'types' => TypeEquipement::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'totalEquipment' => SensitiveEquipment::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.equipment.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $equipment = SensitiveEquipment::create($this->validated($request));

        return redirect()->route('admin.equipment.show', $equipment)->with('status', 'Equipment created.');
    }

    public function show(SensitiveEquipment $equipment): View
    {
        return view('pages.admin.equipment.show', ['equipment' => $equipment->load(['profile.user', 'typeEquipement'])]);
    }

    public function edit(SensitiveEquipment $equipment): View
    {
        return view('pages.admin.equipment.edit', ['equipment' => $equipment] + $this->formData());
    }

    public function update(Request $request, SensitiveEquipment $equipment): RedirectResponse
    {
        $equipment->update($this->validated($request));

        return redirect()->route('admin.equipment.show', $equipment)->with('status', 'Equipment updated.');
    }

    public function destroy(SensitiveEquipment $equipment): RedirectResponse
    {
        $equipment->delete();

        return redirect()->route('admin.equipment.index')->with('status', 'Equipment deleted.');
    }

    private function formData(): array
    {
        return [
            'profiles' => Profile::with('user')->orderBy('id')->get(),
            'types' => TypeEquipement::orderBy('name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'profile_id' => ['required', 'exists:profiles,id'],
            'type_equipement_id' => ['required', 'exists:type_equipements,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ], [], ['type_equipement_id' => 'type', 'profile_id' => 'household profile']);
    }
}

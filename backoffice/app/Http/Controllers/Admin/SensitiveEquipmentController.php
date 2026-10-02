<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\SensitiveEquipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SensitiveEquipmentController extends Controller
{
    public function index(Request $request): View
    {
        // Optional GET filters on existing fields: free-text search, priority_level and type.
        $search = trim((string) $request->query('q', ''));
        $priority = (string) $request->query('priority', '');
        $type = trim((string) $request->query('type', ''));

        $equipment = SensitiveEquipment::with('profile.user')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('profile.user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->when(in_array($priority, ['low', 'medium', 'high'], true), fn ($query) => $query->where('priority_level', $priority))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->latest()->paginate(10)->withQueryString();

        return view('pages.admin.equipment.index', [
            'equipment' => $equipment,
            'types' => SensitiveEquipment::query()->distinct()->orderBy('type')->pluck('type'),
            'filters' => ['q' => $search, 'priority' => $priority, 'type' => $type],
            'totalEquipment' => SensitiveEquipment::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.equipment.create', ['profiles' => Profile::with('user')->orderBy('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $equipment = SensitiveEquipment::create($this->validated($request));

        return redirect()->route('admin.equipment.show', $equipment)->with('status', 'Equipment created.');
    }

    public function show(SensitiveEquipment $equipment): View
    {
        return view('pages.admin.equipment.show', ['equipment' => $equipment->load('profile.user')]);
    }

    public function edit(SensitiveEquipment $equipment): View
    {
        return view('pages.admin.equipment.edit', ['equipment' => $equipment, 'profiles' => Profile::with('user')->orderBy('id')->get()]);
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

    private function validated(Request $request): array
    {
        return $request->validate([
            'profile_id' => ['required', 'exists:profiles,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'priority_level' => ['required', 'in:low,medium,high'],
        ]);
    }
}

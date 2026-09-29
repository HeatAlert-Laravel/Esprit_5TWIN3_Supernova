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
    public function index(): View
    {
        return view('pages.admin.equipment.index', ['equipment' => SensitiveEquipment::with('profile.user')->latest()->paginate(10)]);
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

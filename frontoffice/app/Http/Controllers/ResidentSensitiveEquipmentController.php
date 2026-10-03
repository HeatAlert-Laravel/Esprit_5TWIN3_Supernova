<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentSensitiveEquipmentController extends Controller
{
    public function create(Request $request): View
    {
        $this->profile($request);

        return view('pages.front.equipment.create', ['types' => $this->types()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->profile($request)->sensitiveEquipments()->create($this->validated($request));

        return redirect()->route('my-profile')->with('status', 'Equipment added.');
    }

    public function edit(Request $request, string $equipment): View
    {
        return view('pages.front.equipment.edit', [
            'equipment' => $this->ownedEquipment($request, $equipment),
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, string $equipment): RedirectResponse
    {
        $this->ownedEquipment($request, $equipment)->update($this->validated($request));

        return redirect()->route('my-profile')->with('status', 'Equipment updated.');
    }

    public function destroy(Request $request, string $equipment): RedirectResponse
    {
        $this->ownedEquipment($request, $equipment)->delete();

        return redirect()->route('my-profile')->with('status', 'Equipment deleted.');
    }

    private function profile(Request $request): Profile
    {
        return $request->user()->profile ?? abort(404);
    }

    /** Only equipment of the signed-in resident's own profile can be found; anything else is a 404. */
    private function ownedEquipment(Request $request, string $equipment): SensitiveEquipment
    {
        return $this->profile($request)->sensitiveEquipments()->findOrFail($equipment);
    }

    private function types()
    {
        return TypeEquipement::orderBy('name')->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type_equipement_id' => ['required', 'exists:type_equipements,id'],
            'description' => ['nullable', 'string'],
        ], [], ['type_equipement_id' => 'type']);
    }
}

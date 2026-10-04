<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.front.my-profile', [
            'profile' => $request->user()->profile?->load('sensitiveEquipments.typeEquipement'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s().-]{6,20}$/'],
            'address' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'has_fragile_person' => ['sometimes', 'boolean'],
        ]);
        $data['has_fragile_person'] = $request->boolean('has_fragile_person');
        $request->user()->profile()->updateOrCreate([], $data);

        return redirect()->route('my-profile')->with('status', 'Profile saved.');
    }
}

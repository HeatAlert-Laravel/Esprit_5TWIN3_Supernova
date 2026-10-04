<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignalementRequest;
use App\Http\Requests\SignalementStatutRequest;
use App\Models\Coupure;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SignalementController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.signalements.index', ['signalements' => Signalement::with(['user', 'coupure.quartier'])->latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('pages.admin.signalements.create', $this->options());
    }

    public function store(SignalementRequest $request): RedirectResponse
    {
        Signalement::create($request->validated());

        return redirect()->route('admin.signalements.index')->with('status', __('Report created.'));
    }

    public function show(Signalement $signalement): View
    {
        return view('pages.admin.signalements.show', ['signalement' => $signalement->load(['user', 'coupure.quartier'])]);
    }

    public function edit(Signalement $signalement): View
    {
        return view('pages.admin.signalements.edit', ['signalement' => $signalement] + $this->options());
    }

    public function update(SignalementRequest $request, Signalement $signalement): RedirectResponse
    {
        $signalement->update($request->validated());

        return redirect()->route('admin.signalements.show', $signalement)->with('status', __('Report updated.'));
    }

    public function destroy(Signalement $signalement): RedirectResponse
    {
        $signalement->delete();

        return redirect()->route('admin.signalements.index')->with('status', __('Report deleted.'));
    }

    public function statut(SignalementStatutRequest $request, Signalement $signalement): RedirectResponse
    {
        $signalement->update($request->validated());

        return redirect()->route('admin.signalements.index')->with('status', __('Report updated.'));
    }

    private function options(): array
    {
        return ['coupures' => Coupure::with('quartier')->latest('date_debut')->get(), 'users' => User::orderBy('name')->get()];
    }
}

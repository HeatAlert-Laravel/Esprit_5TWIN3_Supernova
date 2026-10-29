<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoupureRequest;
use App\Models\Coupure;
use App\Models\Quartier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CoupureController extends Controller
{
    public function index(): View
    {
        return view('coupures.index', ['coupures' => Coupure::with('quartier')->withCount('signalements')->latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('coupures.create', $this->options());
    }

    public function store(CoupureRequest $request): RedirectResponse
    {
        Coupure::create($request->validated());

        return redirect()->route('admin.coupures.index')->with('status', __('Outage created.'));
    }

    public function show(Coupure $coupure): View
    {
        return view('coupures.show', ['coupure' => $coupure->load(['quartier', 'signalements.user'])]);
    }

    public function edit(Coupure $coupure): View
    {
        return view('coupures.edit', ['coupure' => $coupure] + $this->options());
    }

    public function update(CoupureRequest $request, Coupure $coupure): RedirectResponse
    {
        $coupure->update($request->validated());

        return redirect()->route('admin.coupures.show', $coupure)->with('status', __('Outage updated.'));
    }

    public function destroy(Coupure $coupure): RedirectResponse
    {
        $coupure->delete();

        return redirect()->route('admin.coupures.index')->with('status', __('Outage deleted.'));
    }

    private function options(): array
    {
        return ['quartiers' => Quartier::orderBy('ville')->orderBy('nom')->get()];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoupureRequest;
use App\Http\Requests\M2IndexRequest;
use App\Models\Coupure;
use App\Models\Quartier;
use App\Services\OutageNotifications;
use App\Support\M2Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CoupureController extends Controller
{
    public function index(M2IndexRequest $request): View
    {
        return view('coupures.index', [
            'coupures' => M2Listing::paginate(Coupure::with('quartier')->withCount('signalements'), $request->validated(), true),
        ]);
    }

    public function create(): View
    {
        return view('coupures.create', $this->options());
    }

    public function store(CoupureRequest $request, OutageNotifications $notifications): RedirectResponse
    {
        DB::transaction(function () use ($request, $notifications) {
            $coupure = Coupure::create($request->validated());
            if ($coupure->statut === 'active') {
                $notifications->send($coupure);
            }
        });

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

    public function update(CoupureRequest $request, Coupure $coupure, OutageNotifications $notifications): RedirectResponse
    {
        DB::transaction(function () use ($request, $coupure, $notifications) {
            // Read and lock the persisted status to avoid duplicate transitions from stale concurrent edits.
            $current = Coupure::whereKey($coupure->id)->lockForUpdate()->firstOrFail();
            $previous = $current->statut;
            $current->update($request->validated());
            if ($previous !== $current->statut) {
                $notifications->send($current);
            }
        });

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

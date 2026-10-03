<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quartier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuartierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $quartiers = Quartier::query()
            ->when($search !== '', fn ($query) => $query
                ->where('nom', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%")
                ->orWhere('code_postal', 'like', "%{$search}%"))
            ->orderBy('ville')
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.quartiers.index', [
            'quartiers' => $quartiers,
            'search' => $search,
            'totalQuartiers' => Quartier::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.quartiers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $quartier = Quartier::create($this->validated($request));

        return redirect()->route('admin.quartiers.index')->with('status', 'Neighborhood created.');
    }

    public function edit(Quartier $quartier): View
    {
        return view('pages.admin.quartiers.edit', ['quartier' => $quartier]);
    }

    public function update(Request $request, Quartier $quartier): RedirectResponse
    {
        $quartier->update($this->validated($request));

        return redirect()->route('admin.quartiers.index')->with('status', 'Neighborhood updated.');
    }

    public function destroy(Quartier $quartier): RedirectResponse
    {
        $quartier->delete();

        return redirect()->route('admin.quartiers.index')->with('status', 'Neighborhood deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'code_postal' => ['required', 'string', 'max:10'],
        ]);
    }
}

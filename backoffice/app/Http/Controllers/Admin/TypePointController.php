<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypePoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TypePointController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $types = TypePoint::query()
            ->withCount('pointFraicheurs')
            ->when($search !== '', fn ($query) => $query->where('nom', 'like', "%{$search}%"))
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.type-points.index', [
            'types' => $types,
            'search' => $search,
            'totalTypes' => TypePoint::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.type-points.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $type = TypePoint::create($this->validated($request));

        return redirect()->route('admin.type-points.show', $type)->with('status', 'Point type created successfully.');
    }

    public function show(TypePoint $typePoint): View
    {
        $typePoint->load(['pointFraicheurs' => fn ($query) => $query->with('quartier')->latest()]);

        return view('pages.admin.type-points.show', ['type' => $typePoint]);
    }

    public function edit(TypePoint $typePoint): View
    {
        return view('pages.admin.type-points.edit', ['type' => $typePoint]);
    }

    public function update(Request $request, TypePoint $typePoint): RedirectResponse
    {
        $typePoint->update($this->validated($request, $typePoint));

        return redirect()->route('admin.type-points.show', $typePoint)->with('status', 'Point type updated successfully.');
    }

    public function destroy(TypePoint $typePoint): RedirectResponse
    {
        $count = $typePoint->pointFraicheurs()->count();

        // Integrity rule (restrictOnDelete): cannot delete a type still assigned to cooling points
        if ($count > 0) {
            return redirect()->route('admin.type-points.show', $typePoint)
                ->with('error', "This point type cannot be deleted because {$count} cooling point(s) are attached to it.");
        }

        $typePoint->delete();

        return redirect()->route('admin.type-points.index')->with('status', 'Point type deleted successfully.');
    }

    private function validated(Request $request, ?TypePoint $type = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:100', Rule::unique('type_points', 'nom')->ignore($type)],
            'icone' => ['nullable', 'string', Rule::in(TypePoint::ICONS)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveConseilRequest;
use App\Models\CategorieConseil;
use App\Models\Conseil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConseilController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categorie_conseils,id'],
            'status' => ['nullable', Rule::in(['published', 'draft'])],
        ]);
        $search = trim($filters['q'] ?? '');
        $categoryId = $filters['category'] ?? '';
        $status = $filters['status'] ?? '';
        $conseils = Conseil::with('categorieConseil')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('titre', 'like', "%{$search}%")->orWhere('resume', 'like', "%{$search}%")))
            ->when($categoryId !== '', fn ($query) => $query->where('categorie_conseil_id', $categoryId))
            ->when($status !== '', fn ($query) => $query->where('actif', $status === 'published'))
            ->latest()->paginate(10)->withQueryString();
        $categories = CategorieConseil::orderBy('nom')->get();

        return view('pages.admin.conseils.index', compact('conseils', 'categories', 'search', 'categoryId', 'status'));
    }

    public function create(Request $request): View
    {
        $selection = $request->validate(['category' => ['nullable', 'integer', 'exists:categorie_conseils,id']]);
        $conseil = new Conseil([
            'categorie_conseil_id' => $selection['category'] ?? null,
            'public_cible' => 'everyone', 'situation' => 'both', 'actif' => false,
        ]);

        return view('pages.admin.conseils.create', ['conseil' => $conseil, 'categories' => CategorieConseil::orderBy('nom')->get()]);
    }

    public function store(SaveConseilRequest $request): RedirectResponse
    {
        $conseil = Conseil::create($request->validated());

        return redirect()->route('admin.conseils.show', $conseil)->with('status', __('Advice article created.'));
    }

    public function show(Conseil $conseil): View
    {
        return view('pages.admin.conseils.show', ['conseil' => $conseil->load('categorieConseil')]);
    }

    public function edit(Conseil $conseil): View
    {
        return view('pages.admin.conseils.edit', ['conseil' => $conseil, 'categories' => CategorieConseil::orderBy('nom')->get()]);
    }

    public function update(SaveConseilRequest $request, Conseil $conseil): RedirectResponse
    {
        $conseil->update($request->validated());

        return redirect()->route('admin.conseils.show', $conseil)->with('status', __('Advice article updated.'));
    }

    public function publication(Request $request, Conseil $conseil): RedirectResponse
    {
        $data = $request->validate(['actif' => ['required', 'boolean']]);
        $conseil->update($data);

        return back()->with('status', $conseil->actif ? __('Advice published. Residents can now read it.') : __('Advice moved to draft. It is hidden from residents.'));
    }

    public function destroy(Conseil $conseil): RedirectResponse
    {
        $conseil->delete();

        return redirect()->route('admin.conseils.index')->with('status', __('Advice article deleted.'));
    }
}

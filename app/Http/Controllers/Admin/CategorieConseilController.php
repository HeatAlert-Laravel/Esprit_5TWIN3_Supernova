<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCategorieConseilRequest;
use App\Models\CategorieConseil;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategorieConseilController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['q'] ?? '');
        $categories = CategorieConseil::withCount(['conseils', 'conseils as published_count' => fn ($query) => $query->published()])
            ->when($search !== '', fn ($query) => $query->where('nom', 'like', "%{$search}%"))
            ->orderBy('nom')->paginate(10)->withQueryString();

        return view('pages.admin.categorie-conseils.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        return view('pages.admin.categorie-conseils.create', ['categorieConseil' => new CategorieConseil(['icone' => 'lightbulb'])]);
    }

    public function store(SaveCategorieConseilRequest $request): RedirectResponse
    {
        $category = CategorieConseil::create($request->validated());

        return redirect()->route('admin.categorie-conseils.show', $category)->with('status', __('Advice category created.'));
    }

    public function show(CategorieConseil $categorieConseil): View
    {
        $conseils = $categorieConseil->conseils()->orderBy('titre')->paginate(10);

        return view('pages.admin.categorie-conseils.show', compact('categorieConseil', 'conseils'));
    }

    public function edit(CategorieConseil $categorieConseil): View
    {
        return view('pages.admin.categorie-conseils.edit', compact('categorieConseil'));
    }

    public function update(SaveCategorieConseilRequest $request, CategorieConseil $categorieConseil): RedirectResponse
    {
        $categorieConseil->update($request->validated());

        return redirect()->route('admin.categorie-conseils.show', $categorieConseil)->with('status', __('Advice category updated.'));
    }

    public function destroy(CategorieConseil $categorieConseil): RedirectResponse
    {
        if ($categorieConseil->conseils()->exists()) {
            return back()->with('error', __('Move or delete the advice in this category before deleting it.'));
        }

        try {
            $categorieConseil->delete();
        } catch (QueryException $exception) {
            // The database also protects against an article being added during deletion.
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return back()->with('error', __('Move or delete the advice in this category before deleting it.'));
        }

        return redirect()->route('admin.categorie-conseils.index')->with('status', __('Advice category deleted.'));
    }
}

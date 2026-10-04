<?php

namespace App\Http\Controllers;

use App\Models\CategorieConseil;
use App\Models\Conseil;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdviceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categorie_conseils,id'],
            'audience' => ['nullable', Rule::in(array_keys(Conseil::AUDIENCES))],
            'situation' => ['nullable', Rule::in(['heatwave', 'outage'])],
        ]);
        $search = trim($filters['q'] ?? '');
        $categoryId = $filters['category'] ?? '';
        $audience = $filters['audience'] ?? '';
        $situation = $filters['situation'] ?? '';
        $conseils = Conseil::published()->with('categorieConseil')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('titre', 'like', "%{$search}%")->orWhere('resume', 'like', "%{$search}%")->orWhere('contenu', 'like', "%{$search}%")))
            ->when($categoryId !== '', fn ($query) => $query->where('categorie_conseil_id', $categoryId))
            ->when($audience !== '', fn ($query) => $query->whereIn('public_cible', ['everyone', $audience]))
            ->when($situation !== '', fn ($query) => $query->whereIn('situation', [$situation, 'both']))
            ->orderBy(CategorieConseil::select('nom')->whereColumn('categorie_conseils.id', 'conseils.categorie_conseil_id'))
            ->orderBy('titre')->paginate(12)->withQueryString();
        $categories = CategorieConseil::whereHas('conseils', fn ($query) => $query->published())->orderBy('nom')->get();

        return view('pages.front.advice.index', compact('conseils', 'categories', 'search', 'categoryId', 'audience', 'situation'));
    }

    public function show(Conseil $conseil): View
    {
        abort_unless($conseil->actif, 404);

        return $this->article($conseil, false);
    }

    public function preview(Conseil $conseil): View
    {
        abort_unless(auth()->user()?->role === 'ADMIN', 403);

        return $this->article($conseil, true);
    }

    private function article(Conseil $conseil, bool $preview): View
    {
        $conseil->load('categorieConseil');
        $related = $preview ? collect() : $conseil->categorieConseil->conseils()->published()
            ->whereKeyNot($conseil->id)->orderBy('titre')->limit(3)->get();

        return view('pages.front.advice.show', compact('conseil', 'related', 'preview'));
    }
}

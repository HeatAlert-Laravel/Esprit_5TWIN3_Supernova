<?php

namespace App\Http\Controllers;

use App\Models\CategorieConseil;
use App\Models\Conseil;
use App\Support\AdviceNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdviceController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filters = $request->validate(AdviceNavigation::rules());
        $onlySaved = $request->routeIs('advice.saved');
        $indexRoute = $onlySaved ? 'advice.saved' : 'advice';
        $returnTo = AdviceNavigation::relative(route($indexRoute, array_filter($filters, fn ($value) => $value !== null && $value !== '')));
        $search = trim($filters['q'] ?? '');
        $categoryId = $filters['category'] ?? '';
        $audience = $filters['audience'] ?? '';
        $situation = $filters['situation'] ?? '';
        $conseils = Conseil::published()->with('categorieConseil')
            ->when($onlySaved, fn ($query) => $query->whereHas('savedByUsers', fn ($users) => $users->where('users.id', auth()->id())))
            ->when(auth()->check(), fn ($query) => $query->withExists(['savedByUsers as is_saved' => fn ($users) => $users->where('users.id', auth()->id())]))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('titre', 'like', "%{$search}%")->orWhere('resume', 'like', "%{$search}%")->orWhere('contenu', 'like', "%{$search}%")))
            ->when($categoryId !== '', fn ($query) => $query->where('categorie_conseil_id', $categoryId))
            ->when($audience !== '', fn ($query) => $query->whereIn('public_cible', ['everyone', $audience]))
            ->when($situation !== '', fn ($query) => $query->whereIn('situation', [$situation, 'both']))
            ->orderBy(CategorieConseil::select('nom')->whereColumn('categorie_conseils.id', 'conseils.categorie_conseil_id'))
            ->orderBy('titre')->paginate(12)->withQueryString();
        $categories = CategorieConseil::whereHas('conseils', fn ($query) => $query->published()
            ->when($onlySaved, fn ($articles) => $articles->whereHas('savedByUsers', fn ($users) => $users->where('users.id', auth()->id()))))
            ->orderBy('nom')->get();

        if ($conseils->currentPage() > $conseils->lastPage()) {
            return redirect()->route($indexRoute, array_merge($filters, ['page' => $conseils->lastPage()]))
                ->with('status', $request->session()->get('status'));
        }

        return view('pages.front.advice.index', compact('conseils', 'categories', 'search', 'categoryId', 'audience', 'situation', 'onlySaved', 'indexRoute', 'returnTo'));
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
        if (auth()->check()) {
            $conseil->loadExists(['savedByUsers as is_saved' => fn ($users) => $users->where('users.id', auth()->id())]);
        }
        $backUrl = AdviceNavigation::returnUrl(request()->query('return'));
        $returnTo = AdviceNavigation::relative($backUrl);
        $related = $preview ? collect() : $conseil->categorieConseil->conseils()->published()
            ->when(auth()->check(), fn ($query) => $query->withExists(['savedByUsers as is_saved' => fn ($users) => $users->where('users.id', auth()->id())]))
            ->whereKeyNot($conseil->id)->orderBy('titre')->limit(3)->get();

        return view('pages.front.advice.show', compact('conseil', 'related', 'preview', 'backUrl', 'returnTo'));
    }
}

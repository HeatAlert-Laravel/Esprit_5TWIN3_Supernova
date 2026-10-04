<?php

namespace App\Http\Controllers;

use App\Models\Conseil;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $featuredAdvice = Conseil::published()->with('categorieConseil')
            ->when($request->user(), fn ($query) => $query->withExists([
                'savedByUsers as is_saved' => fn ($users) => $users->where('users.id', $request->user()->id),
            ]))
            ->orderByRaw("CASE WHEN public_cible = 'everyone' THEN 0 ELSE 1 END")
            ->latest('updated_at')->orderByDesc('id')->limit(3)->get();

        return view('pages.front.home', compact('featuredAdvice'));
    }
}

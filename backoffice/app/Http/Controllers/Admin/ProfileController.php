<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        // Optional GET filters: resident name / email search and an exact neighborhood match.
        $search = trim((string) $request->query('q', ''));
        $neighborhood = trim((string) $request->query('neighborhood', ''));

        $profiles = Profile::with('user')->withCount('sensitiveEquipments')
            ->when($search !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user
                ->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($neighborhood !== '', fn ($query) => $query->where('neighborhood', $neighborhood))
            ->latest()->paginate(10)->withQueryString();

        return view('pages.admin.profiles.index', [
            'profiles' => $profiles,
            'neighborhoods' => $this->neighborhoods(),
            'filters' => ['q' => $search, 'neighborhood' => $neighborhood],
            'totalProfiles' => Profile::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.profiles.create', [
            'users' => User::doesntHave('profile')->orderBy('name')->get(),
            'neighborhoods' => $this->neighborhoods(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $profile = Profile::create($data);

        return redirect()->route('admin.profiles.show', $profile)->with('status', 'Profile created.');
    }

    public function show(Profile $profile): View
    {
        return view('pages.admin.profiles.show', ['profile' => $profile->load('user', 'sensitiveEquipments')]);
    }

    public function edit(Profile $profile): View
    {
        return view('pages.admin.profiles.edit', [
            'profile' => $profile,
            'users' => User::whereDoesntHave('profile')->orWhere('id', $profile->user_id)->orderBy('name')->get(),
            'neighborhoods' => $this->neighborhoods(),
        ]);
    }

    public function update(Request $request, Profile $profile): RedirectResponse
    {
        $profile->update($this->validated($request, $profile));

        return redirect()->route('admin.profiles.show', $profile)->with('status', 'Profile updated.');
    }

    public function destroy(Profile $profile): RedirectResponse
    {
        $profile->delete();

        return redirect()->route('admin.profiles.index')->with('status', 'Profile deleted.');
    }

    /** Neighborhood values already stored (free text): used for the filter and as input suggestions only. */
    private function neighborhoods()
    {
        return Profile::query()->distinct()->orderBy('neighborhood')->pluck('neighborhood');
    }

    private function validated(Request $request, ?Profile $profile = null): array
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id'), Rule::unique('profiles', 'user_id')->ignore($profile?->id)],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'has_fragile_person' => ['sometimes', 'boolean'],
        ]);
        $data['has_fragile_person'] = $request->boolean('has_fragile_person');

        return $data;
    }
}

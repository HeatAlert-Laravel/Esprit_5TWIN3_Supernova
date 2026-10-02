<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** How many households the "check first" list shows. */
    private const CHECK_FIRST_LIMIT = 5;

    public function index(): View
    {
        $profilesCount = Profile::count();
        $incompleteCount = Profile::incomplete()->count();

        return view('pages.admin.dashboard', [
            'usersCount' => User::count(),
            'residentsWithoutProfile' => User::where('role', 'USER')->doesntHave('profile')->count(),
            'profilesCount' => $profilesCount,
            'incompleteProfilesCount' => $incompleteCount,
            'completeProfilesCount' => $profilesCount - $incompleteCount,
            'equipmentCount' => SensitiveEquipment::count(),
            // [priority_level => count] using the existing low / medium / high values only.
            'equipmentByPriority' => SensitiveEquipment::query()->selectRaw('priority_level, count(*) as total')
                ->groupBy('priority_level')->pluck('total', 'priority_level'),
            'checkFirst' => $this->householdsToCheckFirst(),
        ]);
    }

    /**
     * "Households to check first" — a documented, data-only rule (no medical or emergency scoring):
     *   a household is listed when its profile is incomplete (a required detail is blank)
     *   OR it has at least one equipment item with priority_level = 'high'.
     * Order: incomplete profiles first, then more high-priority items, then households that flagged
     * a fragile person, then the most recently updated.
     */
    private function householdsToCheckFirst()
    {
        return Profile::query()
            ->select('profiles.*')
            ->selectRaw(Profile::incompleteSql().' as is_incomplete')
            ->with('user')
            ->withCount([
                'sensitiveEquipments',
                'sensitiveEquipments as high_priority_count' => fn ($query) => $query->where('priority_level', 'high'),
            ])
            ->where(fn ($query) => $query->incomplete()
                ->orWhereHas('sensitiveEquipments', fn ($equipment) => $equipment->where('priority_level', 'high')))
            ->orderByDesc('is_incomplete')
            ->orderByDesc('high_priority_count')
            ->orderByDesc('has_fragile_person')
            ->orderByDesc('updated_at')
            ->limit(self::CHECK_FIRST_LIMIT)
            ->get();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
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
            'equipmentStats' => $this->equipmentStats(),
            'typesCount' => TypeEquipement::count(),
            // [risk_level => count], read from the TypeEquipement each equipment belongs to.
            'equipmentByRisk' => SensitiveEquipment::query()
                ->join('type_equipements', 'type_equipements.id', '=', 'sensitive_equipments.type_equipement_id')
                ->selectRaw('type_equipements.risk_level as risk_level, count(*) as total')
                ->groupBy('type_equipements.risk_level')->pluck('total', 'risk_level'),
            'checkFirst' => $this->householdsToCheckFirst(),
        ]);
    }

    /**
     * Real equipment KPIs from one aggregate query over equipment joined to its type
     * (no statistics table). Risk and sensitivities are read from TypeEquipement only.
     *
     * @return array{total: int, high_risk: int, heat: int, outage: int}
     */
    private function equipmentStats(): array
    {
        $highRisk = "'".implode("','", TypeEquipement::HIGH_RISK_LEVELS)."'";

        $row = SensitiveEquipment::query()
            ->join('type_equipements', 'type_equipements.id', '=', 'sensitive_equipments.type_equipement_id')
            ->selectRaw("count(*) as total,
                coalesce(sum(case when type_equipements.risk_level in ({$highRisk}) then 1 else 0 end), 0) as high_risk,
                coalesce(sum(case when type_equipements.sensitive_to_heat = 1 then 1 else 0 end), 0) as heat,
                coalesce(sum(case when type_equipements.sensitive_to_outage = 1 then 1 else 0 end), 0) as outage")
            ->first();

        return [
            'total' => (int) $row->total,
            'high_risk' => (int) $row->high_risk,
            'heat' => (int) $row->heat,
            'outage' => (int) $row->outage,
        ];
    }

    /**
     * "Households to check first" — a documented, data-only rule (no medical or emergency scoring):
     *   a household is listed when its profile is incomplete (a required detail is blank)
     *   OR it has at least one equipment item whose type is rated high or critical risk.
     * Order: incomplete profiles first, then more high-risk items, then households that flagged
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
                'sensitiveEquipments as high_risk_count' => fn ($query) => $query->highRisk(),
            ])
            ->where(fn ($query) => $query->incomplete()
                ->orWhereHas('sensitiveEquipments', fn ($equipment) => $equipment->highRisk()))
            ->orderByDesc('is_incomplete')
            ->orderByDesc('high_risk_count')
            ->orderByDesc('has_fragile_person')
            ->orderByDesc('updated_at')
            ->limit(self::CHECK_FIRST_LIMIT)
            ->get();
    }
}

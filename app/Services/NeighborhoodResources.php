<?php

namespace App\Services;

use App\Models\Conseil;
use App\Models\Coupure;
use App\Models\PointFraicheur;
use App\Models\Quartier;
use Illuminate\Support\Collection;

/**
 * What a resident can use around a neighborhood: advice, cooling points and outages.
 *
 * Strictly READ-ONLY on the other modules' data (Conseil, PointFraicheur, Coupure): it only uses their
 * existing public scopes and columns and never changes their models, migrations or routes.
 */
class NeighborhoodResources
{
    /**
     * Published advice matching the situation: always heatwave advice, plus power-outage advice when the alert warns of an outage risk.
     *
     * @return Collection<int, Conseil>
     */
    public function advice(bool $outageRisk = false, int $limit = 4): Collection
    {
        $situations = $outageRisk ? ['heatwave', 'both', 'outage'] : ['heatwave', 'both'];

        return Conseil::published()->with('categorieConseil')
            ->whereIn('situation', $situations)
            // Advice covering both situations first, then the most recently updated.
            ->orderByRaw("CASE situation WHEN 'both' THEN 0 WHEN 'heatwave' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->limit($limit)->get();
    }

    /** @return Collection<int, PointFraicheur> */
    public function coolingPoints(Quartier $quartier, int $limit = 4): Collection
    {
        return PointFraicheur::actifs()->with('typePoint')
            ->where('quartier_id', $quartier->id)->orderBy('nom')->limit($limit)->get();
    }

    public function coolingPointsCount(Quartier $quartier): int
    {
        return PointFraicheur::actifs()->where('quartier_id', $quartier->id)->count();
    }

    /** @return Collection<int, Coupure> */
    public function activeOutages(Quartier $quartier, int $limit = 3): Collection
    {
        return $quartier->coupures()->where('statut', 'active')->latest('date_debut')->limit($limit)->get();
    }

    public function activeOutagesCount(Quartier $quartier): int
    {
        return $quartier->coupures()->where('statut', 'active')->count();
    }
}

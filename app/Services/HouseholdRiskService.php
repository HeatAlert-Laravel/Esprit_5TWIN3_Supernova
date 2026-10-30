<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use Illuminate\Support\Collection;

/**
 * Deterministic household exposure score for Module 5 (Profile + SensitiveEquipment).
 *
 * This is the single source of truth for the LOCAL score and level. The AI advisor
 * receives the result of this class and may only explain it: it can never change it.
 *
 * The score measures "heat/outage exposure and preparation needs" for a household.
 * It is NOT a medical probability of an accident and must never be presented as one.
 *
 * Weights (all additive, then capped at 100):
 *   - a fragile person lives in the household ............ +30
 *   - one sensitive equipment ............................ +2 each (presence)
 *   - equipment type risk: critical +20, high +15, medium +8, low +3
 *   - type sensitive to heat ............................. +5 each
 *   - type sensitive to outages .......................... +5 each
 *
 * Thresholds (explicit and reproducible without any AI call):
 *   LOW    = 0 .. 24
 *   MEDIUM = 25 .. 59
 *   HIGH   = 60 .. 100
 */
class HouseholdRiskService
{
    public const LEVEL_LOW = 'LOW';

    public const LEVEL_MEDIUM = 'MEDIUM';

    public const LEVEL_HIGH = 'HIGH';

    /** First score that counts as MEDIUM. */
    public const MEDIUM_FROM = 25;

    /** First score that counts as HIGH. */
    public const HIGH_FROM = 60;

    /** Maximum score value. */
    public const MAX_SCORE = 100;

    /** Weight added when a fragile person lives in the household. */
    public const FRAGILE_PERSON_POINTS = 30;

    /** Points added for every equipment record, regardless of its type. */
    public const EQUIPMENT_POINTS = 2;

    /** Points added when the type is sensitive to heat. */
    public const HEAT_POINTS = 5;

    /** Points added when the type is sensitive to power outages. */
    public const OUTAGE_POINTS = 5;

    /** Risk-level contribution of the equipment type. */
    private const RISK_POINTS = ['critical' => 20, 'high' => 15, 'medium' => 8, 'low' => 3];

    /**
     * Assess one household. Same profile data always gives the same result.
     *
     * The score is an internal HeatAlert exposure/preparation indicator, capped at 100.
     * It is NOT a medical score, a medical prediction or an accident probability.
     *
     * @return array{
     *     score: int,
     *     level: string,
     *     factors: list<array{kind: string, title: string, label: string, points: int, level: ?string, tags: list<string>, detail: string}>,
     *     local_actions: list<string>,
     *     equipment_count: int,
     *     has_fragile_person: bool,
     *     ai_payload: array<string, mixed>
     * }
     */
    public function assess(Profile $profile): array
    {
        $equipment = $profile->sensitiveEquipments()
            ->with('typeEquipement')
            ->orderBy('id')
            ->get();

        $score = 0;
        $factors = [];

        if ($profile->has_fragile_person) {
            $score += self::FRAGILE_PERSON_POINTS;
            $factors[] = [
                'kind' => 'fragile_person',
                'title' => 'Personne fragile',
                'label' => 'Une personne fragile vit dans le foyer',
                'points' => self::FRAGILE_PERSON_POINTS,
                'level' => null,
                'tags' => [],
                'detail' => 'Présente dans le foyer',
            ];
        }

        foreach ($equipment as $item) {
            $factor = $this->equipmentFactor($item);

            $score += $factor['points'];
            $factors[] = $factor;
        }

        if ($equipment->isEmpty()) {
            $factors[] = [
                'kind' => 'info',
                'title' => 'Aucun équipement sensible',
                'label' => 'Aucun équipement sensible enregistré',
                'points' => 0,
                'level' => null,
                'tags' => [],
                'detail' => 'Ajoutez vos appareils pour affiner l’évaluation.',
            ];
        }

        $score = max(0, min(self::MAX_SCORE, $score));

        return [
            'score' => $score,
            'level' => $this->level($score),
            'factors' => $factors,
            'local_actions' => $this->localActions($profile, $equipment),
            'equipment_count' => $equipment->count(),
            'has_fragile_person' => (bool) $profile->has_fragile_person,
            'ai_payload' => $this->aiPayload($profile, $equipment, $score, $factors),
        ];
    }

    public function level(int $score): string
    {
        return match (true) {
            $score >= self::HIGH_FROM => self::LEVEL_HIGH,
            $score >= self::MEDIUM_FROM => self::LEVEL_MEDIUM,
            default => self::LEVEL_LOW,
        };
    }

    /**
     * Points and human-readable reason for one equipment record.
     *
     * @return array{kind: string, title: string, label: string, points: int, level: ?string, tags: list<string>, detail: string}
     */
    private function equipmentFactor(SensitiveEquipment $item): array
    {
        $type = $item->typeEquipement;
        $risk = $type?->risk_level;

        $points = self::EQUIPMENT_POINTS + (self::RISK_POINTS[$risk] ?? 0);
        $reasons = [];
        $tags = [];

        if ($type?->sensitive_to_heat) {
            $points += self::HEAT_POINTS;
            $reasons[] = 'sensible à la chaleur';
            $tags[] = 'Chaleur';
        }

        if ($type?->sensitive_to_outage) {
            $points += self::OUTAGE_POINTS;
            $reasons[] = 'dépend de l’électricité';
            $tags[] = 'Coupure électrique';
        }

        $name = $type?->name ?? 'Type non renseigné';
        $level = $risk ?? 'inconnu';
        $suffix = $reasons === [] ? '' : ' — '.implode(', ', $reasons);

        return [
            'kind' => 'equipment',
            'title' => $name,
            'label' => "Équipement sensible : {$name} (risque {$level}){$suffix}",
            'points' => $points,
            'level' => $risk === null ? null : strtoupper($risk),
            'tags' => $tags,
            'detail' => $tags === [] ? 'Aucune sensibilité particulière' : implode(' · ', $tags),
        ];
    }

    /**
     * Deterministic advice used by the UI and by the AI fallback. No AI, no stored data.
     *
     * @param  Collection<int, SensitiveEquipment>  $equipment
     * @return list<string>
     */
    private function localActions(Profile $profile, Collection $equipment): array
    {
        $actions = [];

        if ($profile->has_fragile_person) {
            $actions[] = 'Identifiez une pièce fraîche et gardez de l’eau à disposition pour la personne fragile du foyer.';
        }

        if ($equipment->contains(fn (SensitiveEquipment $item): bool => (bool) $item->typeEquipement?->sensitive_to_outage)) {
            $actions[] = 'Préparez une solution de secours pour les appareils qui dépendent de l’électricité.';
        }

        if ($equipment->contains(fn (SensitiveEquipment $item): bool => (bool) $item->typeEquipement?->sensitive_to_heat)) {
            $actions[] = 'Éloignez les appareils sensibles de la chaleur directe et vérifiez leur bon fonctionnement.';
        }

        if ($equipment->isEmpty()) {
            $actions[] = 'Enregistrez vos équipements sensibles pour obtenir une analyse adaptée à votre foyer.';
        }

        $actions[] = 'Suivez les alertes météo et les consignes des autorités locales pendant les fortes chaleurs.';

        return array_slice(array_values(array_unique($actions)), 0, 5);
    }

    /**
     * Data minimisation: only household/equipment business fields are sent to Mistral.
     * Names, addresses, phone numbers, e-mails, free-text notes and identifiers are never included.
     *
     * @param  Collection<int, SensitiveEquipment>  $equipment
     * @param  list<array{kind: string, title: string, label: string, points: int, level: ?string, tags: list<string>, detail: string}>  $factors
     * @return array<string, mixed>
     */
    private function aiPayload(Profile $profile, Collection $equipment, int $score, array $factors): array
    {
        return [
            'household' => [
                'fragile_person' => (bool) $profile->has_fragile_person,
                'profile_complete' => $profile->isComplete(),
                'sensitive_equipment_count' => $equipment->count(),
            ],
            'equipment' => $equipment->map(fn (SensitiveEquipment $item): array => [
                'type' => $item->typeEquipement?->name,
                'risk_level' => $item->typeEquipement?->risk_level,
                'sensitive_to_heat' => (bool) $item->typeEquipement?->sensitive_to_heat,
                'sensitive_to_outage' => (bool) $item->typeEquipement?->sensitive_to_outage,
                'has_notes' => filled($item->description),
            ])->values()->all(),
            'local_assessment' => [
                'score' => $score,
                'level' => $this->level($score),
                'factors' => array_column($factors, 'label'),
            ],
        ];
    }
}

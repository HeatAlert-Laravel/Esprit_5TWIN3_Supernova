<?php

namespace App\Http\Controllers;

use App\Services\HouseholdRiskService;
use App\Services\MistralHouseholdAdvisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Module 5 AI advisor endpoint: "Analyse intelligente du risque du foyer".
 *
 * The household is ALWAYS resolved from the authenticated user; no profile_id/user_id is
 * accepted from the client, so a resident can never analyse somebody else's household.
 */
class HouseholdRiskAiController extends Controller
{
    public const MISSING_PROFILE_MESSAGE = 'Complétez d’abord votre profil de foyer avant de lancer l’analyse.';

    public const UNAVAILABLE_MESSAGE = 'L’analyse IA est temporairement indisponible. Votre évaluation locale HeatAlert reste disponible.';

    public function store(
        Request $request,
        HouseholdRiskService $riskService,
        MistralHouseholdAdvisor $advisor,
    ): JsonResponse {
        $profile = $request->user()->profile;

        if ($profile === null) {
            return $this->json([
                'ok' => false,
                'reason' => 'missing_profile',
                'message' => self::MISSING_PROFILE_MESSAGE,
            ], 422);
        }

        $profile->load('sensitiveEquipments.typeEquipement');
        $assessment = $riskService->assess($profile);
        $ai = $advisor->advise($assessment['ai_payload']);

        return $this->json([
            'ok' => true,
            'score' => $assessment['score'],
            'level' => $assessment['level'],
            'factors' => $assessment['factors'],
            'local_actions' => $assessment['local_actions'],
            'equipment_count' => $assessment['equipment_count'],
            'has_fragile_person' => $assessment['has_fragile_person'],
            'ai' => $ai,
            'ai_available' => $ai !== null,
            'message' => $ai === null ? self::UNAVAILABLE_MESSAGE : null,
        ]);
    }

    private function json(array $payload, int $status = 200): JsonResponse
    {
        // AI output is transient: never cache it in a shared proxy or the browser history.
        return response()->json($payload, $status, ['Cache-Control' => 'no-store, private']);
    }
}

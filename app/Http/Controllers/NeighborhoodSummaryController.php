<?php

namespace App\Http\Controllers;

use App\Models\Quartier;
use App\Services\AlertExplainer;
use App\Services\GeminiTextClient;
use App\Services\NeighborhoodWeekSummary;
use App\Services\OpenMeteoForecast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class NeighborhoodSummaryController extends Controller
{
    public function store(Request $request, Quartier $quartier, OpenMeteoForecast $forecast, NeighborhoodWeekSummary $summary): JsonResponse
    {
        $data = $request->validate(['lang' => ['required', Rule::in(array_keys(AlertExplainer::LANGUAGES))]]);

        // Do not even read the forecast when the AI feature is switched off.
        abort_unless(GeminiTextClient::isConfigured(), 503, __('The explanation is temporarily unavailable. Please try again later.'));

        try {
            // Same call (and same cache) as the page itself: no extra request to the weather service.
            $daily = $forecast->detailed($quartier, 7)['daily'];
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], $quartier->hasCoordinates() ? 503 : 422);
        }

        $alerts = $quartier->alerteMeteos()->published()
            ->where(fn ($query) => $query->current()
                ->orWhere(fn ($upcoming) => $upcoming->whereDate('date_debut', '>', today()->toDateString())
                    ->whereDate('date_debut', '<=', today()->addDays(7)->toDateString())))
            ->mostSevereFirst()->orderBy('date_debut')->limit(6)->get();

        return response()->json($summary->summarize($quartier, $daily, $alerts, $data['lang']))
            ->header('Cache-Control', 'no-store');
    }
}

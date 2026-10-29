<?php

namespace App\Http\Controllers;

use App\Models\AlerteMeteo;
use App\Services\AlertExplainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlertExplainController extends Controller
{
    public function store(Request $request, AlerteMeteo $alerte, AlertExplainer $explainer): JsonResponse
    {
        abort_unless($alerte->publiee, 404);

        $data = $request->validate(['lang' => ['required', Rule::in(array_keys(AlertExplainer::LANGUAGES))]]);

        return response()->json($explainer->explain($alerte->load('quartier'), $data['lang']))
            ->header('Cache-Control', 'no-store');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Conseil;
use App\Services\AdviceAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdviceAssistantController extends Controller
{
    public function store(Request $request, Conseil $conseil, AdviceAssistant $assistant): JsonResponse
    {
        abort_unless($conseil->actif, 404);
        $data = $request->validate([
            'action' => ['required', Rule::in(['summary', 'checklist', 'ask'])],
            'question' => ['nullable', 'required_if:action,ask', 'string', 'max:500'],
            'history' => ['nullable', 'string', 'json', 'max:50000'],
        ]);
        $history = json_decode($data['history'] ?? '[]', true);
        Validator::make(['history' => $history], [
            'history' => ['present', 'array', 'list', 'max:6'],
            'history.*' => ['array:role,content'],
            'history.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required', 'string', 'max:8000'],
        ])->validate();

        return response()->json($assistant->answer($conseil, $data['action'], $data['action'] === 'ask' ? trim($data['question']) : '', $data['action'] === 'ask' ? $history : []))
            ->header('Cache-Control', 'no-store');
    }
}

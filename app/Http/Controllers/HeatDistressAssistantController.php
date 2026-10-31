<?php

namespace App\Http\Controllers;

use App\Services\HeatDistressAi;
use App\Services\HeatDistressAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HeatDistressAssistantController extends Controller
{
    public function store(Request $request, HeatDistressAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'language' => ['required', Rule::in(['fr', 'en', 'ar', 'derja'])],
            'message' => ['required_without:location_token', 'nullable', 'string', 'max:1000'],
            'location_token' => ['nullable', 'string', 'max:8192'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'required_with:latitude', 'numeric', 'between:0,200'],
            'accessible' => ['sometimes', 'boolean'],
        ]);

        return response()->json($assistant->answer($data))->header('Cache-Control', 'no-store');
    }

    public function transcribe(Request $request, HeatDistressAi $ai): JsonResponse
    {
        $data = $request->validate([
            'language' => ['required', Rule::in(['fr', 'en', 'ar', 'derja'])],
            'audio' => ['required', 'file', 'max:4096',
                'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/mpeg'],
        ]);

        return response()->json(['text' => $ai->transcribe($request->file('audio'), $data['language'])])
            ->header('Cache-Control', 'no-store');
    }
}

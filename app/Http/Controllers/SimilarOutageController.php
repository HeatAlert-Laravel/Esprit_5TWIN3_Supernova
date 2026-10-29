<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimilarOutageRequest;
use App\Services\SimilarOutages;
use Illuminate\Http\JsonResponse;

class SimilarOutageController extends Controller
{
    public function store(SimilarOutageRequest $request, SimilarOutages $service): JsonResponse
    {
        return response()->json($service->find($request->user(), $request->validated()))
            ->header('Cache-Control', 'no-store');
    }
}

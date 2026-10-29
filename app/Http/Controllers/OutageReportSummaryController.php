<?php

namespace App\Http\Controllers;

use App\Models\Coupure;
use App\Services\OutageReportSummary;
use Illuminate\Http\JsonResponse;

class OutageReportSummaryController extends Controller
{
    public function store(Coupure $coupure, OutageReportSummary $summary): JsonResponse
    {
        return response()->json($summary->generate($coupure))
            ->header('Cache-Control', 'no-store');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\MyReportsRequest;
use App\Models\Signalement;
use App\Support\M2Listing;
use Illuminate\View\View;

class ResidentReportController extends Controller
{
    public function index(MyReportsRequest $request): View
    {
        $ownReports = Signalement::where('user_id', $request->user()->id);

        return view('signalements.resident-index', [
            'signalements' => M2Listing::paginate((clone $ownReports)->with('coupure.quartier'), $request->validated(), false),
            'counts' => (clone $ownReports)->selectRaw('statut, COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut'),
        ]);
    }
}

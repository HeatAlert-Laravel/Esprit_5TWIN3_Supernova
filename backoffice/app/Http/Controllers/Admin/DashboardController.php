<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.dashboard', [
            'usersCount' => User::count(),
            'profilesCount' => Profile::count(),
            'equipmentCount' => SensitiveEquipment::count(),
        ]);
    }
}

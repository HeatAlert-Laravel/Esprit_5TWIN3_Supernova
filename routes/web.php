<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdviceAssistantController;
use App\Http\Controllers\AdviceAudioController;
use App\Http\Controllers\AdviceBookmarkController;
use App\Http\Controllers\AdviceController;
use App\Http\Controllers\AdviceVoteController;
use App\Http\Controllers\AlertExplainController;
use App\Http\Controllers\AlerteMeteoController;
use App\Http\Controllers\CategorieConseilController;
use App\Http\Controllers\ConseilController;
use App\Http\Controllers\CoolingPointController;
use App\Http\Controllers\CoupureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\OutageController;
use App\Http\Controllers\PointFraicheurController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuartierController;
use App\Http\Controllers\QuartierForecastController;
use App\Http\Controllers\ResidentProfileController;
use App\Http\Controllers\ResidentSensitiveEquipmentController;
use App\Http\Controllers\SensitiveEquipmentController;
use App\Http\Controllers\SignalementController;
use App\Http\Controllers\TypeEquipementController;
use App\Http\Controllers\TypePointController;
use App\Http\Controllers\WeatherAlertController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages (guests and signed-in users)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/weather-alerts', [WeatherAlertController::class, 'index'])->name('weather-alerts');
Route::get('/weather-alerts/{alerte}', [WeatherAlertController::class, 'show'])
    ->whereNumber('alerte')->name('weather-alerts.show');
Route::post('/weather-alerts/{alerte}/explain', [AlertExplainController::class, 'store'])
    ->whereNumber('alerte')->middleware('throttle:6,1')->name('weather-alerts.explain');
Route::get('/neighborhoods/{quartier}', [NeighborhoodController::class, 'show'])
    ->whereNumber('quartier')->middleware('throttle:60,1')->name('neighborhoods.show');
Route::get('/outages', [OutageController::class, 'index'])->name('outages');
Route::get('/cooling-points', [CoolingPointController::class, 'index'])->name('cooling-points');
Route::get('/advice', [AdviceController::class, 'index'])->name('advice');
Route::post('/advice/{conseil}/assistant', [AdviceAssistantController::class, 'store'])
    ->middleware('throttle:6,1')->name('advice.assistant');
Route::post('/advice/{conseil}/audio/{voice}', [AdviceAudioController::class, 'store'])
    ->whereIn('voice', ['female', 'male'])->middleware('throttle:6,1')->name('advice.audio.store');
Route::get('/advice/{conseil}/audio/{voice}/{version}', [AdviceAudioController::class, 'show'])
    ->whereIn('voice', ['female', 'male'])->where('version', '[a-f0-9]{64}')->name('advice.audio.show');

/*
|--------------------------------------------------------------------------
| Authentication is registered once by Laravel Fortify.
|--------------------------------------------------------------------------
| Its response sends verified ADMIN users to the dashboard and USER users
| to their profile; unverified users go to the verification notice.
*/

Route::middleware(['auth', \App\Http\Middleware\PrivateAccountResponse::class])->group(function () {
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::get('/account/security', [AccountController::class, 'edit'])->middleware('password.confirm')->name('account.security');
    Route::post('/account/avatar', [AccountController::class, 'updateAvatar'])->name('account.avatar.update');
    Route::delete('/account/avatar', [AccountController::class, 'deleteAvatar'])->name('account.avatar.destroy');
    Route::delete('/account', [AccountController::class, 'destroy'])->middleware('password.confirm')->name('account.destroy');
});

/*
|--------------------------------------------------------------------------
| Signed-in users (USER and ADMIN)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/my-profile', [ResidentProfileController::class, 'show'])->name('my-profile');
    Route::put('/my-profile', [ResidentProfileController::class, 'update'])->name('my-profile.update');

    Route::prefix('my-profile/equipment')->name('profile.equipment.')->group(function () {
        Route::get('/create', [ResidentSensitiveEquipmentController::class, 'create'])->name('create');
        Route::post('/', [ResidentSensitiveEquipmentController::class, 'store'])->name('store');
        Route::get('/{equipment}/edit', [ResidentSensitiveEquipmentController::class, 'edit'])->name('edit');
        Route::put('/{equipment}', [ResidentSensitiveEquipmentController::class, 'update'])->name('update');
        Route::delete('/{equipment}', [ResidentSensitiveEquipmentController::class, 'destroy'])->name('destroy');
    });

    Route::get('/outages/report', [OutageController::class, 'create'])->name('outages.report.create');
    Route::post('/outages/report', [OutageController::class, 'store'])->name('outages.report.store');

    Route::get('/advice/saved', [AdviceController::class, 'index'])->name('advice.saved');
    Route::get('/advice/{conseil}/vote', [AdviceController::class, 'show'])->name('advice.vote.prompt');
    Route::put('/advice/{conseil}/vote', [AdviceVoteController::class, 'update'])
        ->middleware('throttle:30,1')->name('advice.vote.update');
    Route::get('/advice/{conseil}/save', [AdviceController::class, 'show'])->name('advice.save-prompt');
    Route::put('/advice/{conseil}/bookmark', [AdviceBookmarkController::class, 'store'])->name('advice.bookmark.store');
    Route::delete('/advice/{conseil}/bookmark', [AdviceBookmarkController::class, 'destroy'])->name('advice.bookmark.destroy');
    // Administrators preview drafts in the resident layout (the controller rejects non-admins).
    Route::get('/advice/{conseil}/preview', [AdviceController::class, 'preview'])->name('advice.preview');
});

// Registered after /advice/saved so "saved" is never read as an article id.
Route::get('/advice/{conseil}', [AdviceController::class, 'show'])->name('advice.show');

/*
|--------------------------------------------------------------------------
| Admin area (ADMIN only)
|--------------------------------------------------------------------------
| "auth" redirects guests to the login page; "admin" (EnsureAdmin) answers
| 403 to any signed-in user whose role is not ADMIN.
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Neighborhoods and weather alerts
    Route::get('quartiers/{quartier}/forecast', [QuartierForecastController::class, 'show'])->middleware('throttle:30,1')->name('quartiers.forecast');
    Route::resource('quartiers', QuartierController::class);
    Route::resource('alertes-meteo', AlerteMeteoController::class)->parameters(['alertes-meteo' => 'alerte']);

    // Resident profiles, equipment types and sensitive equipment
    Route::resource('profiles', ProfileController::class);
    Route::resource('type-equipements', TypeEquipementController::class)->parameters(['type-equipements' => 'typeEquipement']);
    Route::resource('equipment', SensitiveEquipmentController::class);

    // Power outages and resident reports
    Route::resource('coupures', CoupureController::class);
    Route::patch('signalements/{signalement}/statut', [SignalementController::class, 'statut'])->name('signalements.statut');
    Route::resource('signalements', SignalementController::class);

    // Cooling points and their types
    Route::resource('type-points', TypePointController::class)->parameters(['type-points' => 'typePoint']);
    Route::resource('point-fraicheurs', PointFraicheurController::class)->parameters(['point-fraicheurs' => 'pointFraicheur']);
    Route::patch('point-fraicheurs/{pointFraicheur}/toggle-status', [PointFraicheurController::class, 'toggleStatus'])->name('point-fraicheurs.toggle-status');

    // Advice categories and articles
    Route::resource('categorie-conseils', CategorieConseilController::class)->parameters(['categorie-conseils' => 'categorieConseil']);
    Route::patch('conseils/{conseil}/publication', [ConseilController::class, 'publication'])->name('conseils.publication');
    Route::resource('conseils', ConseilController::class);
});

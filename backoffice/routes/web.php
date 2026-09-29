<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SensitiveEquipmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResidentProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.front.home')->name('home');
Route::get('/weather-alerts', fn () => view('pages.front.placeholder', ['title' => 'Weather Alerts']))->name('weather-alerts');
Route::get('/outages', fn () => view('pages.front.placeholder', ['title' => 'Outages']))->name('outages');
Route::get('/cooling-points', fn () => view('pages.front.placeholder', ['title' => 'Cooling Points']))->name('cooling-points');
Route::get('/advice', fn () => view('pages.front.placeholder', ['title' => 'Advice']))->name('advice');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/my-profile', [ResidentProfileController::class, 'show'])->name('my-profile');
    Route::put('/my-profile', [ResidentProfileController::class, 'update'])->name('my-profile.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('profiles', ProfileController::class);
    Route::resource('equipment', SensitiveEquipmentController::class);
});

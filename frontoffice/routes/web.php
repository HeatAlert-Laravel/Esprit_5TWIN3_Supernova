<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CoolingPointController;
use App\Http\Controllers\ResidentProfileController;
use App\Http\Controllers\ResidentSensitiveEquipmentController;
use App\Http\Controllers\WeatherAlertController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.front.home')->name('home');
Route::get('/weather-alerts', [WeatherAlertController::class, 'index'])->name('weather-alerts');
Route::get('/outages', fn () => view('pages.front.placeholder', ['title' => 'Outages']))->name('outages');
Route::get('/cooling-points', [CoolingPointController::class, 'index'])->name('cooling-points');
Route::get('/advice', fn () => view('pages.front.placeholder', ['title' => 'Advice']))->name('advice');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/my-profile', [ResidentProfileController::class, 'show'])->name('my-profile');
    Route::put('/my-profile', [ResidentProfileController::class, 'update'])->name('my-profile.update');

    Route::prefix('my-profile/equipment')->name('profile.equipment.')->group(function () {
        Route::get('/create', [ResidentSensitiveEquipmentController::class, 'create'])->name('create');
        Route::post('/', [ResidentSensitiveEquipmentController::class, 'store'])->name('store');
        Route::get('/{equipment}/edit', [ResidentSensitiveEquipmentController::class, 'edit'])->name('edit');
        Route::put('/{equipment}', [ResidentSensitiveEquipmentController::class, 'update'])->name('update');
        Route::delete('/{equipment}', [ResidentSensitiveEquipmentController::class, 'destroy'])->name('destroy');
    });
});

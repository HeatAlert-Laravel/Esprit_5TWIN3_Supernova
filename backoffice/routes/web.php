<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AlerteMeteoController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuartierController;
use App\Http\Controllers\Admin\SensitiveEquipmentController;
use App\Http\Controllers\Admin\TypeEquipementController;
use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/login', [AdminAuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login']);
Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('quartiers', QuartierController::class);
    Route::resource('alertes-meteo', AlerteMeteoController::class)->parameters(['alertes-meteo' => 'alerte']);
    Route::resource('profiles', ProfileController::class);
    Route::resource('type-equipements', TypeEquipementController::class)->parameters(['type-equipements' => 'typeEquipement']);
    Route::resource('equipment', SensitiveEquipmentController::class);
});

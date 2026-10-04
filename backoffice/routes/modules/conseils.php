<?php

use App\Http\Controllers\Admin\CategorieConseilController;
use App\Http\Controllers\Admin\ConseilController;
use Illuminate\Support\Facades\Route;

// Included inside the existing authenticated ADMIN group.
Route::resource('categorie-conseils', CategorieConseilController::class)
    ->parameters(['categorie-conseils' => 'categorieConseil']);
Route::patch('conseils/{conseil}/publication', [ConseilController::class, 'publication'])->name('conseils.publication');
Route::resource('conseils', ConseilController::class);

<?php

use App\Http\Controllers\AdviceController;
use Illuminate\Support\Facades\Route;

Route::get('/advice', [AdviceController::class, 'index'])->name('advice');
Route::get('/advice/{conseil}/preview', [AdviceController::class, 'preview'])->middleware('auth')->name('advice.preview');
Route::get('/advice/{conseil}', [AdviceController::class, 'show'])->name('advice.show');

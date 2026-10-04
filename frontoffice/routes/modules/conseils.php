<?php

use App\Http\Controllers\AdviceBookmarkController;
use App\Http\Controllers\AdviceController;
use Illuminate\Support\Facades\Route;

Route::get('/advice', [AdviceController::class, 'index'])->name('advice');
Route::get('/advice/saved', [AdviceController::class, 'index'])->middleware('auth')->name('advice.saved');
Route::get('/advice/{conseil}/save', [AdviceController::class, 'show'])->middleware('auth')->name('advice.save-prompt');
Route::put('/advice/{conseil}/bookmark', [AdviceBookmarkController::class, 'store'])->middleware('auth')->name('advice.bookmark.store');
Route::delete('/advice/{conseil}/bookmark', [AdviceBookmarkController::class, 'destroy'])->middleware('auth')->name('advice.bookmark.destroy');
Route::get('/advice/{conseil}/preview', [AdviceController::class, 'preview'])->middleware('auth')->name('advice.preview');
Route::get('/advice/{conseil}', [AdviceController::class, 'show'])->name('advice.show');

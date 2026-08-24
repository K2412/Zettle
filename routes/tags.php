<?php

use App\Http\Controllers\TagController;
use App\Http\Controllers\TagMergeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::patch('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::post('tags/{tag}/merge', [TagMergeController::class, 'store'])->name('tags.merge');
});

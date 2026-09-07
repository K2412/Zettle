<?php

use App\Http\Controllers\NoteTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('templates', [NoteTemplateController::class, 'index'])->name('templates.index');
    Route::post('templates', [NoteTemplateController::class, 'store'])->name('templates.store');
    Route::patch('templates/{template}', [NoteTemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [NoteTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('templates/{template}/apply', [NoteTemplateController::class, 'apply'])->name('templates.apply');
});

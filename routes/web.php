<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/notes')->name('dashboard');
});

require __DIR__.'/notes.php';
require __DIR__.'/tags.php';
require __DIR__.'/settings.php';

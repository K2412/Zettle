<?php

use App\Http\Controllers\Note\AtomizeController;
use App\Http\Controllers\Note\ConnectionController;
use App\Http\Controllers\Note\FormulateController;
use App\Http\Controllers\Note\NoteTagController;
use App\Http\Controllers\Note\TriageController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NoteDiscoveryController;
use App\Http\Controllers\NoteGraphController;
use App\Http\Controllers\NoteSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('notes/search', NoteSearchController::class)->name('notes.search');
    Route::get('notes/{note:slug}/discover', NoteDiscoveryController::class)->name('notes.discover');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notes', [NoteController::class, 'index'])->name('notes.index');
    Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
    // Registered before the {note:slug} route so the slug binding never captures "graph".
    Route::get('notes/graph', [NoteGraphController::class, 'index'])->name('notes.graph');
    Route::get('notes/{note:slug}', [NoteController::class, 'show'])->name('notes.show');
    Route::patch('notes/{note:slug}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note:slug}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::post('notes/{note:slug}/tags', [NoteTagController::class, 'store'])->name('notes.tags.store');
    Route::delete('notes/{note:slug}/tags/{tag}', [NoteTagController::class, 'destroy'])->name('notes.tags.destroy');

    Route::post('notes/{note:slug}/connections', [ConnectionController::class, 'store'])->name('notes.connections.store');
    Route::patch('notes/{note:slug}/connections/{connection}', [ConnectionController::class, 'update'])->name('notes.connections.update');
    Route::delete('notes/{note:slug}/connections/{connection}', [ConnectionController::class, 'destroy'])->name('notes.connections.destroy');

    // AI generation is non-idempotent and billable, so the read-only lookup rail
    // is a POST (not a prefetchable GET); the write rail rides an Inertia visit.
    Route::post('notes/{note:slug}/assists/atomize', [AtomizeController::class, 'run'])->name('notes.assists.atomize');
    Route::post('notes/{note:slug}/assists/atomize/spawn', [AtomizeController::class, 'spawn'])->name('notes.assists.atomize.spawn');

    Route::post('notes/{note:slug}/assists/triage', [TriageController::class, 'run'])->name('notes.assists.triage');
    Route::post('notes/{note:slug}/assists/triage/apply-type', [TriageController::class, 'applyType'])->name('notes.assists.triage.apply-type');

    Route::post('notes/{note:slug}/assists/formulate/evaluate', [FormulateController::class, 'evaluate'])->name('notes.assists.formulate.evaluate');
});

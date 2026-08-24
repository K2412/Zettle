<?php

use App\Models\Note;
use App\Models\User;

it('lazily loads the CodeMirror editor and edits the body', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Editing', 'slug' => 'editing-a1', 'body' => '']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertPresent('@markdown-editor')
        ->click('.cm-content')
        ->type('.cm-content', 'Hello body');

    $page->assertSee('Hello body');
});

it('autosaves after a pause and moves through saving to saved', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Autosave', 'slug' => 'autosave-b2', 'body' => '']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSee('Saved');

    $page->click('.cm-content')
        ->type('.cm-content', 'A fresh paragraph.');

    // Editing flips to unsaved; the ~1.5s debounce then saves and persists.
    $page->waitForText('Unsaved changes')
        ->wait(2.5);

    expect($note->fresh()->body)->toContain('A fresh paragraph.');
});

it('does not persist while the user is mid-edit before the debounce', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Debounced', 'slug' => 'debounced-b6', 'body' => 'start']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('.cm-content')
        ->type('.cm-content', ' more');

    // Immediately after typing the save has not yet fired.
    $page->waitForText('Unsaved changes');
    expect($note->fresh()->body)->toBe('start');
});

it('autosaves an edited title', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Old title', 'slug' => 'old-title-c3']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->fill('@note-title', 'New title')
        ->waitForText('Unsaved changes')
        ->wait(2.5);

    expect($note->fresh()->title)->toBe('New title');
});

it('switches to the preview tab and renders markdown', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Preview me',
        'slug' => 'preview-me-d4',
        'body' => '# A heading',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@tab-preview');

    $page->assertSee('A heading');
});

it('flushes a pending save when navigating away before the debounce', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Flush me', 'slug' => 'flush-me-e5', 'body' => '']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('.cm-content')
        ->type('.cm-content', 'Typed then left');

    // Navigate immediately, before the 1.5s debounce fires. The pending save is
    // flushed on the way out so the keystrokes are not lost.
    $page->waitForText('Unsaved changes')
        ->click('@all-notes-link')
        ->assertPathIs('/notes');

    $page->wait(1);
    expect($note->fresh()->body)->toContain('Typed then left');
});

it('warns via the native prompt on a hard unload with unsaved changes', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Guarded', 'slug' => 'guarded-e6', 'body' => '']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('.cm-content')
        ->type('.cm-content', 'Half a thought');

    // A beforeunload handler is registered while unsaved so the browser prompts
    // on tab-close/reload. We assert the unsaved state that arms it, since the
    // native prompt itself is outside the page DOM.
    $page->waitForText('Unsaved changes')
        ->assertAttribute('@save-status', 'data-status', 'unsaved');
});

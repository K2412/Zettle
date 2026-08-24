<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('shows an empty state on a fresh account', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/notes');

    $page->assertSee('No notes yet.');
});

it('creates a note with a chosen type and lands in the editor', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/notes');
    $page->fill('title', 'Structure of arguments')
        ->click('Create');

    $page->assertPathBeginsWith('/notes/structure-of-arguments');

    expect($user->notes()->where('title', 'Structure of arguments')->exists())->toBeTrue();
});

it('searches the note list when the Search button is pressed', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Zettelkasten method', 'slug' => 'zettelkasten-method-a1']);
    Note::factory()->for($user)->create(['title' => 'Grocery list', 'slug' => 'grocery-list-b2']);
    $this->actingAs($user);

    $page = visit('/notes');
    $page->assertSee('Grocery list');

    // Typing alone must NOT search — the query only runs on an explicit press.
    $page->type('@search-notes', 'Zettelkasten');
    $page->assertSee('Grocery list');

    $page->click('@search-submit');

    $page->assertSee('Zettelkasten method')
        ->assertDontSee('Grocery list');
});

it('clears back to the full list when an emptied query is submitted', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Zettelkasten method', 'slug' => 'zettelkasten-method-a1']);
    Note::factory()->for($user)->create(['title' => 'Grocery list', 'slug' => 'grocery-list-b2']);
    $this->actingAs($user);

    $page = visit('/notes');
    $page->type('@search-notes', 'Zettelkasten')->click('@search-submit');
    $page->assertDontSee('Grocery list');

    $page->clear('@search-notes')->click('@search-submit');

    $page->assertSee('Zettelkasten method')
        ->assertSee('Grocery list');
});

it('filters the list by tag chip', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'research']);
    $tagged = Note::factory()->for($user)->create(['title' => 'Tagged note', 'slug' => 'tagged-note-c3']);
    $tagged->tags()->attach($tag);
    Note::factory()->for($user)->create(['title' => 'Untagged note', 'slug' => 'untagged-note-d4']);
    $this->actingAs($user);

    $page = visit('/notes');
    $page->assertSee('Untagged note');

    $page->click('research');

    $page->assertSee('Tagged note')
        ->assertDontSee('Untagged note');
});

it('deletes a note behind a confirm dialog', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Disposable', 'slug' => 'disposable-e5']);
    $this->actingAs($user);

    $page = visit('/notes');
    $page->click('@delete-note')
        ->assertSee('This cannot be undone.')
        ->click('@confirm-delete');

    $page->assertDontSee('Disposable');
    expect(Note::query()->whereKey($note->id)->exists())->toBeFalse();
});

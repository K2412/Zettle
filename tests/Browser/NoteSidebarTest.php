<?php

use App\Enums\Relationship;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('attaches an available tag from the sidebar', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'slug' => 'note-a1']);
    $tag = Tag::factory()->for($user)->create(['name' => 'research']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@attach-tag');

    $page->assertPresent('@detach-tag');
    expect($note->fresh()->tags->pluck('name')->all())->toContain('research');
});

it('detaches an attached tag from the sidebar', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'slug' => 'note-b1']);
    $tag = Tag::factory()->for($user)->create(['name' => 'archive']);
    $note->tags()->attach($tag);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSeeIn('@attached-tags', 'archive')
        ->click('@detach-tag');

    $page->wait(0.5);
    expect($note->fresh()->tags)->toHaveCount(0);
});

it('creates and attaches a new tag from the sidebar', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'slug' => 'note-c1']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->fill('name', 'inbox')
        ->click('Add');

    $page->wait(0.5)->assertSeeIn('@attached-tags', 'inbox');
    expect($note->fresh()->tags->pluck('name')->all())->toContain('inbox');
});

it('validates the new tag name', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'slug' => 'note-c2']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('Add');

    // Empty name is rejected; no tag is created.
    $page->wait(0.5);
    expect($note->fresh()->tags)->toHaveCount(0);
});

it('navigates to a backlinked note from the sidebar', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center', 'slug' => 'center-d1']);
    $source = Note::factory()->for($user)->create(['title' => 'Links here', 'slug' => 'links-here-d2']);
    $source->linksTo()->attach($note, ['user_id' => $user->id, 'relationship' => Relationship::Mentions->value]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSeeIn('@backlinks', 'Links here')
        ->click('Links here');

    $page->assertPathBeginsWith('/notes/links-here-d2');
});

it('shows outgoing links that navigate', function () {
    $user = User::factory()->create();
    $target = Note::factory()->for($user)->create(['title' => 'Destination', 'slug' => 'destination-e1']);
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'slug' => 'origin-e2']);
    $note->linksTo()->attach($target, ['user_id' => $user->id, 'relationship' => Relationship::Mentions->value]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSeeIn('@outgoing-links', 'Destination')
        ->click('Destination');

    $page->assertPathBeginsWith('/notes/destination-e1');
});

it('renders the connections panel and the Find connections control beside the assist panel', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'slug' => 'note-f1']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertPresent('@connections-panel')
        ->assertPresent('@find-connections')
        ->assertPresent('@assist-panel');
});

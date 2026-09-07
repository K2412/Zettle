<?php

use App\Models\Note;
use App\Models\User;

it('suggests tags and a hint from the faked SDK without rewriting the origin', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-findable-a1',
        'body' => 'A note that should stay findable.',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->click('@phase-tab-make_findable')
        ->click('@make-findable-run')
        ->assertSee('When would I reach for this idea again?')
        ->assertSee('Reach for this when the claim needs a reminder.')
        ->assertSee('zettelkasten');

    expect($note->fresh()->body)->toBe('A note that should stay findable.')
        ->and($note->fresh()->discovery_hint)->toBeNull();
});

it('attaches a suggested tag and sets the discovery hint on the origin', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-findable-b1',
        'body' => 'seed',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->click('@phase-tab-make_findable')
        ->click('@make-findable-run')
        ->click('@make-findable-attach-tag')
        ->assertSee('Tag attached.')
        ->click('@make-findable-set-hint')
        ->assertSee('Discovery hint saved.');

    expect($note->fresh()->body)->toBe('seed')
        ->and($note->fresh()->discovery_hint)->toBe('Reach for this when the claim needs a reminder.')
        ->and($note->fresh()->tags->pluck('name')->all())->toContain('zettelkasten');
});

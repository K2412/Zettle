<?php

use App\Models\Note;
use App\Models\User;

it('opens a [[ autocomplete of matching notes and inserts the picked title', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Zettelkasten', 'slug' => 'zettelkasten-a1']);
    $note = Note::factory()->for($user)->create(['title' => 'Working note', 'slug' => 'working-note-a2', 'body' => '']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('.cm-content')
        ->type('.cm-content', '[[Zettel');

    // Poll for the async completion popup (CM splits the label into matched /
    // unmatched spans, so a plain text assertion can't see the whole title).
    $page->script(<<<'JS'
        new Promise((resolve, reject) => {
            const started = Date.now();
            const tick = () => {
                const tip = document.querySelector('.cm-tooltip-autocomplete');
                if (tip && tip.textContent.includes('Zettelkasten')) return resolve(true);
                if (Date.now() - started > 4000) return reject('completion popup never showed Zettelkasten');
                setTimeout(tick, 100);
            };
            tick();
        })
    JS);

    // Enter accepts the highlighted completion, inserting [[Title]] at the caret.
    $page->wait(0.3)->keys('.cm-content', ['Enter'])->wait(0.3);

    // CodeMirror splits the line across syntax spans, so read the whole document
    // text rather than a single visible node.
    $inserted = $page->script("document.querySelector('.cm-content').textContent");
    expect($inserted)->toContain('[[Zettelkasten]]');
});

it('excludes the current note from the [[ autocomplete', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Solo topic', 'slug' => 'solo-topic-b1', 'body' => '']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('.cm-content')
        ->type('.cm-content', '[[Solo');

    // Its own title must not be offered as a link target.
    $page->assertDontSee('Solo topic');
});

it('renders a resolved wikilink in preview as a navigable link', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Target note', 'slug' => 'target-note-c1']);
    $note = Note::factory()->for($user)->create([
        'title' => 'Has a link',
        'slug' => 'has-a-link-c2',
        'body' => 'See [[Target note]] for context.',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@tab-preview')
        ->assertPresent('@wikilink-resolved')
        ->click('@wikilink-resolved');

    $page->assertPathBeginsWith('/notes/target-note-c1');
});

it('renders an unresolved wikilink in preview as muted text', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Dangling',
        'slug' => 'dangling-d1',
        'body' => 'Points at [[Nonexistent note]].',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@tab-preview')
        ->assertPresent('@wikilink-unresolved')
        ->assertSee('Nonexistent note');
});

<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('shows an empty state on a fresh account', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/tags');

    $page->assertSee('No tags yet.')
        ->assertSee('Tag a note to grow your first one.');
});

it('lists the user\'s tags with their usage counts, ordered by name', function () {
    $user = User::factory()->create();
    $beta = Tag::factory()->for($user)->create(['name' => 'Beta']);
    Tag::factory()->for($user)->create(['name' => 'Alpha']);
    $beta->notes()->attach(Note::factory()->count(2)->for($user)->create());
    $this->actingAs($user);

    $page = visit('/tags');

    $page->assertPresent('input[value="Alpha"]')
        ->assertSee('· 0 notes')
        ->assertPresent('input[value="Beta"]')
        ->assertSee('· 2 notes');
});

it('reaches the tags page from the sidebar', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/notes');
    $page->click('Tags');

    $page->assertPathIs('/tags')
        ->assertSee('No tags yet.');
});

it('renames a tag inline and sees it persist', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'teh', 'slug' => 'teh', 'color' => '#111111']);
    $this->actingAs($user);

    $page = visit('/tags');
    $page->fill('@tag-name-input', 'the')
        ->click('@tag-save');

    $page->wait(0.5);
    expect($user->tags()->pluck('name', 'slug')->all())->toBe(['the' => 'the']);
});

it('recolors a tag via the color input and stores it lowercased', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml', 'color' => '#111111']);
    $this->actingAs($user);

    $page = visit('/tags');
    $page->fill('@tag-color-input', '#00aaff')
        ->click('@tag-save');

    $page->wait(0.5);
    expect($user->tags()->first()->color)->toBe('#00aaff');
});

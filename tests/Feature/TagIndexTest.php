<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the tags page with the user\'s tags, counts, ordered by name', function () {
    $user = User::factory()->create();
    $beta = Tag::factory()->for($user)->create(['name' => 'Beta']);
    Tag::factory()->for($user)->create(['name' => 'Alpha']);
    $beta->notes()->attach(Note::factory()->count(2)->for($user)->create());

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('tags/index')
            ->has('tags', 2)
            ->where('tags.0.name', 'Alpha')
            ->where('tags.0.notes_count', 0)
            ->where('tags.1.name', 'Beta')
            ->where('tags.1.notes_count', 2)
            // The wire carries only what the page renders — no user_id/slug leak.
            ->has('tags.0', fn (Assert $tag) => $tag
                ->hasAll(['id', 'name', 'color', 'notes_count'])
                ->missing('user_id')
                ->missing('slug')
            )
        );
});

it('never shows another user\'s tags', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'Mine']);
    Tag::factory()->for(User::factory())->create(['name' => 'Theirs']);

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('tags', 1)
            ->where('tags.0.name', 'Mine')
        );
});

it('redirects a guest to login', function () {
    $this->get(route('tags.index'))->assertRedirect(route('login'));
});

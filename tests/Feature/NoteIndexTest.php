<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakeEmbeddings;

it('requires authentication', function () {
    $this->get(route('notes.index'))->assertRedirect(route('login'));
});

it('renders the notes index with the user\'s notes and tags', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->count(3)->create();
    Tag::factory()->for($user)->create();
    Note::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/index')
            ->has('notes.data', 3)
            ->has('tags', 1)
            ->has('filters')
        );
});

it('passes the search and tag filters through to props', function () {
    // A search query now runs hybrid search, which embeds the query — fake the
    // AI SDK so the index renders without touching the network.
    FakeEmbeddings::install();
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('notes.index', ['q' => 'hello', 'tagId' => $tag->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/index')
            ->where('filters.q', 'hello')
            ->where('filters.tagId', $tag->id)
        );
});

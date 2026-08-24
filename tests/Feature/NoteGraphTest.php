<?php

use App\Models\Note;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('does not let a note slugged "graph" shadow the graph page', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Graph', 'slug' => 'graph']);

    $this->actingAs($user)
        ->get('/notes/graph')
        ->assertInertia(fn (Assert $page) => $page->component('notes/graph'));
});

it('renders an empty graph when the user has no notes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notes.graph'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/graph')
            ->has('graph.nodes', 0)
            ->has('graph.edges', 0)
        );
});

it('renders only the acting user\'s notes in the graph', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create();
    Note::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->get(route('notes.graph'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/graph')
            ->has('graph.nodes', 1)
        );
});

it('redirects a guest to login', function () {
    $this->get(route('notes.graph'))->assertRedirect(route('login'));
});

it('renders the graph page with a graph prop of nodes and edges', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('notes.graph'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/graph')
            ->has('graph.nodes')
            ->has('graph.edges')
        );
});

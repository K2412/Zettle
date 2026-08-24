<?php

use App\Models\Note;
use App\Models\User;

it('returns matching notes as id/title/slug for the current user', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Zettelkasten method', 'slug' => 'zettelkasten-method-aaa111']);
    Note::factory()->for($user)->create(['title' => 'Unrelated', 'slug' => 'unrelated-bbb222']);

    $response = $this->actingAs($user)->getJson('/notes/search?query=Zettelkasten');

    $response->assertOk()
        ->assertJsonPath('results.0.title', 'Zettelkasten method')
        ->assertJsonPath('results.0.slug', 'zettelkasten-method-aaa111')
        ->assertJsonCount(1, 'results');
});

it('excludes the note passed as exclude', function () {
    $user = User::factory()->create();
    $current = Note::factory()->for($user)->create(['title' => 'Alpha topic']);
    Note::factory()->for($user)->create(['title' => 'Alpha companion']);

    $response = $this->actingAs($user)->getJson('/notes/search?query=Alpha&exclude='.$current->id);

    $response->assertOk();
    expect(collect($response->json('results'))->pluck('id'))->not->toContain($current->id);
});

it('returns an empty result set for a blank query', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/notes/search?query=')
        ->assertOk()
        ->assertExactJson(['results' => []]);
});

it('never returns another user\'s notes', function () {
    $user = User::factory()->create();
    Note::factory()->for(User::factory())->create(['title' => 'Secret theirs']);

    $this->actingAs($user)->getJson('/notes/search?query=Secret')
        ->assertOk()
        ->assertJsonCount(0, 'results');
});

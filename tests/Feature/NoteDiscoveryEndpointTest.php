<?php

use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteEmbeddingService;
use Tests\Support\FakeEmbeddings;

it('returns ranked discovery suggestions as JSON for the source note', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();

    $source = Note::factory()->for($user)->create(['title' => 'Source', 'body' => 'cats']);
    $near = Note::factory()->for($user)->create(['title' => 'Near', 'body' => 'feline']);

    $fake->map('Source'."\n\n".'cats', [1.0, 0.0])->map('Near'."\n\n".'feline', [0.96, 0.04]);
    $service = app(NoteEmbeddingService::class);
    $service->embed($source);
    $service->embed($near);

    $this->actingAs($user)
        ->getJson(route('notes.discover', $source))
        ->assertOk()
        ->assertJsonPath('suggestions.0.id', $near->id)
        ->assertJsonPath('suggestions.0.title', 'Near')
        ->assertJsonStructure(['suggestions' => [['id', 'title', 'slug', 'snippet', 'similarity']]]);
});

it('authorizes viewing the source note', function () {
    FakeEmbeddings::install();
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->getJson(route('notes.discover', $source))
        ->assertForbidden();
});

it('returns an empty suggestion list when the note has no neighbours', function () {
    FakeEmbeddings::install();
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('notes.discover', $source))
        ->assertOk()
        ->assertExactJson(['suggestions' => []]);
});

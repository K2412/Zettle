<?php

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('updates a note through the service and reconciles wikilinks', function () {
    $user = User::factory()->create();
    $target = Note::factory()->for($user)->create(['title' => 'Target']);
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'body' => '']);

    $this->actingAs($user)
        ->patch(route('notes.update', $note), ['body' => 'Links to [[Target]]'])
        ->assertRedirect();

    expect($note->fresh()->body)->toBe('Links to [[Target]]')
        ->and($note->fresh()->linksTo->pluck('id')->all())->toContain($target->id);
});

it('forbids updating a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->patch(route('notes.update', $note), ['title' => 'Hijacked'])
        ->assertForbidden();
});

it('deletes a note and redirects to the index', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertRedirect(route('notes.index'));

    expect(Note::query()->whereKey($note->id)->exists())->toBeFalse();
});

it('forbids deleting a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->delete(route('notes.destroy', $note))
        ->assertForbidden();

    expect(Note::query()->whereKey($note->id)->exists())->toBeTrue();
});

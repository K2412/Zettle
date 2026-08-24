<?php

use App\Models\Note;
use App\Models\User;
use App\Policies\NotePolicy;

it('lets the owner view, update, and delete their note', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->for($owner)->create();
    $policy = new NotePolicy;

    expect($policy->view($owner, $note))->toBeTrue()
        ->and($policy->update($owner, $note))->toBeTrue()
        ->and($policy->delete($owner, $note))->toBeTrue();
});

it('blocks a non-owner from view, update, and delete', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();
    $policy = new NotePolicy;

    expect($policy->view($intruder, $note))->toBeFalse()
        ->and($policy->update($intruder, $note))->toBeFalse()
        ->and($policy->delete($intruder, $note))->toBeFalse();
});

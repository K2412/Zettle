<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('deletes a tag through the confirm that names how many notes it leaves', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);
    $tag->notes()->attach(Note::factory()->count(3)->for($user)->create());
    $this->actingAs($user);

    $page = visit('/tags');
    $page->assertPresent('input[value="ml"]')
        ->click('@tag-delete')
        ->assertSee("Remove 'ml'?")
        ->assertSee('This removes it from 3 notes.')
        ->click('@tag-delete-confirm');

    $page->wait(0.5);
    expect($user->tags()->count())->toBe(0);
    $page->assertMissing('input[value="ml"]');
});

it('leaves the tag in place when the confirm is cancelled', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'keep', 'slug' => 'keep']);
    $this->actingAs($user);

    $page = visit('/tags');
    $page->click('@tag-delete')
        ->assertSee('Remove')
        ->click('Cancel');

    $page->wait(0.3);
    expect($user->tags()->count())->toBe(1);
    $page->assertPresent('input[value="keep"]');
});

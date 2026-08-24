<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('merges one tag into another through the dialog, dropping the source and growing the target', function () {
    $user = User::factory()->create();
    $source = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);
    $target = Tag::factory()->for($user)->create(['name' => 'reading', 'slug' => 'reading']);

    $source->notes()->attach(Note::factory()->count(2)->for($user)->create());
    $target->notes()->attach(Note::factory()->for($user)->create());
    $this->actingAs($user);

    $page = visit('/tags');
    $page->assertPresent('input[value="ml"]')
        ->click('@tag-merge')
        ->assertSee("Merge 'ml' into…")
        ->click('@tag-merge-target')
        ->click("@tag-merge-option-{$target->id}")
        ->click('@tag-merge-confirm');

    $page->wait(0.5);
    expect($user->tags()->count())->toBe(1)
        ->and($target->fresh()->notes()->count())->toBe(3);
    $page->assertMissing('input[value="ml"]');
});

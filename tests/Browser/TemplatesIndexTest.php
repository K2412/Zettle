<?php

use App\Models\NoteTemplate;
use App\Models\User;

it('reaches the templates page from the sidebar Templates nav item', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/notes');
    $page->assertSee('Templates');

    $page->click('Templates');

    $page->assertPathIs('/templates');
});

it('creates a note from a template', function () {
    $user = User::factory()->create();
    NoteTemplate::factory()->for($user)->create([
        'name' => 'Daily',
        'title_prefix' => 'D: ',
        'body_template' => 'Daily body',
    ]);
    $this->actingAs($user);

    $page = visit('/templates');

    $page->assertSee('Daily')
        ->click('@template-apply')
        ->assertPathBeginsWith('/notes/');
});

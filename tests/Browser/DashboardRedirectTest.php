<?php

use App\Models\User;

it('sends an authenticated visit to /dashboard onto the notes index', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/dashboard');

    $page->assertPathIs('/notes')
        ->assertSee('No notes yet.');
});

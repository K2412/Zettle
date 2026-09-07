<?php

use App\Models\User;

it('reaches the graph page from the sidebar Graph nav item', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/notes');
    $page->assertSee('Graph');

    $page->click('Graph');

    $page->assertPathIs('/notes/graph');
});

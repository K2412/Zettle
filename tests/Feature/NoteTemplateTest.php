<?php

use App\Models\Note;
use App\Models\NoteTemplate;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Queue::fake());

it('renders the templates index', function () {
    $user = User::factory()->create();
    NoteTemplate::factory()->for($user)->create(['name' => 'Fleeting']);

    $this->actingAs($user)
        ->get(route('templates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/Index')
            ->has('templates', 1)
            ->where('templates.0.name', 'Fleeting')
            ->has('tags')
        );
});

it('creates a template with selected tags', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('templates.index'))
        ->post(route('templates.store'), [
            'name' => 'Fleeting',
            'hotkey' => 'ctrl+shift+f',
            'title_prefix' => 'FL: ',
            'body_template' => 'Fleeting body',
            'tag_ids' => [$tag->id],
        ])
        ->assertRedirect(route('templates.index'));

    $template = $user->noteTemplates()->firstOrFail();

    expect($template->name)->toBe('Fleeting')
        ->and($template->hotkey)->toBe('ctrl+shift+f')
        ->and($template->title_prefix)->toBe('FL:')
        ->and($template->body_template)->toBe('Fleeting body')
        ->and($template->tags->pluck('id')->all())->toContain($tag->id);
});

it('updates a template', function () {
    $user = User::factory()->create();
    $template = NoteTemplate::factory()->for($user)->create(['name' => 'Old']);

    $this->actingAs($user)
        ->from(route('templates.index'))
        ->patch(route('templates.update', $template), [
            'name' => 'New',
            'hotkey' => '',
            'title_prefix' => 'N: ',
            'body_template' => 'Updated body',
            'tag_ids' => [],
        ])
        ->assertRedirect(route('templates.index'));

    expect($template->fresh())
        ->name->toBe('New')
        ->hotkey->toBeNull()
        ->body_template->toBe('Updated body');
});

it('applies a template to create a note and redirects to it', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();
    $template = NoteTemplate::factory()->for($user)->create([
        'name' => 'Daily',
        'title_prefix' => 'D: ',
        'body_template' => 'Daily body',
    ]);
    $template->tags()->attach($tag);

    $this->actingAs($user)
        ->post(route('templates.apply', $template))
        ->assertRedirect();

    $note = $user->notes()->where('body', 'Daily body')->first();

    expect($note)->toBeInstanceOf(Note::class)
        ->and($note->title)->toStartWith('D: ')
        ->and($note->tags->pluck('id')->all())->toContain($tag->id);
});

it('deletes a template owned by the user', function () {
    $user = User::factory()->create();
    $template = NoteTemplate::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('templates.index'))
        ->delete(route('templates.destroy', $template))
        ->assertRedirect(route('templates.index'));

    expect(NoteTemplate::query()->whereKey($template->id)->exists())->toBeFalse();
});

it('forbids applying another user\'s template', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $template = NoteTemplate::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->post(route('templates.apply', $template))
        ->assertForbidden();
});

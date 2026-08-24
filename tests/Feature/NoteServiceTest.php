<?php

use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Jobs\EmbedNoteJob;
use App\Models\Connection;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\Note\NoteService;
use Illuminate\Support\Facades\Queue;

it('creates notes with a unique slug', function () {
    $user = User::factory()->create();

    $note = app(NoteService::class)->createForUser(['title' => 'Hello world'], $user);

    expect($note->title)->toBe('Hello world')
        ->and($note->slug)->toMatch('/^hello-world-[A-Za-z0-9]{6}$/')
        ->and($note->user_id)->toBe($user->id);
});

it('defaults new notes to fleeting when no type is given', function () {
    $user = User::factory()->create();

    $note = app(NoteService::class)->createForUser(['title' => 'A raw capture'], $user);

    expect($note->note_type)->toBe(NoteType::Fleeting);
});

it('creates a note with the chosen type', function () {
    $user = User::factory()->create();

    $note = app(NoteService::class)->createForUser(
        ['title' => 'A source claim', 'note_type' => NoteType::Literature],
        $user,
    );

    expect($note->note_type)->toBe(NoteType::Literature);
});

it('reconciles [[wikilinks]] in the body into mentions connections', function () {
    Queue::fake();
    $user = User::factory()->create();
    $target = Note::factory()->for($user)->create(['title' => 'Target']);
    $source = Note::factory()->for($user)->create(['title' => 'Source', 'body' => '']);

    app(NoteService::class)->updateWithLinks(
        $source,
        ['body' => 'Refers to [[Target]] here'],
        $user,
    );

    $connection = Connection::query()->where('source_note_id', $source->id)->first();

    expect($source->fresh()->linksTo->pluck('id')->all())->toContain($target->id)
        ->and($connection->relationship)->toBe(Relationship::Mentions);
});

it('removes a mentions connection when its wikilink is deleted from the body', function () {
    Queue::fake();
    $user = User::factory()->create();
    $target = Note::factory()->for($user)->create(['title' => 'Target']);
    $source = Note::factory()->for($user)->create(['title' => 'Source', 'body' => 'See [[Target]]']);
    $service = app(NoteService::class);
    $service->updateWithLinks($source, ['body' => 'See [[Target]]'], $user);

    $service->updateWithLinks($source, ['body' => 'No links now'], $user);

    expect(Connection::query()->where('source_note_id', $source->id)->count())->toBe(0);
});

it('preserves authored (non-mentions) connections when re-parsing the body', function () {
    Queue::fake();
    $user = User::factory()->create();
    $target = Note::factory()->for($user)->create(['title' => 'Target']);
    $source = Note::factory()->for($user)->create(['title' => 'Source']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
    ]);

    app(NoteService::class)->updateWithLinks($source, ['body' => 'No wikilinks'], $user);

    expect(Connection::query()
        ->where('source_note_id', $source->id)
        ->where('relationship', Relationship::Supports)
        ->exists())->toBeTrue();
});

it('dispatches the embed job when title or body changes', function () {
    Queue::fake();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Original']);

    app(NoteService::class)->updateWithLinks($note, ['body' => 'Fresh content'], $user);

    Queue::assertPushed(EmbedNoteJob::class);
});

it('lists the user\'s notes newest-first, excluding other users', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Older', 'created_at' => now()->subDay()]);
    Note::factory()->for($user)->create(['title' => 'Newer', 'created_at' => now()]);
    Note::factory()->for(User::factory())->create(['title' => 'Theirs']);

    $notes = app(NoteService::class)->listForUser($user);

    expect($notes->total())->toBe(2)
        ->and($notes->first()->title)->toBe('Newer');
});

it('filters the list by tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();
    $tagged = Note::factory()->for($user)->create(['title' => 'Tagged']);
    $tagged->tags()->attach($tag);
    Note::factory()->for($user)->create(['title' => 'Untagged']);

    $notes = app(NoteService::class)->listForUser($user, '', $tag->id);

    expect($notes->pluck('title')->all())->toBe(['Tagged']);
});

it('lists user tags ordered by name with a notes count', function () {
    $user = User::factory()->create();
    $alpha = $user->tags()->create(['name' => 'Alpha', 'slug' => 'alpha', 'color' => '#111111']);
    $user->tags()->create(['name' => 'Beta', 'slug' => 'beta', 'color' => '#222222']);
    Note::factory()->for($user)->create()->tags()->attach($alpha);

    $tags = app(NoteService::class)->tagsForUser($user);

    expect($tags->pluck('name')->all())->toBe(['Alpha', 'Beta'])
        ->and((int) $tags->first()->notes_count)->toBe(1);
});

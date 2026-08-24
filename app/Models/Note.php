<?php

namespace App\Models;

use App\Enums\NoteType;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $body
 * @property NoteType $note_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, Note> $linksTo
 * @property-read Collection<int, Note> $linkedFrom
 */
#[Fillable(['user_id', 'title', 'slug', 'body', 'note_type'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, Searchable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'note_type' => NoteType::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linksTo(): BelongsToMany
    {
        return $this->belongsToMany(
            Note::class,
            'connections',
            'source_note_id',
            'target_note_id'
        )->using(ConnectionPivot::class)->withPivot('relationship', 'rationale')->withTimestamps();
    }

    public function linkedFrom(): BelongsToMany
    {
        return $this->belongsToMany(
            Note::class,
            'connections',
            'target_note_id',
            'source_note_id'
        )->using(ConnectionPivot::class)->withPivot('relationship', 'rationale')->withTimestamps();
    }

    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'source_note_id');
    }

    public function incomingConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'target_note_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }
}

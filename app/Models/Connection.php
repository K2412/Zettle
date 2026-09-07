<?php

namespace App\Models;

use App\Enums\Relationship;
use Database\Factories\ConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $source_note_id
 * @property int $target_note_id
 * @property Relationship $relationship
 * @property string|null $rationale
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'source_note_id', 'target_note_id', 'relationship', 'rationale'])]
class Connection extends Model
{
    /** @use HasFactory<ConnectionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship' => Relationship::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'source_note_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'target_note_id');
    }
}

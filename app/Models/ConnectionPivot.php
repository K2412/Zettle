<?php

namespace App\Models;

use App\Enums\Relationship;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for the note-to-note {@see Connection} relations, so the `relationship`
 * column reads back as a {@see Relationship} enum on every access path — the same
 * cast the Connection model applies — rather than a raw string.
 */
class ConnectionPivot extends Pivot
{
    protected $table = 'connections';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship' => Relationship::class,
        ];
    }
}

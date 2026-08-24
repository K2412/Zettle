<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class MergeTags
{
    use AsAction;

    /**
     * Fold the source tag into the target: every note carrying the source gains
     * the target, then the source is deleted. The target's name and color survive.
     */
    public function handle(Tag $source, Tag $target): Tag
    {
        abort_if($source->is($target), 422, 'A tag cannot be merged into itself.');
        abort_if($source->user_id !== $target->user_id, 403, 'Both tags must belong to the same owner.');

        return DB::transaction(function () use ($source, $target): Tag {
            $target->notes()->syncWithoutDetaching($source->notes()->pluck('notes.id')->all());

            $source->delete();

            return $target;
        });
    }
}

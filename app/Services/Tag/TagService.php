<?php

namespace App\Services\Tag;

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TagService
{
    /**
     * @return Collection<int, Tag>
     */
    public function listForUser(User $user): Collection
    {
        return Tag::query()
            ->select(['id', 'name', 'color'])
            ->where('user_id', $user->id)
            ->withCount('notes')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(array $data, User $user): Tag
    {
        return Tag::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'color' => $data['color'] ?? $this->randomColor(),
        ]);
    }

    /**
     * @param  array{name: string, color: string}  $data
     */
    public function update(Tag $tag, array $data): Tag
    {
        $tag->name = $data['name'];
        $tag->slug = Str::slug($data['name']);
        $tag->color = Str::lower($data['color']);
        $tag->save();

        return $tag;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAndAttachToNote(array $data, Note $note, User $user): Tag
    {
        $tag = Tag::query()->firstOrCreate(
            ['user_id' => $user->id, 'slug' => Str::slug($data['name'])],
            ['name' => $data['name'], 'color' => $this->randomColor()]
        );

        $note->tags()->syncWithoutDetaching([$tag->id]);

        return $tag;
    }

    public function randomColor(): string
    {
        $colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#3b82f6', '#8b5cf6', '#ec4899'];

        return $colors[array_rand($colors)];
    }
}

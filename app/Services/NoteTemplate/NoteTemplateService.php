<?php

namespace App\Services\NoteTemplate;

use App\Models\Note;
use App\Models\NoteTemplate;
use App\Models\User;
use App\Services\Note\NoteService;
use Illuminate\Support\Collection;

class NoteTemplateService
{
    public function __construct(public NoteService $noteService) {}

    public function listForUser(User $user): Collection
    {
        return NoteTemplate::query()
            ->where('user_id', $user->id)
            ->with('tags')
            ->orderBy('name')
            ->get();
    }

    public function createForUser(array $data, User $user): NoteTemplate
    {
        $template = NoteTemplate::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'hotkey' => $data['hotkey'] ?? null,
            'title_prefix' => $data['title_prefix'] ?? '',
            'body_template' => $data['body_template'] ?? '',
        ]);

        if (! empty($data['tag_ids'])) {
            $template->tags()->sync($data['tag_ids']);
        }

        return $template->load('tags');
    }

    public function updateTemplate(NoteTemplate $template, array $data): NoteTemplate
    {
        $template->update([
            'name' => $data['name'],
            'hotkey' => $data['hotkey'] ?? null,
            'title_prefix' => $data['title_prefix'] ?? '',
            'body_template' => $data['body_template'] ?? '',
        ]);

        if (array_key_exists('tag_ids', $data)) {
            $template->tags()->sync($data['tag_ids'] ?? []);
        }

        return $template->load('tags');
    }

    public function applyTemplate(NoteTemplate $template, User $user): Note
    {
        $prefix = $template->title_prefix;
        if ($prefix !== '' && ! str_ends_with($prefix, ' ')) {
            $prefix .= ' ';
        }

        $title = $prefix.now()->format('Y-m-d H:i');

        $note = $this->noteService->createForUser(['title' => $title], $user);

        $note->update(['body' => $template->body_template]);

        $tagIds = $template->loadMissing('tags')->tags->pluck('id');
        if ($tagIds->isNotEmpty()) {
            $note->tags()->sync($tagIds);
        }

        return $note;
    }
}

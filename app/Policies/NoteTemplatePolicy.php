<?php

namespace App\Policies;

use App\Models\NoteTemplate;
use App\Models\User;

class NoteTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, NoteTemplate $noteTemplate): bool
    {
        return $user->id === $noteTemplate->user_id;
    }

    public function update(User $user, NoteTemplate $noteTemplate): bool
    {
        return $user->id === $noteTemplate->user_id;
    }

    public function delete(User $user, NoteTemplate $noteTemplate): bool
    {
        return $user->id === $noteTemplate->user_id;
    }

    public function apply(User $user, NoteTemplate $noteTemplate): bool
    {
        return $user->id === $noteTemplate->user_id;
    }
}

<?php

namespace App\Actions\NoteTemplate;

use App\Models\Note;
use App\Models\NoteTemplate;
use App\Models\User;
use App\Services\NoteTemplate\NoteTemplateService;
use Lorisleiva\Actions\Concerns\AsAction;

class ApplyTemplate
{
    use AsAction;

    public function __construct(public NoteTemplateService $templates) {}

    public function handle(NoteTemplate $template, User $user): Note
    {
        return $this->templates->applyTemplate($template, $user);
    }
}

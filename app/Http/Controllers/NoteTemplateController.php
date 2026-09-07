<?php

namespace App\Http\Controllers;

use App\Actions\NoteTemplate\ApplyTemplate;
use App\Http\Requests\StoreNoteTemplateRequest;
use App\Http\Requests\UpdateNoteTemplateRequest;
use App\Models\NoteTemplate;
use App\Services\NoteTemplate\NoteTemplateService;
use App\Services\Tag\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteTemplateController extends Controller
{
    public function __construct(
        private NoteTemplateService $templates,
        private TagService $tags,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', NoteTemplate::class);

        return Inertia::render('templates/Index', [
            'templates' => $this->templates->listForUser($request->user()),
            'tags' => $this->tags->listForUser($request->user()),
        ]);
    }

    public function store(StoreNoteTemplateRequest $request): RedirectResponse
    {
        $this->templates->createForUser($request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template created.')]);

        return back();
    }

    public function update(UpdateNoteTemplateRequest $request, NoteTemplate $template): RedirectResponse
    {
        $this->templates->updateTemplate($template, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template updated.')]);

        return back();
    }

    public function destroy(NoteTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template deleted.')]);

        return back();
    }

    public function apply(NoteTemplate $template, ApplyTemplate $action): RedirectResponse
    {
        $this->authorize('apply', $template);

        $note = $action->handle($template, request()->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note created from template.')]);

        return to_route('notes.show', $note);
    }
}

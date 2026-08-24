<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Services\Note\NoteGraphService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteGraphController extends Controller
{
    public function __construct(private NoteGraphService $graph) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Note::class);

        return Inertia::render('notes/graph', [
            'graph' => $this->graph->buildGraphData($request->user()),
        ]);
    }
}

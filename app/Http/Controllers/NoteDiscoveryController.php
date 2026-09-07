<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Services\Note\NoteConnectionDiscoveryService;
use Illuminate\Http\JsonResponse;

/**
 * Serves note discovery suggestions on demand (fetched when the Find
 * connections modal opens — not a show-page prop, since discovery is an
 * explicit action). Authorizes viewing the source note before ranking.
 */
class NoteDiscoveryController extends Controller
{
    public function __construct(private NoteConnectionDiscoveryService $discovery) {}

    public function __invoke(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        return response()->json([
            'suggestions' => $this->discovery->discover($note),
        ]);
    }
}

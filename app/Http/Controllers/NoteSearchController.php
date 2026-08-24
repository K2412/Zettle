<?php

namespace App\Http\Controllers;

use App\Services\Note\NoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteSearchController extends Controller
{
    public function __construct(private NoteService $notes) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = (string) $request->string('query');
        $exclude = $request->integer('exclude') ?: null;

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $results = $this->notes->searchForUser($request->user(), $query, $exclude);

        return response()->json(['results' => $results]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Tag\MergeTags;
use App\Http\Requests\MergeTagsRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TagMergeController extends Controller
{
    public function store(MergeTagsRequest $request, Tag $tag): RedirectResponse
    {
        $target = Tag::query()->findOrFail($request->integer('target_tag_id'));

        MergeTags::run($tag, $target);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tags merged.')]);

        return to_route('tags.index');
    }
}

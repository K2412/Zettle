<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tag'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-f]{6}$/i'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('name')) {
                    return;
                }

                /** @var Tag $tag */
                $tag = $this->route('tag');
                $slug = Str::slug($this->string('name')->toString());

                $collides = Tag::query()
                    ->where('user_id', $this->user()->id)
                    ->where('slug', $slug)
                    ->whereKeyNot($tag->getKey())
                    ->exists();

                if ($collides) {
                    $validator->errors()->add(
                        'name',
                        'You already have a tag named that — merge them instead.',
                    );
                }
            },
        ];
    }
}

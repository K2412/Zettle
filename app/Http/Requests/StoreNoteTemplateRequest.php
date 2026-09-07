<?php

namespace App\Http\Requests;

use App\Models\NoteTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', NoteTemplate::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('note_templates', 'name')->where('user_id', $userId),
            ],
            'hotkey' => [
                'nullable',
                'string',
                'min:1',
                'max:50',
                'regex:/^(alt\+)?(ctrl\+)?(shift\+)?[a-z0-9]$/',
                Rule::unique('note_templates', 'hotkey')->where('user_id', $userId),
            ],
            'title_prefix' => ['nullable', 'string', 'max:100'],
            'body_template' => ['nullable', 'string', 'max:10000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')->where('user_id', $userId)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('hotkey') === '') {
            $this->merge(['hotkey' => null]);
        }
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNoteTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('template'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;
        $templateId = $this->route('template')->id;

        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('note_templates', 'name')->where('user_id', $userId)->ignore($templateId),
            ],
            'hotkey' => [
                'nullable',
                'string',
                'min:1',
                'max:50',
                'regex:/^(alt\+)?(ctrl\+)?(shift\+)?[a-z0-9]$/',
                Rule::unique('note_templates', 'hotkey')->where('user_id', $userId)->ignore($templateId),
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

        if (! $this->exists('tag_ids')) {
            $this->merge(['tag_ids' => []]);
        }
    }
}

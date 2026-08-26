<?php

namespace App\Http\Requests;

use App\Enums\NoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ApplyTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('note'));
    }

    /**
     * Applying a triage suggestion sets the note's type. It must be a valid
     * NoteType — the only note field this write touches (ADR-0005).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'note_type' => ['required', new Enum(NoteType::class)],
        ];
    }
}

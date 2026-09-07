<?php

namespace App\Http\Requests;

use App\Enums\Relationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('note'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'relationship' => [
                'required',
                (new Enum(Relationship::class))->only(Relationship::authored()),
            ],
            'rationale' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

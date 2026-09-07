<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpawnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('note'));
    }

    /**
     * A spawn accepts a non-empty list of accepted candidate titles. `required`
     * on each string rejects blank and whitespace-only titles — it trims before
     * the presence check — so nothing empty ever reaches the spawner.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'titles' => ['required', 'array', 'min:1'],
            'titles.*' => ['required', 'string'],
        ];
    }
}

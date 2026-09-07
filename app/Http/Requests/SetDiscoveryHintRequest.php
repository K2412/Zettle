<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetDiscoveryHintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('note'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'discovery_hint' => ['required', 'string', 'max:500'],
        ];
    }
}

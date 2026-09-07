<?php

namespace Database\Factories;

use App\Models\NoteTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteTemplate>
 */
class NoteTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'hotkey' => null,
            'title_prefix' => '',
            'body_template' => '',
        ];
    }

    public function fleeting(): static
    {
        return $this->state(fn () => [
            'name' => 'Fleeting Note',
            'hotkey' => 'ctrl+shift+f',
            'title_prefix' => 'FL: ',
            'body_template' => "## Thought\n\n## Source\n\n## Related",
        ]);
    }

    public function literature(): static
    {
        return $this->state(fn () => [
            'name' => 'Literature Note',
            'hotkey' => 'ctrl+shift+l',
            'title_prefix' => 'LIT: ',
            'body_template' => "## Reference\n\n## Key Ideas\n\n## Quotes\n\n## My Thoughts",
        ]);
    }

    public function permanent(): static
    {
        return $this->state(fn () => [
            'name' => 'Permanent Note',
            'hotkey' => 'ctrl+shift+p',
            'title_prefix' => 'PN: ',
            'body_template' => "## Claim\n\n## Evidence\n\n## Connections",
        ]);
    }
}

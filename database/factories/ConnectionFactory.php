<?php

namespace Database\Factories;

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Connection>
 */
class ConnectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_note_id' => Note::factory(),
            'target_note_id' => Note::factory(),
            'relationship' => Relationship::Supports,
            'rationale' => null,
        ];
    }
}

<?php

use App\Enums\NoteType;

it('has the five zettelkasten note types', function () {
    expect(NoteType::Fleeting->value)->toBe('fleeting')
        ->and(NoteType::Literature->value)->toBe('literature')
        ->and(NoteType::Permanent->value)->toBe('permanent')
        ->and(NoteType::Structure->value)->toBe('structure')
        ->and(NoteType::Project->value)->toBe('project')
        ->and(NoteType::cases())->toHaveCount(5);
});

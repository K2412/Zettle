<?php

use App\Enums\Relationship;

it('returns the forward display label per type', function (Relationship $rel, string $label) {
    expect($rel->label())->toBe($label);
})->with([
    'supports' => [Relationship::Supports, 'supports'],
    'depends_on' => [Relationship::DependsOn, 'depends on'],
    'is_explained_by' => [Relationship::IsExplainedBy, 'is explained by'],
    'example_of' => [Relationship::ExampleOf, 'example of'],
]);

it('returns the right inverse label per directed type', function (Relationship $rel, string $inverse) {
    expect($rel->inverseLabel())->toBe($inverse);
})->with([
    // Spelled out explicitly in the epic / ADR.
    'supports → supported by' => [Relationship::Supports, 'supported by'],
    'depends_on → required by' => [Relationship::DependsOn, 'required by'],
    'explains → is explained by' => [Relationship::Explains, 'is explained by'],
    'example_of → has example' => [Relationship::ExampleOf, 'has example'],
]);

it('reads symmetric types the same both ways', function (Relationship $rel) {
    expect($rel->isSymmetric())->toBeTrue()
        ->and($rel->inverseLabel())->toBe($rel->label());
})->with([
    'contradicts' => [Relationship::Contradicts],
    'analogous_to' => [Relationship::AnalogousTo],
    'tension_with' => [Relationship::TensionWith],
    'distinguishes_from' => [Relationship::DistinguishesFrom],
]);

it('marks only the four symmetric types as symmetric', function () {
    $symmetric = collect(Relationship::cases())
        ->filter(fn (Relationship $r) => $r->isSymmetric())
        ->map(fn (Relationship $r) => $r->value)
        ->values()
        ->all();

    expect($symmetric)->toEqualCanonicalizing([
        'contradicts', 'analogous_to', 'tension_with', 'distinguishes_from',
    ]);
});

it('excludes system types from the authored set', function () {
    $authored = collect(Relationship::authored())->map(fn (Relationship $r) => $r->value)->all();

    expect($authored)->toHaveCount(16)
        ->and($authored)->not->toContain('mentions')
        ->and($authored)->not->toContain('provenance');
});

it('groups authored types into Evidential / Structural / Dialectical kinds', function (string $group, array $expected) {
    $inGroup = collect(Relationship::authored())
        ->filter(fn (Relationship $r) => $r->group() === $group)
        ->map(fn (Relationship $r) => $r->value)
        ->values()
        ->all();

    expect($inGroup)->toEqualCanonicalizing($expected);
})->with([
    'Evidential' => ['Evidential', ['supports', 'contradicts', 'evidence_for', 'counterexample_to', 'example_of']],
    'Structural' => ['Structural', ['extends', 'explains', 'is_explained_by', 'depends_on', 'synthesizes', 'analogous_to']],
    'Dialectical' => ['Dialectical', ['qualifies', 'corrects', 'tension_with', 'raises_question_about', 'distinguishes_from']],
]);

it('exposes the authored vocabulary as plain grouped option data for the client', function () {
    $options = Relationship::options();

    // Shape: a list of groups, each { group, options: [{ value, label }] }.
    expect($options)->toHaveCount(3);

    $groups = collect($options)->pluck('group')->all();
    expect($groups)->toBe(['Evidential', 'Structural', 'Dialectical']);

    $flat = collect($options)->flatMap(fn (array $g) => $g['options'])->all();
    expect($flat)->toHaveCount(16);

    $supports = collect($flat)->firstWhere('value', 'supports');
    expect($supports)->toBe(['value' => 'supports', 'label' => 'supports']);

    // System types never leak into the serialized vocabulary.
    expect(collect($flat)->pluck('value')->all())
        ->not->toContain('mentions')
        ->not->toContain('provenance');
});

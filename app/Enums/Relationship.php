<?php

namespace App\Enums;

enum Relationship: string
{
    // Manual synthesis vocabulary (Phase 5 of the playbook).
    case Supports = 'supports';
    case Contradicts = 'contradicts';
    case Qualifies = 'qualifies';
    case Corrects = 'corrects';
    case Extends = 'extends';
    case Explains = 'explains';
    case IsExplainedBy = 'is_explained_by';
    case EvidenceFor = 'evidence_for';
    case CounterexampleTo = 'counterexample_to';
    case ExampleOf = 'example_of';
    case AnalogousTo = 'analogous_to';
    case DependsOn = 'depends_on';
    case TensionWith = 'tension_with';
    case RaisesQuestionAbout = 'raises_question_about';
    case DistinguishesFrom = 'distinguishes_from';
    case Synthesizes = 'synthesizes';

    // System-derived relationships.
    case Provenance = 'provenance';
    case Mentions = 'mentions';

    /**
     * The forward display phrase — how the note that asserted the edge reads it
     * (source → target).
     */
    public function label(): string
    {
        return self::meta()[$this->value]['label'];
    }

    /**
     * The display-only inverse phrase — how the target note reads an inbound edge
     * (ADR-0002: the edge is stored once, this is computed, never a second row).
     * Symmetric relationships read the same both ways.
     */
    public function inverseLabel(): string
    {
        return self::meta()[$this->value]['inverse'];
    }

    /**
     * Symmetric relationships read the same from either note (ADR-0002).
     */
    public function isSymmetric(): bool
    {
        return self::meta()[$this->value]['symmetric'];
    }

    /**
     * The kind bucket a relationship belongs to: Evidential, Structural, or
     * Dialectical. System types have no authoring kind.
     */
    public function group(): ?string
    {
        return self::meta()[$this->value]['kind'];
    }

    /**
     * Per-relationship display + classification in one place — a new relationship
     * is one case above plus one row here, not four `match` arms to keep in step.
     * Symmetric rows carry inverse == label.
     *
     * @return array<string, array{label: string, inverse: string, kind: ?string, symmetric: bool}>
     */
    private static function meta(): array
    {
        return [
            'supports' => ['label' => 'supports', 'inverse' => 'supported by', 'kind' => 'Evidential', 'symmetric' => false],
            'contradicts' => ['label' => 'contradicts', 'inverse' => 'contradicts', 'kind' => 'Evidential', 'symmetric' => true],
            'qualifies' => ['label' => 'qualifies', 'inverse' => 'qualified by', 'kind' => 'Dialectical', 'symmetric' => false],
            'corrects' => ['label' => 'corrects', 'inverse' => 'corrected by', 'kind' => 'Dialectical', 'symmetric' => false],
            'extends' => ['label' => 'extends', 'inverse' => 'extended by', 'kind' => 'Structural', 'symmetric' => false],
            'explains' => ['label' => 'explains', 'inverse' => 'is explained by', 'kind' => 'Structural', 'symmetric' => false],
            'is_explained_by' => ['label' => 'is explained by', 'inverse' => 'explains', 'kind' => 'Structural', 'symmetric' => false],
            'evidence_for' => ['label' => 'evidence for', 'inverse' => 'has evidence', 'kind' => 'Evidential', 'symmetric' => false],
            'counterexample_to' => ['label' => 'counterexample to', 'inverse' => 'has counterexample', 'kind' => 'Evidential', 'symmetric' => false],
            'example_of' => ['label' => 'example of', 'inverse' => 'has example', 'kind' => 'Evidential', 'symmetric' => false],
            'analogous_to' => ['label' => 'analogous to', 'inverse' => 'analogous to', 'kind' => 'Structural', 'symmetric' => true],
            'depends_on' => ['label' => 'depends on', 'inverse' => 'required by', 'kind' => 'Structural', 'symmetric' => false],
            'tension_with' => ['label' => 'in tension with', 'inverse' => 'in tension with', 'kind' => 'Dialectical', 'symmetric' => true],
            'raises_question_about' => ['label' => 'raises question about', 'inverse' => 'questioned by', 'kind' => 'Dialectical', 'symmetric' => false],
            'distinguishes_from' => ['label' => 'distinguishes from', 'inverse' => 'distinguishes from', 'kind' => 'Dialectical', 'symmetric' => true],
            'synthesizes' => ['label' => 'synthesizes', 'inverse' => 'synthesized by', 'kind' => 'Structural', 'symmetric' => false],
            'provenance' => ['label' => 'provenance', 'inverse' => 'provenance', 'kind' => null, 'symmetric' => false],
            'mentions' => ['label' => 'mentions', 'inverse' => 'mentions', 'kind' => null, 'symmetric' => false],
        ];
    }

    /**
     * The hand-authored vocabulary — every case except the system-derived
     * `mentions` and `provenance`.
     *
     * @return list<self>
     */
    public static function authored(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $r) => $r->group() !== null,
        ));
    }

    /**
     * The authored vocabulary as plain grouped option data — the ONE source of
     * truth the client picker and display labels serialize from, so the
     * relationships are never re-hardcoded in TypeScript.
     *
     * @return list<array{group: string, options: list<array{value: string, label: string}>}>
     */
    public static function options(): array
    {
        $order = ['Evidential', 'Structural', 'Dialectical'];

        return array_map(fn (string $group) => [
            'group' => $group,
            'options' => array_values(array_map(
                fn (self $r) => ['value' => $r->value, 'label' => $r->label()],
                array_filter(self::authored(), fn (self $r) => $r->group() === $group),
            )),
        ], $order);
    }
}

<?php

use App\Enums\Phase;

it('has the seven playbook phases', function () {
    expect(Phase::cases())->toHaveCount(7)
        ->and(Phase::Triage->value)->toBe('triage')
        ->and(Phase::Atomize->value)->toBe('atomize')
        ->and(Phase::Formulate->value)->toBe('formulate')
        ->and(Phase::Connect->value)->toBe('connect')
        ->and(Phase::MakeFindable->value)->toBe('make_findable')
        ->and(Phase::Structure->value)->toBe('structure')
        ->and(Phase::ClusterProject->value)->toBe('cluster_project');
});

it('labels each phase with its human string', function () {
    expect(Phase::Triage->label())->toBe('Triage')
        ->and(Phase::Atomize->label())->toBe('Atomize')
        ->and(Phase::Formulate->label())->toBe('Formulate')
        ->and(Phase::Connect->label())->toBe('Connect')
        ->and(Phase::MakeFindable->label())->toBe('Make findable')
        ->and(Phase::Structure->label())->toBe('Structure')
        ->and(Phase::ClusterProject->label())->toBe('Cluster to project');
});

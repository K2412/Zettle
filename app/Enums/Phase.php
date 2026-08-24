<?php

namespace App\Enums;

/**
 * The on-demand assists the agent can run on a note, one per playbook phase.
 */
enum Phase: string
{
    case Triage = 'triage';
    case Atomize = 'atomize';
    case Formulate = 'formulate';
    case Connect = 'connect';
    case MakeFindable = 'make_findable';
    case Structure = 'structure';
    case ClusterProject = 'cluster_project';

    public function label(): string
    {
        return match ($this) {
            self::Triage => 'Triage',
            self::Atomize => 'Atomize',
            self::Formulate => 'Formulate',
            self::Connect => 'Connect',
            self::MakeFindable => 'Make findable',
            self::Structure => 'Structure',
            self::ClusterProject => 'Cluster to project',
        };
    }
}

<?php

namespace App\Enums;

/**
 * Where a note should go after Phase 1 triage (playbook §Phase 1).
 */
enum TriageDestination: string
{
    case Discard = 'discard';
    case Task = 'task';
    case ProjectOnly = 'project_only';
    case KeepLiterature = 'keep_literature';
    case Develop = 'develop';
    case Question = 'question';
    case MultiIdea = 'multi_idea';
}

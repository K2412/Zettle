<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Base class for every note-synthesis assist agent.
 *
 * All assists resolve their agent through the container (via {@see Promptable::make()})
 * and go through Anthropic with the configured synthesis model. Because the agent is a
 * container-resolved collaborator invoked only through {@see Promptable::prompt()}, tests
 * bind a fake with `AtomizeAgent::fake([...])` and no HTTP call is ever made.
 *
 * ADR-0005: assists are read-only to note content. Concrete agents emit suggestions
 * (structured data or prose) — they never receive a path to write a note's title/body.
 */
abstract class AssistAgent implements Agent
{
    use Promptable;

    protected function provider(): Lab
    {
        return Lab::Anthropic;
    }

    protected function model(): string
    {
        return (string) config('ai.zettle.synthesis.model', 'claude-sonnet-4-6');
    }
}

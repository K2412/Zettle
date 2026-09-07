<?php

namespace App\Providers;

use App\Ai\Agents\AtomizeAgent;
use App\Ai\Agents\ClusterProjectAgent;
use App\Ai\Agents\ConnectAgent;
use App\Ai\Agents\FormulateAgent;
use App\Ai\Agents\MakeFindableAgent;
use App\Ai\Agents\NoteTagSuggester;
use App\Ai\Agents\StructureAgent;
use App\Ai\Agents\TriageAgent;
use Illuminate\Support\ServiceProvider;

/**
 * Without a real Anthropic key the app can't call the synthesis API, so outside
 * production this provider installs a deterministic fake for every assist agent.
 * That keeps local dev and browser tests — which run in a separate server process
 * the in-test `Agent::fake()` helper can't reach — network-free and deterministic.
 * A configured key leaves the real API in place.
 */
class AssistsFakeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Allowlist, not denylist: only local dev and the test suite ever get the
        // fakes. A keyless staging/preview then fails loud on a real API call
        // instead of silently serving canned suggestions.
        if (! $this->app->environment('local', 'testing')) {
            return;
        }

        if (! empty(config('ai.providers.anthropic.key'))) {
            return;
        }

        foreach ($this->fakes() as $agent => $responses) {
            $agent::fake($responses);
        }
    }

    /**
     * The deterministic fake payload for each assist agent. Structured agents get
     * an array of structured responses; the prose FormulateAgent gets a flat array
     * of strings.
     *
     * @return array<class-string, array<int, mixed>>
     */
    private function fakes(): array
    {
        return [
            AtomizeAgent::class => [
                [
                    'ideas' => [
                        [
                            'title' => 'This note holds a first distinct idea',
                            'rationale' => 'It stands as its own claim.',
                        ],
                        [
                            'title' => 'This note holds a second distinct idea',
                            'rationale' => 'It could link elsewhere on its own.',
                        ],
                    ],
                ],
            ],
            TriageAgent::class => [
                [
                    'destination' => 'develop',
                    'note_type' => 'permanent',
                    'reasoning' => 'It extends an idea you hold and deserves a permanent note.',
                ],
            ],
            FormulateAgent::class => [
                'Your title states a topic, not a claim. Consider stating what is true about it. '.
                'Define any pronoun or term a first-time reader would not resolve, and make the '.
                'scope of the claim visible.',
            ],
            ConnectAgent::class => [
                [
                    'suggestions' => [
                        [
                            'note_id' => 0,
                            'relationship' => 'supports',
                            'rationale' => 'Supports the neighbour by sharing the same claim.',
                        ],
                    ],
                ],
            ],
            MakeFindableAgent::class => [
                [
                    'retrieval_contexts' => [
                        'When would I reach for this idea again?',
                        'What question does this note answer?',
                    ],
                    'discovery_hint' => 'Reach for this when the claim needs a reminder.',
                ],
            ],
            NoteTagSuggester::class => [
                ['tags' => ['zettelkasten', 'permanent-notes']],
            ],
            StructureAgent::class => [
                [
                    'central_question' => 'What argument do these notes form together?',
                    'scaffold' => "## Core\n- Related notes belong here.\n\n## Open questions\n- What is still unresolved?",
                    'index_entry' => '',
                ],
            ],
            ClusterProjectAgent::class => [
                [
                    'assessment' => 'The cluster is dense enough to harvest into a project.',
                    'suggested_order' => [],
                    'gaps' => ['A bridging note is still missing.'],
                ],
            ],
        ];
    }
}

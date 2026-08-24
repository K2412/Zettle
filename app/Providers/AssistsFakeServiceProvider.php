<?php

namespace App\Providers;

use App\Ai\Agents\AtomizeAgent;
use Illuminate\Support\ServiceProvider;

/**
 * Without a real Anthropic key the app can't call the synthesis API, so outside
 * production this provider installs a deterministic AtomizeAgent fake. That keeps
 * local dev and browser tests — which run in a separate server process the in-test
 * AtomizeAgent::fake() helper can't reach — network-free and deterministic. A
 * configured key leaves the real API in place.
 */
class AssistsFakeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Allowlist, not denylist: only local dev and the test suite ever get the
        // fake. A keyless staging/preview then fails loud on a real API call
        // instead of silently serving canned ideas.
        if (! $this->app->environment('local', 'testing')) {
            return;
        }

        if (! empty(config('ai.providers.anthropic.key'))) {
            return;
        }

        AtomizeAgent::fake([
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
        ]);
    }
}

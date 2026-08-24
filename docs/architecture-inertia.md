# Full-Stack Architecture Prompt — Laravel + Inertia v3 + React 19

Use this as a system/context prompt when asking an LLM to build features in this stack. The backend rules are grounded in the Laravel Boost guidelines shipped with this repo (`.claude/skills/laravel-best-practices`, `inertia-react-development`, `wayfinder-development`); the React rules in Vercel's React performance guide.

---

## Stack Overview

- **Backend**: Laravel 13 on PHP 8.3+ with thin controllers, domain service classes, and Laravel Actions (`lorisleiva/laravel-actions`) for bespoke operations. Validation + authorization live in Form Requests and policies. Tests are Pest 5. Confirm installed major versions (`composer show --direct`, `package.json`) before relying on any package API — don't assume.
- **Full-stack bridge**: Inertia v3 — no REST API for first-party screens, no client-side router of your own. Laravel owns routing; controllers return `Inertia::render('page', $props)` and Inertia serializes those props across the wire to a React page component. It's a classic server-driven app that renders as a single-page app.
- **Client-side interactivity**: React 19 (`@inertiajs/react`) with TypeScript. Presentational, ephemeral state (dropdowns, modals, tabs, hover, drag) lives in React (`useState`/`useReducer`/refs). Server-owned state arrives as Inertia props and is mutated by visiting a route, never by mutating props in place.
- **Typed routes**: [Laravel Wayfinder](https://github.com/laravel/wayfinder) generates TypeScript functions for named routes and controller actions. Import route helpers from `@/routes/*` and controller actions from `@/actions/*` — never hand-write URL strings.
- **UI kit**: shadcn/ui (Radix primitives + `class-variance-authority`) under `resources/js/components/ui`, styled with Tailwind CSS v4. Icons from `lucide-react`.
- **Build**: Vite 8 with `@vitejs/plugin-react`. **The React Compiler is enabled** (`babel-plugin-react-compiler`) — see the memoization note below, it changes the rules.

### Why this doc is strict

Inertia enforces a **physical server/client boundary**. The backend is PHP, the frontend is React, and only serialized props cross the seam. That wall gives you a lot for free — server state can't be silently mutated on the client, and there's a single obvious place (the controller) where a page's data is assembled.

But the wall also creates its own failure modes, and this doc is a **mandate, not a preference** about them:

1. **The controller is the entry point** — the "thin controller" discipline is the whole game on the backend. Business logic that creeps into the controller is business logic nobody can reuse from a job, command, or second screen.
2. **Every prop is a serialized payload** that ships to the browser, is embedded in the HTML on first load, and re-ships on every partial reload. Over-fetching props is a performance tax *and* a data-leak risk — the client can read every prop you send. Prop hygiene is not optional.
3. **React owns a whole second state model** on the far side of the wire. Confusing "server state that happens to be in a prop" with "client state that belongs in `useState`" is the main source of bugs — stale UIs, double sources of truth, and effects that fight the server.

Validate at the boundary (FSE-015: reject or normalize invalid input at controlled entry points), keep domain logic behind seams you can substitute and test (FSE-013), and decompose by domain capability, not by technical layer (FSA-007). The structure below is how those hold in an Inertia app.

---

## Backend Rules

The backend layering is a clean Laravel app — services, Actions, Form Requests, policies, and the service-vs-action decision are all unchanged from any other Laravel frontend. What's specific to Inertia is only the **entry point**: a controller action that returns `Inertia::render(...)` instead of a Blade view or a JSON resource.

### Thin controllers (the entry point)

A controller action is your routing entry point. Treat it like a thin dispatcher: authorize, gather view data (delegated to services/computed sources), and hand off. An action should rarely exceed ~10 lines — a delegate plus a `render`/`redirect`.

**Conventions:**

- **Naming mirrors the resource, resourceful verbs.** `ProfileController@edit`, `ProfileController@update`, `ProjectController@index/show/create/store`.
- **No business logic in the controller.** No multi-step orchestration, no complex queries inline, no business rules. Those live in services and actions.
- **Reads delegate to a service; writes delegate to a service or action.** The controller assembles the prop bag and returns it.
- **Inject dependencies, don't resolve them.** Constructor-inject services used across several actions; method-inject the ones a single action needs (as in `store` below). Never reach for `app(...)`/`resolve(...)` inside the controller body.
- **Return the minimum props the page needs** (see [Minimize what crosses the wire](#minimize-what-crosses-the-wire)).
- **After a write, redirect** (`to_route(...)`) — Inertia turns the redirect into a client visit. Flash feedback via `Inertia::flash(...)`.
- **Give reads a deterministic order.** Without an explicit `ORDER BY`, row order is undefined; default to `latest()` (created_at desc) in the service so paginated props are stable.

```php
<?php // app/Http/Controllers/ProjectController.php

namespace App\Http\Controllers;

use App\Services\Project\ProjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectService $projects): Response
    {
        return Inertia::render('projects/index', [
            'projects' => $projects->listForUser($request->user()),
        ]);
    }
}
```

```php
<?php // app/Http/Controllers/ProjectController.php (write)

use App\Actions\Project\CreateProject;
use App\Http\Requests\StoreProjectRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

public function store(StoreProjectRequest $request, CreateProject $action): RedirectResponse
{
    $action->handle($request->validated(), $request->user());

    Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

    return to_route('projects.index');
}
```

Note the controller does **not** query ad hoc, authorize inline, or write to the database itself. It delegates reads to a service and the write to an action, and the `StoreProjectRequest` has already validated + authorized before the method body runs.

### Form Requests (validation + authorization live here)

This is the real thing in an Inertia app — there's no client-side form object equivalent that the backend trusts. Every non-trivial write gets a Form Request that owns **both** validation and authorization. Validation errors are returned to Inertia automatically and surface in React as `errors` (see [Forms](#forms-inertia-form--useform)).

**Create it:** `php artisan make:request StoreProjectRequest`.

```php
<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('projects')],
            'status' => ['required', new Enum(ProjectStatus::class)],
        ];
    }
}
```

- `authorize()` is the permission gate; `rules()` is the shape gate. **You need both** — validation is not authorization (FSE-015 covers the boundary; the policy covers permission). When authorization depends on the resolved route model rather than the incoming payload, `Gate::authorize('update', $post)` at the top of the controller action is the equivalent gate — pick one and use it consistently.
- `$request->validated()` returns only the validated bag — hand that straight to your service/action. **Never** pass `$request->all()` into a mass-assignment, and every model still defines `$fillable` as a second line of defense.
- Prefer **array rule notation** (`['required', 'string', Rule::unique('projects')]`) in new requests — it composes with `Rule::` objects — but match the notation the neighbouring requests already use.
- Cross-field or stateful checks go in the request's `after()` method (or `Rule::when(...)` for conditional rules), not in the controller.
- Reuse a `rules()` array across store/update by extracting to a shared method or a base request when the sets overlap — one source of truth.

For sharing rules between a Form Request and any non-Inertia surface (an API endpoint, a command), extract the rules array to one place so validation never forks.

### Domain Service Classes

Reusable business logic lives in service classes grouped by domain — plain PHP resolved from the container. They encapsulate queries, orchestration, and business rules, and are called from controllers, actions, jobs, or commands alike. This keeps domain logic behind a seam (FSE-013) rather than hardwired into the HTTP entry point.

```
app/
  Services/
    Project/
      ProjectService.php
    Billing/
      BillingService.php
```

```php
// app/Services/Project/ProjectService.php
class ProjectService
{
    public function listForUser(User $user): Collection
    {
        return $user->projects()->with('tasks')->latest()->get();
    }

    public function create(array $data, User $user): Project
    {
        return $user->projects()->create($data);
    }
}
```

### Laravel Actions (for bespoke operations)

Use `lorisleiva/laravel-actions` when an operation is a self-contained task that should also be runnable as a job, listener, or command — or when it bundles its own authorization, validation, and execution. Don't use actions for trivial CRUD a service already handles.

```php
// app/Actions/Project/ArchiveCompletedProjects.php
class ArchiveCompletedProjects
{
    use AsAction;

    public function handle(User $user): int
    {
        return $user->projects()
            ->where('status', 'completed')
            ->where('completed_at', '<', now()->subMonths(3))
            ->update(['archived' => true]);
    }

    public function asCommand(Command $command): void
    {
        $count = $this->handle(User::find($command->argument('user')));
        $command->info("Archived {$count} projects.");
    }
}
```

A controller invokes it by type-hinting it on the action method (as in `store` above) or calling `ArchiveCompletedProjects::run($user)`.

### Decision: Service vs Action

| Situation | Use |
|---|---|
| Standard domain logic (queries, CRUD orchestration, business rules) | Service class |
| Bespoke task that should also run as a job, command, or listener | Laravel Action |
| Operation with its own auth + validation that runs from multiple entry points | Laravel Action |
| Logic reused across multiple services or contexts | Extract to a Laravel Action, call via `MyAction::run(...)` |
| Complex multi-step operation or pipeline | Laravel Action wrapping a `Pipeline` or `DB::transaction` |
| A service class is growing too large | Extract cohesive chunks into Actions the service orchestrates |
| Trivial one-liner | Keep it in the service or the controller method |

Multi-step writes wrap a `Pipeline` in `DB::transaction` so any failing stage rolls everything back:

```php
class OrderService
{
    public function checkout(Cart $cart, User $user): Order
    {
        return DB::transaction(function () use ($cart, $user) {
            $order = CreateOrderFromCart::run($cart, $user);
            ApplyDiscountCodes::run($order, $cart->discountCodes);
            ChargePaymentMethod::run($order, $user->defaultPaymentMethod());
            SendOrderConfirmation::run($order);
            return $order;
        });
    }
}
```

### When you still use a JSON API / traditional controller

Inertia serves your first-party screens. Keep plain JSON controllers (Eloquent API Resources) for surfaces Inertia doesn't render:

- A JSON/API surface (mobile client, third-party integration)
- Webhooks and OAuth callbacks
- File downloads / streamed responses
- Stateless endpoints polled by client code that isn't a page visit

Those follow the standard thin-controller + Form Request rules unchanged. When *consuming* a third-party API, model each endpoint as a request class (e.g. Saloon) with explicit method/path/body and a typed response, and always set timeouts + bounded retries with backoff — retry transient failures only, never permanent ones.

### Testing (every seam is a test seam)

The layering above exists partly so each piece is testable in isolation — keep pure rules runnable without booting the framework, and reach for the framework only where the contract needs it.

- **Feature tests** cover the controller → Inertia response contract. Assert the page and its props with Inertia's testing helpers rather than scraping HTML:

```php
use Inertia\Testing\AssertableInertia as Assert;

$this->actingAs($user)
    ->get(route('projects.index'))
    ->assertInertia(fn (Assert $page) => $page
        ->component('projects/index')
        ->has('projects', 3)
    );

$this->actingAs($user)
    ->post(route('projects.store'), ['name' => 'Launch', 'status' => 'active'])
    ->assertRedirect(route('projects.index'));
```

- **Unit tests** cover services, actions, and policies directly — the pure business logic, no HTTP round-trip. This is the payoff for pushing logic out of the controller.
- **Form Requests**: assert the rules that matter (a missing `name` fails, an unauthorized user is 403'd) through a feature test hitting the route.
- Choose the smallest test that protects the contract — don't boot Laravel for a calculation, don't mock away the thing you're actually verifying.

Create with `php artisan make:test --pest {name}` (add `--unit` for unit tests); run with `php artisan test --compact --filter=...`.

---

## The Inertia Bridge (props, routing, reloads)

There is no prop system you design — Inertia *is* the contract. A controller returns a page name and a prop array; Inertia serializes the props, renders the matching React page, and on subsequent visits swaps only what changed.

### Routing

Routes live in Laravel (`routes/web.php`, `routes/settings.php`). Register resourceful controllers; use `Route::inertia()` for pages with no controller logic:

```php
Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

// Page with no backend logic — no controller needed:
Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
```

From React, **navigate with typed helpers, never string URLs**:

```tsx
import { Link } from '@inertiajs/react';
import { index, show } from '@/routes/projects';   // Wayfinder named-route helpers

<Link href={index()}>All projects</Link>
<Link href={show(project.id)}>{project.name}</Link>
```

`<Link>` performs an Inertia visit (XHR + partial swap), not a full page load. Wayfinder helpers keep the URL, method, and params type-checked against the Laravel route.

### Shared data (auth user, flash, etc.)

Cross-cutting props every page needs come from `HandleInertiaRequests::share()`, not from each controller:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => ['user' => $request->user()],
        'sidebarOpen' => ! $request->hasCookie('sidebar_state')
            || $request->cookie('sidebar_state') === 'true',
    ];
}
```

Read shared props anywhere with `usePage()`:

```tsx
import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

const { auth } = usePage<{ auth: Auth }>().props;
```

Keep `share()` lean — it ships on **every** request. Don't put per-page data here.

### Minimize what crosses the wire

Every prop is serialized into the initial HTML and re-sent on partial reloads, so **size matters and every field is visible to the client** — this is the Inertia analogue of the RSC-boundary rule (vercel-react §3.6): only send fields the page actually renders.

- Shape props with **Eloquent API Resources** or explicit arrays — never return a raw model with 30 columns when the card shows 3. Select only the columns the page renders (`->select(...)`, and `:id,name` on eager loads — always include the foreign key).
- **Eager-load every relationship a prop touches** (`->with('author')`). A prop bag built in a loop over lazy relations is the classic N+1 — and it's silent until the payload is huge. Enable `Model::preventLazyLoading(! app()->isProduction())` in `AppServiceProvider::boot()` so it throws in dev instead of shipping.
- Use `withCount('comments')` instead of loading a whole relation just to send its size.
- Do array transforms (`sort`, `filter`, `map`) on the client, not by shipping a second derived copy of the same data (vercel-react §3.2).
- Use **partial reloads** (`only` / `except`) so a re-visit re-fetches just the props that changed:

```tsx
import { router } from '@inertiajs/react';

router.reload({ only: ['projects'] });
```

- For genuinely slow props, use **deferred props** (Inertia v3 `Inertia::defer()`) so the page paints immediately and the heavy data streams in behind a skeleton — the Inertia counterpart to a Suspense boundary (vercel-react §1.6). Always pair a deferred prop with a pulsing/animated skeleton empty state.

```php
return Inertia::render('projects/show', [
    'project' => $project,
    'activity' => Inertia::defer(fn () => $activityService->for($project)),
]);
```

```tsx
import { Deferred } from '@inertiajs/react';

<Deferred data="activity" fallback={<ActivitySkeleton />}>
    <ActivityFeed />
</Deferred>
```

- Assemble independent props in the controller without introducing a **server-side waterfall** — don't query one source only to gate a second independent one (vercel-react §1.5 / §3.7). For genuinely independent, expensive reads, run them in parallel with `Concurrency::run([...])` rather than sequentially.

> **Two different `defer`s — don't confuse them.** `Inertia::defer(fn () => ...)` is a *deferred prop* that streams to the client after first paint (above). Laravel's `defer(fn () => ...)` helper runs fire-and-forget work (analytics, logging, cleanup) *after the HTTP response is sent* in the same process — reach for it instead of a queued job when the work is trivial and needn't survive a crash.

### Inertia v3 conveniences (reach for these before hand-rolling)

The protocol already solves most "make it feel fast" problems — don't rebuild them with `useEffect` + `fetch`:

- **Prefetch on intent**: `<Link href={show(id)} prefetch>` warms the next page on hover/focus. The navigation counterpart to preloading a heavy bundle (vercel-react §2.6).
- **Instant visits**: `<Link href={...} component="Projects/Show" pageProps={{ project }}>` renders the target immediately with shared props while page props load behind it.
- **Optimistic updates**: `router.optimistic(...)` (also a prop on `<Form>` / `useForm`) applies the change locally and rolls back automatically if the request fails — the correct way to bridge "instant feedback" and "server truth".
- **Below-the-fold data**: `<WhenVisible data="stats" fallback={<Skeleton />}>` lazy-loads a prop when it scrolls into view; `<InfiniteScroll data="users">` paginates on scroll (server uses `Inertia::scroll()`).
- **Live data**: the `usePoll(5000, { only: ['stats'] })` hook refreshes a prop on an interval and handles cleanup + inactive-tab throttling for you.
- **Layout data that changes per page**: call `setLayoutProps({ title, showSidebar })` from the page instead of prop-drilling into a persistent layout. (Static breadcrumbs still go on the `Page.layout` property.)

---

## Frontend / React Rules

This is the core of the boundary: **Inertia props are server-owned truth; React state is client-owned.** Choosing the wrong side is the main source of stale UIs (treating a prop as mutable) or untrustworthy state (keeping something client-side that the backend must validate).

### The state decision: Inertia prop vs React state

Use an **Inertia prop** (server round-trip via a visit) when the state:
- must persist or hit the database
- involves auth, validation, or business rules
- must be trusted by the backend
- is the source of truth for what's saved

You never mutate a prop in place. You change server state by **visiting a route** (a form submit, a `router` visit, a `<Link>`), and the fresh prop comes back down.

Use **React state** (`useState`/`useReducer`/`useRef`, zero round-trip) when the state is:
- ephemeral and presentational — dropdown open/closed, active tab, modal visibility, hover, "show password"
- a mid-interaction transient — drag position, in-progress reorder, unsaved input keystrokes
- something a server request per change would make feel laggy

Use **both, bridged**, when an interaction needs instant local feedback *and* must eventually reach the server: drive the input with local state (or `useForm`) and submit to a route on save. For instant-feeling writes, Inertia v3 **optimistic updates** apply the change locally and roll back automatically if the request fails.

**Rule of thumb:** if losing the value on refresh is fine and the backend doesn't care about it, it's React state. If the value is meaningful to the server, it's an Inertia prop changed via a visit.

### Page components (thin, like the controller)

Page components live in `resources/js/pages/` and map 1:1 to `Inertia::render('...')` names. Keep them thin — they receive typed props, compose UI components, and wire up forms/visits. Push complex client logic into hooks and reusable components, exactly as the controller pushes logic into services.

```tsx
// resources/js/pages/projects/index.tsx
import { Head } from '@inertiajs/react';
import { ProjectCard } from '@/components/project-card';
import { create } from '@/routes/projects';
import type { Project } from '@/types';

export default function ProjectsIndex({ projects }: { projects: Project[] }) {
    return (
        <>
            <Head title="Projects" />
            <div className="grid gap-4 md:grid-cols-3">
                {projects.map((project) => (
                    <ProjectCard key={project.id} project={project} />
                ))}
            </div>
        </>
    );
}
```

**Layouts** are assigned per page, not imported into each — the resolver in `resources/js/app.tsx` maps page-name prefixes to layouts, and a page declares breadcrumbs via a static `layout` property:

```tsx
import { index } from '@/routes/projects';

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projects', href: index() }],
};
```

### Forms (Inertia `<Form>` / `useForm`)

Forms submit to a Laravel route; validation errors come back from the Form Request and surface as `errors`. Prefer the `<Form>` component wired to a Wayfinder controller action — it manages `processing`, `errors`, and reset for you:

```tsx
import { Form } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';

<Form {...ProjectController.store.form()} options={{ preserveScroll: true }}>
    {({ processing, errors }) => (
        <>
            <Input name="name" defaultValue="" required />
            <InputError message={errors.name} />
            <Button disabled={processing}>Create</Button>
        </>
    )}
</Form>
```

For programmatic control (multi-step, conditional fields, transforms before submit), use the `useForm` hook:

```tsx
import { useForm } from '@inertiajs/react';
import { store } from '@/routes/projects';

const form = useForm({ name: '', status: 'active' });

const submit = (e: React.FormEvent) => {
    e.preventDefault();
    form.post(store().url, { preserveScroll: true });
};
```

- **The server is the validator.** Client-side `required`/`type` attributes are UX niceties; the Form Request is the gate. Treat submitted values as untrusted server-side regardless.
- Bind the controller action with Wayfinder (`ProjectController.store.form()`) so the method + URL stay typed.
- `errors` keys match the Form Request field names — render them with the shared `InputError` component, don't hand-roll error markup per field.
- The `<Form>` render-prop exposes more than `processing`/`errors`: `wasSuccessful`, `recentlySuccessful`, `isDirty`, `progress` (upload %), `defaults`, `reset`, `clearErrors`. Use the declarative reset props — `resetOnSuccess`, `resetOnError`, `setDefaultsOnSuccess` — instead of resetting by hand.
- For a request that must **not** trigger a page visit (typeahead search, a background lookup hitting a JSON endpoint), use the `useHttp` hook — same ergonomics as `useForm`, but it returns JSON instead of swapping props.

### Component structure

- **One concept per component.** Compose pages from small components under `resources/js/components/` and primitives under `resources/js/components/ui/` (shadcn/ui).
- **Reuse the UI kit before writing new markup.** Buttons, inputs, dialogs, selects, etc. already exist under `components/ui`. Extend via `class-variance-authority` variants, not copy-paste.
- **Never define a component inside another component** (vercel-react §5.4) — it's a new type every render, so React remounts it, losing state and focus. Lift it out and pass props. This is the most common re-render bug in React code.
- **Extract to reusable components / custom hooks; don't hide logic in a giant page.** Server logic → controllers/services/actions. Client logic → a custom hook (below). Composition should track domain capability (FSA-007), not split one screen across many technical shards.

### Client-only logic — custom hooks (the reusable-state analogue)

When a page has complex client-side state that never needs the server (a multi-step wizard, drag-and-drop, a rich editor, keyboard shortcuts), extract it into a named hook under `resources/js/hooks/` instead of inlining a large tangle of `useState`/`useEffect` in the component. This mirrors the existing hooks in the app (`use-appearance`, `use-clipboard`, `use-flash-toast`, `use-two-factor-auth`).

```ts
// resources/js/hooks/use-wizard.ts
import { useCallback, useState } from 'react';

export function useWizard(stepCount: number) {
    const [step, setStep] = useState(0);

    const next = useCallback(
        () => setStep((s) => Math.min(s + 1, stepCount - 1)),
        [stepCount],
    );
    const back = useCallback(() => setStep((s) => Math.max(s - 1, 0)), []);

    // Derived values are computed during render, not stored in state.
    const isFirst = step === 0;
    const isLast = step === stepCount - 1;

    return { step, next, back, isFirst, isLast };
}
```

- **Derive during render** (vercel-react §5.1) — `isFirst`/`isLast` above are plain expressions, not extra state kept in sync by an effect. Storing derivable values in state is the #1 source of state drift and redundant renders.
- **Use functional `setState` updates** (vercel-react §5.11) when the next value depends on the previous — `setStep((s) => s + 1)`, not `setStep(step + 1)`.
- **Lazy-initialize** expensive initial state (vercel-react §5.12): `useState(() => expensiveInit())`.

### Shared client state — React Context (the store analogue)

When multiple components need the same *client-only* state (theme, sidebar collapse, a command palette), use a Context provider mounted in `app.tsx`'s `withApp`, not prop-drilling or duplicated `useState`. The app already composes `TooltipProvider` and the sonner `Toaster` there.

```tsx
// resources/js/contexts/command-palette.tsx
import { createContext, useContext, useState } from 'react';

const CommandPaletteContext = createContext<{
    open: boolean;
    setOpen: (open: boolean) => void;
} | null>(null);

export function CommandPaletteProvider({ children }: { children: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    return (
        <CommandPaletteContext value={{ open, setOpen }}>
            {children}
        </CommandPaletteContext>
    );
}

export function useCommandPalette() {
    const ctx = useContext(CommandPaletteContext);
    if (!ctx) throw new Error('useCommandPalette must be used within its provider');
    return ctx;
}
```

Reserve Context for genuinely client-originated shared state. **Server-originated shared state (auth user, flash) comes from Inertia shared props via `usePage()`, not a Context.** Toasts already flow from server flash → sonner via the `use-flash-toast` hook; reuse that path rather than building a parallel one.

### Side effects — `useEffect` and Inertia router events

Setup/teardown for client systems (WebSockets, timers, DOM/canvas APIs, global listeners) belongs in `useEffect` with a cleanup return. The existing `use-flash-toast` hook is the model — it subscribes to an Inertia router event and returns the unsubscribe:

```ts
useEffect(() => {
    return router.on('flash', (event) => {
        const data = (event as CustomEvent).detail?.flash?.toast;
        if (data) toast[data.type](data.message);
    });
}, []);
```

- **Narrow effect dependencies to primitives** (vercel-react §5.7) — depend on `user.id`, not the whole `user` object, so the effect doesn't re-run on unrelated field changes.
- **Don't use an effect to sync derived state** (vercel-react §5.1) or to react to a prop change you could handle by deriving during render or keying the component.
- Use passive listeners (`{ passive: true }`) for scroll/touch/wheel handlers that don't call `preventDefault()` (vercel-react §4.2).

### Memoization — the React Compiler is on

**This project builds with the React Compiler** (`babel-plugin-react-compiler`). It auto-memoizes components and hook values, so **do not reach for `useMemo`/`useCallback`/`memo` by default** (vercel-react §5.6 note). Write straightforward code; let the compiler optimize re-renders. Reserve manual memoization for a measured hot path the compiler provably can't handle, and don't wrap a cheap primitive expression in `useMemo` (vercel-react §5.3) — the comparison costs more than the expression.

### Bundle size (Vite)

- **Import icons and components directly, not from barrel files** (vercel-react §2.1). Import named icons from `lucide-react` as you already do; avoid deep re-export hubs that pull thousands of modules.
- **Import Wayfinder helpers by name**, not as a namespace (`import { show, store } from '@/actions/...'`) — named imports tree-shake; a namespace import drags every route into the bundle.
- **Lazy-load heavy, non-initial components** with `React.lazy` + `Suspense` or a dynamic `import()` (vercel-react §2.4) — a rich editor, a charting library, a modal that's rarely opened.
- **Preload on intent** (vercel-react §2.6): trigger the dynamic `import()` on hover/focus of the control that opens the heavy view, so it's ready by click.

---

## Security Mandates

Inertia's one-way prop flow removes some footguns (you can't mutate server state by editing a prop), but adds one big one: **every prop is visible to the client.** Discipline:

- **Never send a prop the user shouldn't see.** Props are embedded in the page HTML and readable in the browser. Shape reads with API Resources; omit internal columns, tokens, other users' data, and admin-only fields. This is a data-hygiene mandate, not a nicety.
- **Validate and authorize in the Form Request / policy, every time.** `authorize()` gates permission; `rules()` gates shape. Middleware only proves "is logged in." Validation is not authorization — you need both (FSE-015).
- **Never mass-assign request input.** Pass `$request->validated()` (or an explicit array) to the model, never `$request->all()` — and every model defines `$fillable` (never `$guarded = []` on anything that takes user input).
- **Treat all submitted form values as untrusted.** Client-side HTML validation is UX only; the server rules run regardless.
- **Authorize page reads too**, not just writes — a `show`/`edit` controller must check the policy before rendering, or you'll ship another user's record as a prop.
- **Rate-limit auth and sensitive routes** with `throttle` middleware (the settings routes here already do, e.g. `throttle:6,1` on password update).
- **Encrypt secrets at rest**: tokens/API keys use the `encrypted` cast and sit in the model's `$hidden` array so they never serialize into a prop.
- CSRF is handled by Inertia's HTTP client automatically — it sends the `XSRF-TOKEN` cookie back as the `X-XSRF-TOKEN` header, which Laravel accepts in place of a `@csrf` field. Don't add `@csrf` to Inertia forms, and don't disable the protection.

---

## Full-Stack Decision Tree

| What you're deciding | Answer |
|---|---|
| Where does routing live? | Laravel (`routes/*.php`). Resourceful controllers; `Route::inertia()` for logic-free pages. |
| How do I link/navigate in React? | Wayfinder helpers (`@/routes/*`, `@/actions/*`) inside `<Link>` / `router` — never string URLs. |
| Where does auth, validation, data fetching live? | Laravel — controllers delegate to policies, Form Requests, services. |
| Where does business logic live? | Domain service classes; Laravel Actions for bespoke cross-cutting tasks. |
| What is the page entry point? | A thin controller returning `Inertia::render('page', $props)`. |
| Where does form validation + authorization live? | A Form Request (`rules()` + `authorize()`). |
| How do props reach React? | Serialized by Inertia; per-page from the controller, cross-cutting from `HandleInertiaRequests::share()`. |
| Is this state a prop or React state? | Server-meaningful / persisted / validated → Inertia prop (changed by visiting a route). Ephemeral / presentational / instant → React state. Bridge via a form submit or optimistic update when it's both. |
| How do I change server state? | Submit a form or `router` visit to a route; the fresh prop returns. Never mutate a prop in place. |
| When do I extract a custom hook? | When client-only logic is complex enough to clutter the component. |
| When do I use React Context? | Client-originated state shared across components (theme, sidebar, palette). |
| Context vs Inertia shared props? | Context = client-originated shared state. Shared props (`usePage()`) = server-originated shared state (auth, flash). |
| Should I `useMemo`/`memo` this? | Usually no — the React Compiler is enabled. Only for a measured hot path it can't handle. |
| A prop is slow to compute — what do I do? | `Inertia::defer()` + a `<Deferred>`/`<WhenVisible>` skeleton; partial reloads (`only`) for re-fetches; `Concurrency::run()` for parallel independent reads. |
| `Inertia::defer()` vs `defer()`? | `Inertia::defer()` = a prop that streams after paint. Laravel `defer()` = fire-and-forget work after the response (logging, analytics). |
| A prop bag loops over relations — how do I avoid N+1? | Eager-load with `with()` / `withCount()`, select only needed columns, and turn on `preventLazyLoading` in dev. |
| I need a request that doesn't swap the page | `useHttp` (JSON endpoint), not `useForm`/`<Form>`. |
| Make navigation feel instant | `<Link prefetch>`, instant visits (`component`/`pageProps`), or optimistic updates — not a manual loading state. |
| When do I reach for a plain JSON controller? | Non-Inertia surfaces only: API, webhooks, downloads. |
| How do I test a page? | Feature test + `assertInertia` on component/props; unit-test services/actions/policies directly. Pest. |

---

## File Structure Reference

```
app/
  Http/
    Controllers/               # Thin entry points returning Inertia::render(...)
      ProjectController.php
    Requests/                  # Form Requests (validation + authorization)
      StoreProjectRequest.php
    Middleware/
      HandleInertiaRequests.php # Shared props (auth, flash, sidebar)
  Actions/                     # lorisleiva/laravel-actions (bespoke tasks)
    Project/
      ArchiveCompletedProjects.php
  Services/                    # Domain service classes (core business logic)
    Project/
      ProjectService.php
  Policies/                    # Authorization
    ProjectPolicy.php

resources/
  js/
    app.tsx                    # createInertiaApp: layout resolver, providers
    pages/                     # One file per Inertia::render('...') page
      projects/
        index.tsx
        show.tsx
        create.tsx
      settings/
        profile.tsx
    layouts/                   # App/auth/settings layouts (assigned via resolver)
      app-layout.tsx
    components/                # Reusable components
      project-card.tsx
      input-error.tsx
      ui/                      # shadcn/ui primitives (Radix + CVA)
        button.tsx
        input.tsx
        dialog.tsx
    hooks/                     # Client-only logic (the reusable-state analogue)
      use-appearance.tsx
      use-flash-toast.ts
    contexts/                  # React Context providers (client shared state)
      command-palette.tsx
    routes/                    # Wayfinder named-route helpers (generated)
    actions/                   # Wayfinder controller-action helpers (generated)
    types/                     # Shared TS types (Auth, models, props)

routes/
  web.php
  settings.php
```

---

## Common Pitfalls

- Using a plain `<a>` (or `<form>`) instead of `<Link>` / `<Form>` — it triggers a full reload and drops out of the SPA.
- Rendering a deferred / `WhenVisible` prop without handling its `undefined` state and skeleton first.
- Mutating a prop object in React and expecting it to persist — props are server-owned; change them via a visit.
- Building a prop bag over lazy relations (N+1) — eager-load first.
- Sending a whole model as a prop when the page shows three fields — shape it, and remember the client can read all of it.
- v3 renames: use `router.cancelAll()` (not `router.cancel()`), and listen for `httpException` / `networkError` (not `invalid` / `exception`).
- Reaching for `useMemo`/`memo` reflexively — the React Compiler is on; let it work.

---

## Key Mental Model

1. **Laravel is the brain** — routing, auth, validation, business logic, data. React never becomes a second backend.
2. **Controllers are thin dispatchers** — they authorize, gather props (delegating to services), and `render` or `redirect`. Logic does not accumulate here even though nothing stops it; that restraint is the backend half of the game.
3. **Services hold domain logic, Actions hold bespoke operations** — identical to any clean Laravel app; kept behind seams you can test and reuse.
4. **Form Requests own validation + authorization** — the two gates every write must pass; validation is not authorization.
5. **Inertia props are server-owned truth, React state is client-owned** — you change server state by visiting a route and receiving a fresh prop, never by mutating a prop. Ephemeral UI state lives in React and never crosses the wire.
6. **The wire is a cost and a window** — every prop is serialized to the browser and visible there. Send the minimum, shape it with Resources, defer the slow parts, and never leak what the user shouldn't see.
7. **The boundary works for you — so lean into it** — let the React Compiler handle memoization, let Wayfinder type your routes, let Form Requests guard the seam, and keep each side doing only its job.
```
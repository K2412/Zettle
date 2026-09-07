<h1 align="center">Zettle</h1>

<p align="center">
  <strong>Your Zettelkasten, Supercharged</strong>
</p>

<p align="center">
  An open-source, AI-powered note-taking app built on Zettelkasten principles.<br>
  Think in connections. Build your second brain.
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#installation">Installation</a> •
  <a href="#usage">Usage</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#contributing">Contributing</a> •
  <a href="#license">License</a>
</p>

---

## About

Zettle is a modern note-taking application that implements the Zettelkasten method — a proven system for building a personal knowledge base. Unlike traditional note apps, Zettle focuses on **connections between ideas** rather than hierarchical folders.

**Key philosophy:**

- **Atomic notes** — One idea per permanent note, small enough to reuse, connected enough to be powerful
- **Bidirectional links** — Every note can connect to any other using `[[wikilinks]]`
- **AI-augmented** — Assists surface and shape connections; they never think for you, and they never rewrite the note you are looking at
- **Open source** — Your second brain deserves transparency and freedom from vendor lock-in

## Features

### Core

- **Bidirectional linking** — Link notes with `[[Note Title]]` syntax; on save the server reconciles wikilinks into `mentions` connections, and every note shows its backlinks. Typed edges you asserted by hand survive that sync.
- **Typed connections** — Beyond mentions, assert a directed, *typed* relationship between two notes (`supports`, `contradicts`, `depends_on`, …). The edge is stored once; the target note shows a computed **inverse label** ("supported by"), and symmetric relationships read the same both ways.
- **Knowledge graph** — Visualize your whole note network as an interactive **force-directed canvas** (`d3-force`). Typed edges render solid, mentions faint-dashed, nodes take their tag color; drag to rearrange, click to open.
- **Hybrid search** — Find notes by words *or* meaning. An explicit Search press blends Laravel Scout keyword matches with **sqlite-vec** semantic hits, fused by Reciprocal Rank Fusion. Typing alone does not search.
- **Rich tags** — Colored tags with a dedicated management page: **rename**, **recolor**, **delete**, and **merge** two tags into one. Filter the notes index by tag.
- **Note templates** — Reusable title prefixes, body skeletons, and tag presets. Apply a template to land in a new note already scaffolded.
- **CodeMirror editor** — A CodeMirror 6 markdown editor with `[[` autocomplete, **debounced auto-save** and a live save indicator, and write / preview tabs (preview rendered with `marked` plus a wikilink renderer).

### AI-augmented

Each note has an **assist panel** that suggests the next playbook phase and runs **two-rail** actions: a read-only JSON fetch that proposes, then an Inertia write you opt into. Assists **never rewrite** the viewed note's title or body.

| Assist | What it proposes | What you can accept |
|---|---|---|
| **Triage** | Destination and note type | Set the type |
| **Atomize** | Distinct ideas hiding in one note | Spawn permanent notes, provenance-linked back |
| **Formulate** | A claim scaffold and a prose critique of your draft | Copy; writes nothing |
| **Connect** | Typed links to unlinked neighbours | Create the connection |
| **Make findable** | Retrieval contexts, tags, a discovery hint | Attach a tag or set the hint |
| **Structure** | A cluster scaffold and central question | Create a structure note |
| **Cluster to project** | Ripe clusters ready to harvest | Create a project note |

**Find connections** (separate from the Connect assist) surfaces semantically similar unlinked notes and opens the connect form pre-filled.

> AI is **optional**. Without API keys the rest of the app runs fully. In `local` and `testing`, embeddings and assists fall back to a deterministic fake so development and the test suite never hit the network.

### Authentication

Headless **Laravel Fortify** — email/password registration, login, and password reset; email verification; **two-factor authentication** (TOTP + recovery codes); **passkeys** (WebAuthn); and profile / security management.

### Experience

- **Dark mode** — Full dark-mode support with appearance settings
- **Instant SPA navigation** — Inertia visits swap pages without a full reload; typed routes via Laravel Wayfinder
- **Clean UI** — A minimal writing environment built on bits-ui + Tailwind CSS 4

## Tech Stack

### Backend

- **PHP** 8.3+ (**8.4 recommended** — `Pdo\Sqlite::loadExtension` is required for sqlite-vec)
- **Laravel** 13
- **Inertia** v3 — server-driven SPA; controllers return `Inertia::render(...)`, no separate REST API for first-party screens
- **Laravel Fortify** — headless authentication
- **Laravel Scout** 11 — keyword search (database driver by default)
- **Laravel AI SDK** (`laravel/ai`) — Anthropic (assists) and OpenAI (embeddings)
- **Laravel Actions** (`lorisleiva/laravel-actions`)
- **sqlite-vec** — vector search extension for SQLite

### Frontend

- **Svelte** 5 + **TypeScript** (`@inertiajs/svelte`) with runes
- **bits-ui** — headless primitives under `resources/js/components/ui`
- **Laravel Wayfinder** — typed route and controller helpers; no handwritten URLs
- **Tailwind CSS** 4
- **CodeMirror** 6 — the markdown authoring surface
- **marked** — preview rendering · **d3-force** — the graph simulation · **@lucide/svelte** — icons
- **Vite** 8 + Vite+ (`vp`)

Client state follows three scopes — inline `$state`, page-local classes in `.svelte.ts`, and context on Show for the assist panel. There are no module-level store singletons. See [`docs/architecture-svelte.md`](docs/architecture-svelte.md) and [`docs/patterns-svelte-class-stores.md`](docs/patterns-svelte-class-stores.md).

### Tooling & quality

- **Pest** 5 — feature, unit, and **browser** tests (Playwright)
- **Laravel Pint** — PHP formatter · **svelte-check** — TypeScript + Svelte
- **Laravel Boost** — in-app MCP tooling for AI-assisted development

### Database

- **SQLite** (default, with the **sqlite-vec** extension)

This is **not** a SvelteKit app. Laravel owns URLs; Inertia persistent layouts are the tree that class stores hang off.

## Requirements

- PHP 8.3 or higher (8.4 recommended)
- Composer
- Node.js 20+ and npm
- SQLite with extension-loading enabled (`pdo_sqlite` built with loadable extensions)
- An Anthropic API key (for AI assists) — optional but recommended
- An OpenAI API key (for embeddings used by search + discovery) — optional but recommended

## Installation

### Don't have PHP / Composer yet?

The [php.new](https://php.new) installer sets up PHP, Composer, and the Laravel installer in one step.

**macOS**
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

**Windows** (run PowerShell as Administrator)
```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

**Linux**
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

Already have PHP 8.3+ and Composer? Skip straight to the Quick Start.

### Quick Start

```bash
git clone https://github.com/K2412/Zettle.git
cd Zettle

composer install
npm install

cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Install **sqlite-vec** into `database/extensions/` before migrating. The binary is platform-specific and not committed. See [`database/extensions/README.md`](database/extensions/README.md). On macOS arm64 you can copy `vec0.dylib` from the `sqlite-vec-darwin-arm64` npm package.

```bash
php artisan migrate
npm run build
```

`composer run setup` wraps environment + database bootstrap plus `npm install` and `npm run build` if you'd rather run it in one step — still install sqlite-vec first.

### Configure AI + embeddings (optional)

```env
ANTHROPIC_API_KEY=sk-ant-...    # AI assists
OPENAI_API_KEY=sk-...           # vector embeddings (search + discovery)
```

**sqlite-vec** is auto-discovered from `database/extensions/vec0.*`; to point elsewhere, set `SQLITE_VEC_EXTENSION_PATH`. Embeddings use OpenAI `text-embedding-3-small` (1536-dim) by default.

If no keys are set, embedding + assist features stand down — except in `local`/`testing`, where a deterministic fake stands in.

### Development server

```bash
composer run dev
```

Or separately:

```bash
php artisan serve      # Laravel
npm run dev            # Vite (Svelte HMR)
```

Visit `http://localhost:8000`. Authenticated users hitting `/dashboard` are redirected to `/notes`.

## Configuration

```env
APP_NAME=Zettle
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
SCOUT_DRIVER=database
```

The `database` Scout driver is zero-dependency and the default.

## Usage

### Creating notes

1. Open **Notes** and type a title (optionally pick a type).
2. Hit **Create** — you land in the editor.
3. Changes auto-save shortly after you stop typing.

Or apply a **template** from the Templates page to start from a prefix, body, and tags.

### Linking notes

Type `[[` in the editor to open autocomplete. Links render as anchors in preview, and the target note shows a backlink. Only `mentions` are reconciled from wikilinks; typed connections you authored stay put.

### Typed connections

From a note's **Connections** panel, search for another note, pick a relationship, and optionally a rationale. The other note shows the inverse — one stored edge, read from both ends.

### Finding connections (AI)

Click **Find connections** to surface semantically similar notes you haven't linked yet, then assert a connection in one step.

### AI assists

Open the assist panel on a note. The suggested phase is marked. Run a read-only assist, then accept a write (spawn, link, attach tag, create structure/project, …) if you want it. The origin note's title and body are never rewritten.

### Viewing the graph

Click **Graph** in the sidebar. Drag nodes to rearrange; click one to open that note.

### Managing tags

Attach, detach, or create tags inline on a note. On **Tags**, rename, recolor, delete, or merge — notes on the source move to the target.

## Project Structure

```
zettle/
├── app/
│   ├── Actions/                # DeleteNote, MergeTags, ApplyTemplate, …
│   ├── Ai/Agents/              # Triage, Atomize, Formulate, Connect, …
│   ├── Enums/                  # NoteType, Relationship, Phase, …
│   ├── Http/
│   │   ├── Controllers/        # Thin Inertia + JSON two-rail controllers
│   │   ├── Requests/           # Form Requests
│   │   └── Middleware/         # HandleInertiaRequests (shared props)
│   ├── Jobs/                   # EmbedNoteJob, ForgetNoteEmbeddingJob
│   ├── Models/                 # Note, Tag, Connection, NoteTemplate, User
│   ├── Policies/
│   └── Services/               # Notes, tags, graph, embeddings, clusters, assists
├── resources/js/
│   ├── pages/                  # One Svelte file per Inertia page (PascalCase)
│   │   └── notes/              # Index, Show, Graph + *.svelte.ts class stores
│   ├── components/             # Notes, tags, templates, ui/ (bits-ui)
│   ├── layouts/
│   ├── lib/                    # note-search, note-assists, formulate-templates
│   ├── routes/  actions/       # Wayfinder-generated (gitignored; generated on build)
│   └── types/
├── routes/
│   ├── web.php  notes.php  tags.php  templates.php  settings.php
├── database/
│   ├── migrations/             # notes, connections, tags, templates, embeddings
│   ├── extensions/             # vec0.* (gitignored; see README there)
│   └── factories/
├── docs/
│   ├── architecture-svelte.md
│   └── patterns-svelte-class-stores.md
└── tests/                      # Pest — Feature, Unit, and Browser
```

## Architecture

The rules live in [`docs/architecture-svelte.md`](docs/architecture-svelte.md). Two of them carry the rest:

1. **Inertia props are server-owned truth; Svelte `$state` is client-owned.**
   Server-meaningful, persisted, or validated state is a prop, changed by *visiting a route* — never by mutating a prop in place. Ephemeral UI state (tabs, dialogs, assist results, canvas pointers) lives in `$state` or a class store and never crosses the wire.

2. **Controllers are thin dispatchers.**
   A controller authorizes, gathers props (delegating to services), and returns `Inertia::render(...)` or a redirect. Validation and authorization live in Form Requests and policies; business logic lives in `app/Services/` and `app/Actions/`. Svelte is never a second backend.

## Contributing

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Development guidelines

- Follow the existing code style (Pint for PHP, the repo's Svelte/TS conventions)
- Write Pest tests for new features (feature + browser where it touches the UI)
- Read [`docs/architecture-svelte.md`](docs/architecture-svelte.md) before adding a controller, page, or service
- Use Wayfinder helpers only — no handwritten URLs
- New shared client state is a class + context, never a module singleton

### Running tests

```bash
php artisan test --compact
php artisan test --compact --filter=TagMergeEndpointTest
```

Browser tests need Playwright browsers once:

```bash
npx playwright install chromium
php artisan test --testsuite=Browser
```

### Code quality

```bash
vendor/bin/pint
npm run types:check
```

## Roadmap

- [ ] Import from Obsidian / Roam
- [ ] Export to markdown
- [ ] Mobile-first writing experience
- [ ] Self-hosted sync

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Acknowledgments

- Inspired by the Zettelkasten method pioneered by Niklas Luhmann
- Built with [Laravel](https://laravel.com), [Inertia](https://inertiajs.com), [Svelte](https://svelte.dev), and [Tailwind CSS](https://tailwindcss.com)
- UI on [bits-ui](https://bits-ui.com); vector search by [sqlite-vec](https://github.com/asg017/sqlite-vec)

---

<p align="center">
  Made for thinkers
</p>

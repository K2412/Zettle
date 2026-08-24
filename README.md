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

**Key Philosophy:**
- **Atomic Notes** — One idea per note, small enough to be useful, connected enough to be powerful
- **Bidirectional Links** — Every note can connect to any other using `[[wikilinks]]`
- **AI-Augmented** — AI helps surface and shape connections, not think for you
- **Open Source** — Your second brain deserves transparency and freedom from vendor lock-in

## Features

### Core

- **Bidirectional Linking** — Link notes with `[[Note Title]]` syntax; on save the server reconciles wikilinks into `mentions` connections, and every note shows its backlinks.
- **Typed Connections** — Beyond mentions, assert a directed, *typed* relationship between two notes (`supports`, `contradicts`, `depends_on`, …). The edge is stored once; the target note shows a computed **inverse label** ("supported by"), and symmetric relationships read the same both ways.
- **Knowledge Graph** — Visualize your whole note network as an interactive **force-directed canvas** (`d3-force`). Typed edges render solid, mentions faint-dashed, nodes take their tag color; drag to rearrange, click to open.
- **Hybrid Search** — Find notes by words *or* meaning. An explicit search blends Laravel Scout keyword matches with **sqlite-vec** semantic (nearest-neighbour) hits, fused by Reciprocal Rank Fusion.
- **Rich Tags** — Colored tags with a dedicated management page: **rename**, **recolor** (any hex), **delete**, and **merge** two tags into one. Filter the notes index by tag.
- **CodeMirror Editor** — A CodeMirror 6 markdown editor with `[[` autocomplete, **debounced auto-save** and a live save indicator, and write / preview tabs (rendered with `react-markdown`).

### AI-Augmented

- **Find Connections** — Semantic-similarity discovery powered by vector embeddings (stored in **sqlite-vec**) surfaces related notes you might want to link, and opens the connect form pre-filled.
- **AI Assists** — An assist panel on each note suggests the next move for it (its *phase*) and runs read-only AI actions that **propose, never rewrite**. **Atomize** is shipped: it reads a note holding several ideas and proposes each as its own atomic note, spawning fresh permanent notes linked back to the origin. More assists (Triage, Formulate, Connect, Make Findable, Structure, Cluster) are being ported.

> AI is **optional**. Without API keys the app runs fully; embedding and assist features simply stand down (in local/testing they fall back to a deterministic fake).

### Authentication

Headless **Laravel Fortify** — email/password registration, login, and password reset; email verification; **two-factor authentication** (TOTP + recovery codes); **passkeys** (WebAuthn); and profile / security management.

### Experience

- **Dark Mode** — Full dark-mode support with appearance settings.
- **Instant SPA Navigation** — Inertia visits swap pages without a full reload; typed routes via Laravel Wayfinder.
- **Clean UI** — A minimal, distraction-free environment built on shadcn/ui.
- **React Compiler** — Auto-memoized components; no hand-rolled `useMemo`/`useCallback`.

## Screenshots

<p align="center">
  <em>Screenshots coming soon</em>
</p>

## Tech Stack

### Backend
- **PHP** 8.3+ (8.4 recommended)
- **Laravel** 13
- **Inertia** v3 — server-driven SPA; controllers return `Inertia::render(...)`, no separate REST API for first-party screens
- **Laravel Fortify** — headless authentication (login, register, 2FA, email verification, passkeys)
- **Laravel Scout** 11 — full-text search abstraction (database driver by default)
- **Laravel AI SDK** (`laravel/ai`) — unified API for Anthropic, OpenAI, and other providers
- **Laravel Actions** (`lorisleiva/laravel-actions`) — invokable domain actions
- **sqlite-vec** — vector search extension for SQLite (semantic connections + discovery)

### Frontend
- **React** 19 + **TypeScript** (`@inertiajs/react`), with the **React Compiler** enabled
- **shadcn/ui** — Radix primitives + `class-variance-authority`
- **Laravel Wayfinder** — typed route/controller helpers generated for the frontend
- **Tailwind CSS** 4 — utility-first styling
- **CodeMirror** 6 — the markdown authoring surface
- **react-markdown** — preview rendering · **d3-force** — the graph simulation · **lucide-react** — icons
- **Vite** 8 — build tooling

### Tooling & Quality
- **Pest** 5 — feature, unit, and **browser** tests (Playwright, headless); 260+ tests across services, actions, controllers (with `AssertableInertia`), and client behaviour
- **Laravel Pint** — PHP formatter · **ESLint** + **Prettier** — JS/TS
- **Laravel Boost** — in-app MCP tooling for AI-assisted development

### Database
- **SQLite** (default, with the **sqlite-vec** extension for vector search)

## Requirements

- PHP 8.3 or higher (8.4 recommended)
- Composer
- Node.js 20+ and npm
- SQLite with extension-loading enabled (`pdo_sqlite` built with `--enable-loadable-extensions`)
- An Anthropic API key (for AI Assists) — optional but recommended
- An OpenAI API key (for the vector embeddings used by search + discovery) — optional but recommended

## Installation

### Quick Start

```bash
# Clone the repository
git clone https://github.com/K2412/Zettle.git
cd Zettle

# Install PHP and Node dependencies
composer install
npm install

# Bootstrap environment + database
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# Build frontend assets
npm run build
```

A `composer run setup` script wraps the environment + database bootstrap if you'd rather run it in one step.

### Configure AI + Embeddings (optional)

Edit `.env` and set the provider keys you have:

```env
ANTHROPIC_API_KEY=sk-ant-...    # AI Assists
OPENAI_API_KEY=sk-...           # vector embeddings (search + discovery)
```

**sqlite-vec** is auto-discovered from `database/extensions/vec0.*`; to point elsewhere, set `SQLITE_VEC_EXTENSION_PATH` to the extension's absolute path. Embeddings use OpenAI `text-embedding-3-small` (1536-dim) by default.

If no keys are set, embedding + assist features stand down gracefully — the rest of the app keeps working. In `local`/`testing`, a deterministic fake stands in so the suite runs without network.

### Development Server

```bash
# Everything at once (server, queue, logs, Vite):
composer run dev

# …or run them separately:
php artisan serve      # Laravel server
npm run dev            # Vite dev server (React HMR)
```

Visit `http://localhost:8000` in your browser.

## Configuration

Key environment variables in `.env`:

```env
APP_NAME=Zettle
APP_URL=http://localhost:8000

# Database (SQLite is default)
DB_CONNECTION=sqlite

# Search — the built-in database driver needs no external services
SCOUT_DRIVER=database
```

The `database` Scout driver is zero-dependency and the default. Meilisearch remains an option if keyword relevance ever needs it (see the ADRs).

## Usage

### Creating Notes
1. Open the notes index and type a title into the **New note** field.
2. Hit **Create** — you land straight in the editor.
3. Changes auto-save shortly after you stop typing, with a live status indicator.

### Linking Notes
Type `[[` in the editor to open the autocomplete popover — keep typing and it filters live against your existing notes. Links render as styled anchors in preview mode, and the target note shows the connection as a backlink.

### Typed Connections
From a note's **Connections** panel, connect it to another with a chosen relationship (supports, contradicts, depends on, …) and an optional rationale. The other note automatically shows the inverse ("supported by") — one stored edge, read from both ends.

### Finding Connections (AI)
Click **Find connections** to surface semantically-similar notes you haven't linked yet, then assert a connection to any of them in one step.

### AI Assists
Open a note's **assist panel** to see the suggested next move for it. Run **Atomize** on a sprawling note to break it into atomic candidate notes — accept the ones you want and each becomes its own permanent note, linked back to the origin. The origin is never rewritten.

### Viewing the Graph
Click **Graph** in the sidebar to see your whole note network as an interactive force-directed canvas. Drag nodes to rearrange; click one to open that note.

### Managing Tags
Attach, detach, or create tags inline on a note. On the **Tags** page, rename a tag, recolor it (any hex), delete it, or **merge** two tags into one — every note on the source moves to the target.

## Project Structure

```
zettle/
├── app/
│   ├── Actions/                # Invokable domain actions (DeleteNote, MergeTags, …)
│   ├── Ai/                     # AI agents (assists) + provider wiring
│   ├── Enums/                  # NoteType, Relationship, Phase, …
│   ├── Http/
│   │   ├── Controllers/        # Thin controllers returning Inertia::render(...)
│   │   ├── Requests/           # Form Requests (validation + authorization)
│   │   └── Middleware/         # HandleInertiaRequests (shared props)
│   ├── Jobs/                   # EmbedNoteJob, …
│   ├── Models/                 # Note, Tag, Connection, User, …
│   ├── Policies/               # NotePolicy, TagPolicy, …
│   └── Services/               # Domain logic — Note (+ Embedding / Graph / Search / Assists), Tag
├── resources/
│   └── js/
│       ├── pages/              # One file per Inertia page (notes/, tags/, settings/, …)
│       ├── components/         # Reusable components + ui/ (shadcn primitives)
│       ├── hooks/              # Client-only logic (use-appearance, use-flash-toast, …)
│       ├── layouts/            # App / auth / settings layouts
│       ├── routes/  actions/   # Wayfinder-generated typed helpers
│       └── types/              # Shared TypeScript types
├── routes/
│   ├── web.php  notes.php  tags.php  settings.php
├── database/
│   ├── migrations/             # notes, connections, tags, note_tag, note_embeddings (vec0), …
│   └── factories/
├── docs/
│   ├── architecture-inertia.md # The Laravel + Inertia + React architecture rulebook
│   └── adr/                    # Architecture Decision Records
└── tests/                      # Pest — Feature, Unit, and Browser
```

## Architecture

The strict architecture rules for this codebase live in [`docs/architecture-inertia.md`](docs/architecture-inertia.md). Two rules carry the whole thing:

1. **Inertia props are server-owned truth; React state is client-owned.**
   Server-meaningful, persisted, or validated state is a prop, changed by *visiting a route* — never by mutating a prop in place. Ephemeral, presentational state (modal open/closed, active tab, drag position) lives in React and never crosses the wire.

2. **Controllers are thin dispatchers.**
   A controller authorizes, gathers props (delegating to services), and returns `Inertia::render(...)` or a redirect. Validation and authorization live in Form Requests and policies; business logic lives in `app/Services/` and `app/Actions/`.

## Contributing

Contributions are welcome!

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Development Guidelines
- Follow the existing code style (Pint for PHP, ESLint + Prettier for TS)
- Write Pest tests for new features (feature + browser where it touches the UI)
- Read [`docs/architecture-inertia.md`](docs/architecture-inertia.md) before adding a controller, page, or service
- Update documentation as needed

### Running Tests

```bash
# Run all tests (compact output)
php artisan test --compact

# Filter to a single test
php artisan test --compact --filter=TagMergeEndpointTest
```

### Code Quality

```bash
vendor/bin/pint        # format PHP
npm run lint           # lint + fix JS/TS
npm run types          # TypeScript type-check
```

## Roadmap

- [ ] The remaining AI Assists (Triage, Formulate, Connect, Make Findable, Structure, Cluster)
- [ ] AI tag suggestions drawn from your existing taxonomy
- [ ] Import from Obsidian / Roam
- [ ] Export to markdown
- [ ] Mobile-first writing experience
- [ ] Self-hosted sync

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Acknowledgments

- Inspired by the Zettelkasten method pioneered by Niklas Luhmann
- Built with [Laravel](https://laravel.com), [Inertia](https://inertiajs.com), [React](https://react.dev), and [Tailwind CSS](https://tailwindcss.com)
- UI on [shadcn/ui](https://ui.shadcn.com); vector search by [sqlite-vec](https://github.com/asg017/sqlite-vec)

---

<p align="center">
  Made for thinkers
</p>

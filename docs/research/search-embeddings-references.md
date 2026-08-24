# Research — Search + embeddings (Laravel AI SDK + embedding advancements)

Web research for slice 4. The headline: the current **Laravel AI SDK** (`laravel/ai`, Laravel 13.x)
added first-class vector storage + similarity search — but **only on PostgreSQL + pgvector**. The
source app predates this and hand-rolled everything on **sqlite-vec**. That's the load-bearing fork.

Sources:
- [Laravel AI SDK docs (13.x)](https://laravel.com/docs/13.x/ai-sdk)
- [Introducing the Laravel AI SDK (blog)](https://laravel.com/blog/introducing-the-laravel-ai-sdk)
- [laravel/ai on GitHub](https://github.com/laravel/ai) · [Embeddings on DeepWiki](https://deepwiki.com/laravel/ai/7.5-embeddings)
- Embedding-model comparisons 2026: [Milvus](https://milvus.io/blog/choose-embedding-model-rag-2026.md) · [aimultiple](https://aimultiple.com/embedding-models) · [openxcell](https://www.openxcell.com/blog/best-embedding-models)

## Laravel AI SDK — embeddings API (current)

```php
use Laravel\Ai\Embeddings;

$response = Embeddings::for([$note->title."\n\n".$note->body])
    ->dimensions(1536)
    ->cache()                     // ai.caching.embeddings; 30-day default
    ->generate(Lab::OpenAI, 'text-embedding-3-small');

$response->embeddings; // [[0.12, 0.45, ...]]
// Stringable sugar: Str::of($text)->toEmbeddings();
```

- **Providers:** OpenAI, OpenAI-compatible, Gemini, Azure, Bedrock, Cohere, Mistral, Jina, VoyageAI, Ollama, OpenRouter. `dimensions()` supports Matryoshka-style truncation. Multimodal via Gemini / VoyageAI (image/audio/video/PDF).
- **This is what the source already uses** (`NoteEmbeddingService` calls `Embeddings::for(...)->dimensions()->generate(model:)`), so generation ports almost unchanged — only the SDK version + config differ.

## The advancement — native vector storage + query (pgvector only)

New in the SDK vs the source's v0.8.1: you no longer hand-roll KNN SQL. On **PostgreSQL + pgvector**:

```php
// migration
Schema::ensureVectorExtensionExists();
$table->vector('embedding', dimensions: 1536)->index();  // HNSW, cosine

// query — the whole knn() hand-roll collapses to this
Note::query()
    ->whereVectorSimilarTo('embedding', $queryVector, minSimilarity: 0.4)
    ->limit(10)->get();
// also: orderByVectorDistance(), whereVectorDistanceLessThan(), selectVectorDistance()
// plus a SimilaritySearch agent tool for RAG
```

**Critical limitation:** these vector query builders are **PostgreSQL + pgvector only**. SQLite and
other drivers are not supported natively.

## What the source did (sqlite-vec, hand-rolled)

`config('database.connections.sqlite.extensions.vec')` loads the **sqlite-vec** native extension on
connect (`SqliteVecServiceProvider`), a `vec0` virtual table (`note_embeddings`), and raw SQL:
`INSERT OR REPLACE ... vec_f32(?)` to store, `WHERE embedding MATCH ? AND k = ? ORDER BY distance`
for KNN. Works, stays single-file/local-first, but: needs a native extension present at runtime (the
migration even logs-and-skips when it's missing), and none of the SDK's vector niceties apply.

## The fork (this dominates the grill)

| Option | Vector store | Uses SDK's native vector API? | DB change | Local-first? |
|---|---|---|---|---|
| **A. Postgres + pgvector** | pgvector | ✅ yes — `whereVectorSimilarTo`, `$table->vector()`, SimilaritySearch tool | sqlite → **postgres** | ✗ (needs a server) |
| **B. sqlite-vec (port source)** | sqlite-vec ext | ✗ SDK only generates vectors; store/query hand-rolled | none (stays sqlite) | ✓ single-file |
| **C. Meilisearch hybrid** | Meili vector+full-text | ✗ (its own engine) | none, + Meili service | ✗ (needs a server) |

- **A** best leverages the SDK you asked for and drops all hand-rolled SQL, but abandons the app's sqlite local-first stance for a Postgres server.
- **B** is the faithful port and keeps local-first, but forgoes the new SDK vector features and carries the native-extension deployment friction.
- **C** could also upgrade full-text search (currently Scout `database` driver) to Meilisearch and do hybrid semantic+keyword in one engine — but it's a separate service and doesn't touch pgvector.

## Embedding model choice (2026)

- **OpenAI `text-embedding-3-small`** (1536-dim, cheap, strong English retrieval) — the source's default; pragmatic starting point. `-3-large` (3072) supports Matryoshka truncation down to ~256 with little loss.
- **Gemini Embedding 2** — best all-rounder in 2026 benchmarks, cross-lingual + multimodal (768-dim).
- **Voyage** — best for code/technical text; strong dimension compression (MRL).
- For English note prose + cost, `text-embedding-3-small` is the sensible default; the SDK's `dimensions()` lets us trade storage vs quality later.

## Full-text search (currently Scout `database` driver)

Independent question the slice may fold in: keep the DB driver, or upgrade to Meilisearch (which also
unlocks vector/hybrid — option C). ADR-0001 deferred Meilisearch to "the search/embeddings slice" —
i.e. now. So "do we upgrade full-text search too?" is on the table.

## Open questions for the grill

1. **Vector store: A (pg+pgvector, SDK-native) vs B (sqlite-vec, faithful/local-first) vs C (Meili hybrid).** The load-bearing call — trades the SDK's new features against the app's sqlite local-first identity.
2. **Full-text search: keep Scout `database` driver, or upgrade to Meilisearch now?**
3. **Embedding provider/model + dimensions** (OpenAI 3-small default? Gemini? Voyage?) + which API key.
4. **Generation trigger:** the queued `EmbedNoteJob` on note save is already stubbed — wire it to the SDK. Backfill existing notes?
5. **Scope:** ship the embeddings-powered **discovery modal** (the source's "Find connections") in this slice, or keep it to search+embeddings infra and wire the modal separately?

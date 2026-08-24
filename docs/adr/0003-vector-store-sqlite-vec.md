# Vector store: sqlite-vec, not the SDK's pgvector

Context: the Search + embeddings slice adds note embeddings and semantic search. The current Laravel
AI SDK ships native vector storage + similarity search (`Schema::vector()`, `whereVectorSimilarTo()`,
a `SimilaritySearch` tool) — but only on **PostgreSQL + pgvector**. The app runs on SQLite and is
deliberately local-first / single-file.

## Decision

Store embeddings with **sqlite-vec** — a `vec0` virtual table (`note_embeddings`) and the native
extension loaded on connect, with hand-rolled KNN SQL (`… embedding MATCH ? AND k = ? ORDER BY
distance`), porting the source app's approach. The **Laravel AI SDK is used for generation only**
(`Embeddings::for(...)->dimensions(1536)->generate(model: 'text-embedding-3-small')`).

## Rejected

- **Postgres + pgvector (the SDK's native path)** — the cleanest code and the SDK's headline new
  feature, but it forces an **app-wide** `DB_CONNECTION` change from sqlite to postgres (every
  migration, dev setup, deployment) and abandons the local-first / single-file identity the app is
  built around. Too large a change to smuggle into an embeddings slice, and against the product stance.
- **Meilisearch hybrid** — one engine for full-text + vector, but a separate server service, again
  cutting against local-first; full-text stays on the Scout `database` driver instead.

## Consequences

The sqlite-vec **native extension is hard-required** (installed in CI); without it the vector table
can't be created and embeddings fail — there is no silent-skip fallback. We forgo the SDK's vector
query builders and keep hand-rolled KNN SQL, which is the price of staying on SQLite. If the app ever
outgrows single-file SQLite, revisiting this for pgvector is a data + infra migration, not a config
flip — hence this record. Full-text keyword search stays on the Scout `database` driver; the note
search box blends it with sqlite-vec KNN via reciprocal rank fusion.

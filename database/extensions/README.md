# sqlite-vec extension

Semantic search stores note embeddings in a [sqlite-vec](https://github.com/asg017/sqlite-vec)
`vec0` virtual table. The native extension is **hard-required** (see
`docs/adr/0003-vector-store-sqlite-vec.md`): without it the `note_embeddings`
table can't be created and embeddings fail — there is no silent-skip fallback.

The binary is platform-specific and **not committed** (it is gitignored). Each
machine drops its own `vec0.*` here; `config/database.php` auto-discovers
`database/extensions/vec0.*` when `SQLITE_VEC_EXTENSION_PATH` is unset.

## Install locally

Download the loadable release for your platform from
<https://github.com/asg017/sqlite-vec/releases> (stable `v0.1.6`) and extract
the `vec0.*` file into this directory. For example, on Linux x86_64:

```sh
curl -fsSL https://github.com/asg017/sqlite-vec/releases/download/v0.1.6/sqlite-vec-0.1.6-loadable-linux-x86_64.tar.gz \
  | tar -xz -C database/extensions
```

macOS arm64 users can equivalently use the `vec0.dylib` shipped in the
`sqlite-vec-darwin-arm64` npm package.

## Requirements

PHP 8.4+ is needed: its `Pdo\Sqlite` PDO subclass exposes `loadExtension`,
which `SqliteVecServiceProvider` calls on each sqlite connection. CI installs
the extension in the `tests` workflow before migrating.

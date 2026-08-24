<?php

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use PDO;
use RuntimeException;

/**
 * Loads the sqlite-vec native extension onto every sqlite connection as it opens,
 * so the vec0 virtual table and its KNN functions (vec_f32, vec_version, MATCH k)
 * are available. The extension is hard-required (ADR-0003): a configured-but-
 * unloadable extension throws rather than silently degrading semantic search.
 */
class SqliteVecServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            $connection = $event->connection;

            if ($connection->getDriverName() !== 'sqlite') {
                return;
            }

            $path = config('database.connections.sqlite.extensions.vec');

            if (! is_string($path) || $path === '') {
                return;
            }

            // Configured but unresolvable: fail loud (ADR-0003 hard-requires the
            // extension) rather than silently degrading to no vector search.
            if (! file_exists($path)) {
                throw new RuntimeException("sqlite-vec extension configured at [{$path}] but the file does not exist.");
            }

            $pdo = $connection->getPdo();

            if (! $pdo instanceof PDO || ! method_exists($pdo, 'loadExtension')) {
                throw new RuntimeException(
                    'sqlite-vec is configured but PDO::loadExtension is unavailable. '.
                    'Ensure PHP 8.4+ (Pdo\Sqlite) or a pdo_sqlite built with extension loading.',
                );
            }

            $pdo->loadExtension($path);
        });
    }
}

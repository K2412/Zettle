<?php

use App\Providers\AppServiceProvider;
use App\Providers\AssistsFakeServiceProvider;
use App\Providers\EmbeddingsFakeServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\SqliteVecServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    SqliteVecServiceProvider::class,
    EmbeddingsFakeServiceProvider::class,
    AssistsFakeServiceProvider::class,
];

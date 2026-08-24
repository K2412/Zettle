<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Which provider handles each class of AI operation when none is named on
    | the call. This app only generates embeddings (OpenAI); the other slots
    | are here so the SDK resolves cleanly if a future slice adds them.
    |
    */

    'default' => 'openai',
    'default_for_embeddings' => 'openai',

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Each provider is an API-key + driver pair the SDK can talk to. Only
    | OpenAI is wired; the key is read from config here (never env() in code).
    |
    */

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
        ],

        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Zettle Embedding Settings
    |--------------------------------------------------------------------------
    |
    | The model and dimensions the note embedding pipeline uses. sqlite-vec's
    | note_embeddings table is created at these dimensions, so changing the
    | dimension count means re-creating the table and re-embedding.
    |
    */

    'zettle' => [
        'embedding' => [
            'model' => env('ZETTLE_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'dimensions' => (int) env('ZETTLE_EMBEDDING_DIMENSIONS', 1536),
        ],
        'synthesis' => [
            'model' => env('ZETTLE_SYNTHESIS_MODEL', 'claude-sonnet-4-6'),
        ],
    ],

];

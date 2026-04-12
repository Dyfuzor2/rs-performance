<?php

declare(strict_types=1);

return [
    /*
 |--------------------------------------------------------------------------
    | Internal token for n8n → hosting API (DTC enrichment, Vertex proxy, …)
    |--------------------------------------------------------------------------
    |
    | Must match the X-API-Token header sent from n8n HTTP Request nodes.
    | Prefer setting N8N_API_TOKEN in production .env (never commit secrets).
    |
    */
    'api_token' => env('N8N_API_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | n8n public REST API (Filament sync, operator docs)
    |--------------------------------------------------------------------------
    |
    | Same credentials as Cursor `N8N_API_URL` + `N8N_API_KEY` (Settings → API).
    | Auth: send header `X-N8N-API-KEY` on requests under `/api/v1/*` (see n8n docs).
    | Used only server-side for sync + live health in Filament.
    |
    */
    'public_api' => [
        'base_url' => rtrim((string) env('N8N_API_URL', ''), '/'),
        'key' => (string) env('N8N_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Link “Otwórz w edytorze n8n” w panelu (bez sekretów)
    |--------------------------------------------------------------------------
    */
    'editor_base_url' => rtrim((string) env('N8N_EDITOR_BASE_URL', env('N8N_API_URL', '')), '/'),
];

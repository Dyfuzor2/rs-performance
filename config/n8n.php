<?php

declare(strict_types=1);

use App\Support\N8n\N8nPublicApiKeyNormalizer;

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
    | Same instance as `N8N_API_URL` + `N8N_API_KEY` (Settings → n8n API). URL must match the host where the JWT was issued (RS default: https://auto.rs3d.pl; SOCmid only if you use that second instance).
    | Same pair as Cursor n8n MCP (`.cursor/mcp.env`) after merge — one Public API, no duplicate config.
    | Auth: send header `X-N8N-API-KEY` on requests under `/api/v1/*` (see n8n docs).
    | Used only server-side for sync + live health in Filament.
    |
    */
    'public_api' => [
        'base_url' => N8nPublicApiKeyNormalizer::baseUrl(env('N8N_API_URL')),
        'key' => N8nPublicApiKeyNormalizer::apiKey(env('N8N_API_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Link “Otwórz w edytorze n8n” w panelu (bez sekretów)
    |--------------------------------------------------------------------------
    */
    'editor_base_url' => N8nPublicApiKeyNormalizer::baseUrl(
        env('N8N_EDITOR_BASE_URL') ?: env('N8N_API_URL')
    ),
];

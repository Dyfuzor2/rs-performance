<?php

declare(strict_types=1);

/**
 * Config must not reference App\* classes: configuration files load before the
 * container is ready, and some bootstrap paths resolve autoload differently than
 * HTTP/Artisan. Logic is kept in sync with `App\Support\N8n\N8nPublicApiKeyNormalizer`.
 */
$normalizeN8nBaseUrl = static function (?string $value): string {
    $s = trim((string) $value);
    if (str_starts_with($s, "\xEF\xBB\xBF")) {
        $s = substr($s, 3);
    }

    return rtrim($s, '/');
};

$normalizeN8nApiKey = static function (?string $value): string {
    $s = trim((string) $value);
    if (str_starts_with($s, "\xEF\xBB\xBF")) {
        $s = substr($s, 3);
    }

    if ($s === '') {
        return '';
    }

    $s = rtrim($s, ';');

    if (
        (str_starts_with($s, '"') && str_ends_with($s, '"'))
        || (str_starts_with($s, "'") && str_ends_with($s, "'"))
    ) {
        $s = substr($s, 1, -1);
        $s = trim($s);
    }

    return trim($s);
};

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
        'base_url' => $normalizeN8nBaseUrl(env('N8N_API_URL')),
        'key' => $normalizeN8nApiKey(env('N8N_API_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Link “Otwórz w edytorze n8n” w panelu (bez sekretów)
    |--------------------------------------------------------------------------
    */
    'editor_base_url' => $normalizeN8nBaseUrl(
        env('N8N_EDITOR_BASE_URL') ?: env('N8N_API_URL')
    ),

    /*
    |--------------------------------------------------------------------------
    | n8n-mcp HTTP (VPS support plane — Cursor / agent tools)
    |--------------------------------------------------------------------------
    |
    | Filament pokazuje osobny ping zdrowia MCP (bez sekretów). Używaj pełnego
    | URL /health zgodnego z Caddy (np. https://n8n-mcp.rs3d.pl/health).
    |
    */
    'mcp_http' => [
        'health_url' => env('N8N_MCP_HEALTH_URL', 'https://n8n-mcp.rs3d.pl/health'),
        'public_label' => env('N8N_MCP_PUBLIC_LABEL', 'n8n-mcp.rs3d.pl'),
    ],
];

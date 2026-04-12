<?php

declare(strict_types=1);

namespace App\Support\N8n;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Live probe for n8n Public REST API (Settings → API key, header X-N8N-API-KEY).
 *
 * @see https://docs.n8n.io/api/authentication/
 */
final class N8nPublicApiHealthService
{
    private const CACHE_TTL_SECONDS = 20;

    private const CACHE_KEY = 'n8n_public_api_health_snapshot_v2';

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     latency_ms: ?float,
     *     http_status: ?int,
     *     workflows_sample_count: ?int,
     *     health_ok: ?bool,
     *     base_url: string,
     *     env_configured: bool,
     *     api_message: ?string,
     *     hints: list<string>,
     *     mcp_health_ok: ?bool,
     *     mcp_latency_ms: ?float,
     *     mcp_label: string,
     * }
     */
    public function snapshot(): array
    {
        $base = rtrim((string) config('n8n.public_api.base_url'), '/');
        $key = (string) config('n8n.public_api.key');
        $envConfigured = $base !== '' && $key !== '';

        if (! $envConfigured) {
            return [
                'ok' => false,
                'message' => 'Uzupełnij N8N_API_URL i N8N_API_KEY w .env (Settings → API w n8n).',
                'latency_ms' => null,
                'http_status' => null,
                'workflows_sample_count' => null,
                'health_ok' => null,
                'base_url' => $base,
                'env_configured' => false,
                'api_message' => null,
                'hints' => [],
                'mcp_health_ok' => null,
                'mcp_latency_ms' => null,
                'mcp_label' => (string) config('n8n.mcp_http.public_label'),
            ];
        }

        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->probeUncached($base, $key)
        );
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     latency_ms: ?float,
     *     http_status: ?int,
     *     workflows_sample_count: ?int,
     *     health_ok: ?bool,
     *     base_url: string,
     *     env_configured: bool,
     *     api_message: ?string,
     *     hints: list<string>
     * }
     */
    public function probeFresh(): array
    {
        Cache::forget(self::CACHE_KEY);

        $base = rtrim((string) config('n8n.public_api.base_url'), '/');
        $key = (string) config('n8n.public_api.key');

        if ($base === '' || $key === '') {
            return $this->snapshot();
        }

        return $this->probeUncached($base, $key);
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     latency_ms: ?float,
     *     http_status: ?int,
     *     workflows_sample_count: ?int,
     *     health_ok: ?bool,
     *     base_url: string,
     *     env_configured: bool,
     *     api_message: ?string,
     *     hints: list<string>,
     *     mcp_health_ok: ?bool,
     *     mcp_latency_ms: ?float,
     *     mcp_label: string,
     * }
     */
    private function probeUncached(string $base, string $key): array
    {
        $mcpLabel = (string) config('n8n.mcp_http.public_label');
        $mcpHealthUrl = trim((string) config('n8n.mcp_http.health_url'));
        $mcpProbe = $this->probeMcpHttpHealth($mcpHealthUrl);

        $healthOk = null;
        try {
            $health = Http::timeout(6)->get($base . '/healthz');
            $healthOk = $health->successful();
        } catch (Throwable) {
            $healthOk = false;
        }

        try {
            $t0 = microtime(true);
            $response = Http::timeout(25)
                ->withHeaders(['X-N8N-API-KEY' => $key])
                ->acceptJson()
                ->get($base . '/api/v1/workflows', ['limit' => 5]);
            $latency = (microtime(true) - $t0) * 1000.0;
            $status = $response->status();
            $rawPayload = $response->json();
            $payload = is_array($rawPayload) ? $rawPayload : null;
            $rows = [];
            if (is_array($payload)) {
                $rows = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
            }
            if (! is_array($rows)) {
                $rows = [];
            }
            $sampleCount = count($rows);

            $apiMessage = $this->extractApiMessage($payload);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => sprintf('REST API v1 OK · próbka %d workflowów', $sampleCount ?? 0),
                    'latency_ms' => round($latency, 1),
                    'http_status' => $status,
                    'workflows_sample_count' => $sampleCount,
                    'health_ok' => $healthOk,
                    'base_url' => $base,
                    'env_configured' => true,
                    'api_message' => $apiMessage,
                    'hints' => [],
                    'mcp_health_ok' => $mcpProbe['ok'],
                    'mcp_latency_ms' => $mcpProbe['latency_ms'],
                    'mcp_label' => $mcpLabel,
                ];
            }

            $hints = $this->hintsForStatus($status, $apiMessage, $base);

            $message = sprintf('API zwróciło HTTP %d (sprawdź klucz i URL instancji).', $status);
            if ($apiMessage !== null && $apiMessage !== '') {
                $message .= ' · ' . $apiMessage;
            }

            return [
                'ok' => false,
                'message' => $message,
                'latency_ms' => round($latency, 1),
                'http_status' => $status,
                'workflows_sample_count' => $sampleCount,
                'health_ok' => $healthOk,
                'base_url' => $base,
                'env_configured' => true,
                'api_message' => $apiMessage,
                'hints' => $hints,
                'mcp_health_ok' => $mcpProbe['ok'],
                'mcp_latency_ms' => $mcpProbe['latency_ms'],
                'mcp_label' => $mcpLabel,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Brak połączenia z n8n: ' . $e->getMessage(),
                'latency_ms' => null,
                'http_status' => null,
                'workflows_sample_count' => null,
                'health_ok' => $healthOk,
                'base_url' => $base,
                'env_configured' => true,
                'api_message' => null,
                'hints' => [],
                'mcp_health_ok' => $mcpProbe['ok'],
                'mcp_latency_ms' => $mcpProbe['latency_ms'],
                'mcp_label' => $mcpLabel,
            ];
        }
    }

    /**
     * @return array{ok: ?bool, latency_ms: ?float}
     */
    private function probeMcpHttpHealth(string $healthUrl): array
    {
        if ($healthUrl === '' || ! str_starts_with($healthUrl, 'http')) {
            return ['ok' => null, 'latency_ms' => null];
        }

        try {
            $t0 = microtime(true);
            $r = Http::timeout(5)->get($healthUrl);
            $latency = (microtime(true) - $t0) * 1000.0;

            return [
                'ok' => $r->successful(),
                'latency_ms' => round($latency, 1),
            ];
        } catch (Throwable) {
            return ['ok' => false, 'latency_ms' => null];
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractApiMessage(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        if (isset($payload['message']) && is_string($payload['message'])) {
            return $payload['message'];
        }

        if (isset($payload['error']) && is_string($payload['error'])) {
            return $payload['error'];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function hintsForStatus(int $status, ?string $apiMessage, string $baseUrl): array
    {
        if ($status === 401) {
            return [
                'Klucz musi pochodzić z tej samej instancji co N8N_API_URL (' . $baseUrl . '): zaloguj się → Ustawienia → n8n API → Utwórz klucz → skopiuj cały JWT w jednej linii.',
                'Jeśli 401 jest także przy curl bezpośrednio do n8n (port 5678 na VPS), JWT nie jest zapisany w bazie tej instancji — usuń stare klucze w UI i utwórz nowy, potem merge do .env.',
                'Po zmianie .env na hostingu: php85 artisan config:clear (bez tego Laravel może trzymać stary klucz).',
                'Reverse proxy (Caddy/nginx) zwykle nie jest winny, gdy 401 występuje już na localhost:5678 — wtedy problem to wyłącznie klucz lub baza n8n.',
            ];
        }

        if ($status === 403) {
            return [
                'Sprawdź na serwerze n8n: N8N_PUBLIC_API_DISABLED nie może być true (docs.n8n.io/hosting/securing/disable-public-api).',
            ];
        }

        if ($apiMessage !== null && $apiMessage !== '') {
            return [$apiMessage];
        }

        return [];
    }
}

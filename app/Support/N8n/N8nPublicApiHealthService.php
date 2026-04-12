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

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     latency_ms: ?float,
     *     http_status: ?int,
     *     workflows_sample_count: ?int,
     *     health_ok: ?bool,
     *     base_url: string,
     *     env_configured: bool
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
            ];
        }

        return Cache::remember(
            'n8n_public_api_health_snapshot_v1',
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
     *     env_configured: bool
     * }
     */
    public function probeFresh(): array
    {
        Cache::forget('n8n_public_api_health_snapshot_v1');

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
     *     env_configured: bool
     * }
     */
    private function probeUncached(string $base, string $key): array
    {
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
            $payload = $response->json();
            $rows = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);
            $sampleCount = is_array($rows) ? count($rows) : null;

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
                ];
            }

            return [
                'ok' => false,
                'message' => sprintf('API zwróciło HTTP %d (sprawdź klucz i URL instancji).', $status),
                'latency_ms' => round($latency, 1),
                'http_status' => $status,
                'workflows_sample_count' => $sampleCount,
                'health_ok' => $healthOk,
                'base_url' => $base,
                'env_configured' => true,
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
            ];
        }
    }
}

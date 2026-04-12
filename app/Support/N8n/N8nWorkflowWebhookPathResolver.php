<?php

declare(strict_types=1);

namespace App\Support\N8n;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads workflow JSON from n8n Public API and lists Webhook trigger paths.
 */
final class N8nWorkflowWebhookPathResolver
{
    /**
     * @return array{
     *     fetch_status: 'ok'|'http_error'|'exception'|'invalid_body',
     *     fetch_http_status?: int,
     *     candidates: list<array{path: string, http_method: string}>,
     *     public_hint: string
     * }
     */
    public function analyzeWebhooks(string $base, string $key, string $workflowId): array
    {
        $workflowId = trim($workflowId);
        if ($workflowId === '' || $key === '') {
            return [
                'fetch_status' => 'exception',
                'candidates' => [],
                'public_hint' => 'Brak ID workflow lub klucza API — nie można pobrać definicji z n8n.',
            ];
        }

        $base = rtrim($base, '/');
        $url = $base . '/api/v1/workflows/' . rawurlencode($workflowId);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'X-N8N-API-KEY' => $key,
                    'Accept' => 'application/json',
                ])
                ->get($url);
        } catch (Throwable) {
            return [
                'fetch_status' => 'exception',
                'candidates' => [],
                'public_hint' => 'Błąd sieci przy GET definicji workflow z n8n.',
            ];
        }

        if (! $response->successful()) {
            return [
                'fetch_status' => 'http_error',
                'fetch_http_status' => $response->status(),
                'candidates' => [],
                'public_hint' => sprintf(
                    'GET /api/v1/workflows/{id} zwróciło HTTP %d — sprawdź ID workflow, N8N_API_URL i uprawnienia klucza API (np. workflow:read).',
                    $response->status(),
                ),
            ];
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [
                'fetch_status' => 'invalid_body',
                'candidates' => [],
                'public_hint' => 'Odpowiedź n8n nie jest poprawnym JSON (definicja workflow).',
            ];
        }

        $nodesResult = $this->extractNodesList($payload);
        if ($nodesResult === null) {
            return [
                'fetch_status' => 'ok',
                'candidates' => [],
                'public_hint' => 'Odpowiedź API nie zawiera tablicy nodes (niektóre wersje n8n lub ograniczony klucz API) — uzupełnij „Ścieżkę ręcznego webhooka” albo zaktualizuj n8n / scope klucza.',
            ];
        }

        if ($nodesResult === []) {
            return [
                'fetch_status' => 'ok',
                'candidates' => [],
                'public_hint' => 'Workflow ma pustą listę węzłów (nodes) — nie da się wykryć webhooka.',
            ];
        }

        $candidates = [];
        $activeWebhookNodes = 0;
        foreach ($nodesResult as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['disabled'] ?? false) === true) {
                continue;
            }

            $type = (string) ($node['type'] ?? '');
            if (! $this->isWebhookTriggerNode($type)) {
                continue;
            }

            $activeWebhookNodes++;

            $path = $this->extractWebhookPath($node);
            if ($path === null || $path === '') {
                continue;
            }

            if (! $this->isSafeWebhookPathSegment($path)) {
                continue;
            }

            $candidates[] = [
                'path' => $path,
                'http_method' => $this->extractHttpMethod($node),
            ];
        }

        if ($candidates === [] && $activeWebhookNodes > 0) {
            return [
                'fetch_status' => 'ok',
                'candidates' => [],
                'public_hint' => 'Jest aktywny węzeł Webhook, ale ścieżka jest pusta, wyrażeniem ({{…}}) albo zawiera niedozwolone znaki — w n8n ustaw stałą ścieżkę (np. rs-moj-flow) albo wklej pełny Production URL / fragment po /webhook/ w polu „Ścieżka ręcznego webhooka”.',
            ];
        }

        if ($candidates === []) {
            return [
                'fetch_status' => 'ok',
                'candidates' => [],
                'public_hint' => 'W grafie nie ma aktywnego węzła Webhook (tylko harmonogram, inne triggery lub wyłączony Webhook). Dodaj Webhook do ręcznego startu, wpisz ścieżkę w rekordzie albo uruchom workflow w edytorze n8n.',
            ];
        }

        return [
            'fetch_status' => 'ok',
            'candidates' => $candidates,
            'public_hint' => '',
        ];
    }

    /**
     * @return array{path: string, http_method: string}|null
     */
    public function resolve(string $base, string $key, string $workflowId): ?array
    {
        $analysis = $this->analyzeWebhooks($base, $key, $workflowId);
        $first = $analysis['candidates'][0] ?? null;

        return $first;
    }

    private function isWebhookTriggerNode(string $type): bool
    {
        if ($type === 'n8n-nodes-base.webhook' || $type === '@n8n/n8n-nodes-base.webhook') {
            return true;
        }

        return str_ends_with($type, 'nodes-base.webhook');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<mixed>|null null = brak / nieprawidłowa struktura nodes
     */
    private function extractNodesList(array $payload): ?array
    {
        if (array_key_exists('nodes', $payload)) {
            if (! is_array($payload['nodes'])) {
                return null;
            }

            return array_values($payload['nodes']);
        }

        $data = $payload['data'] ?? null;
        if (is_array($data) && array_key_exists('nodes', $data)) {
            if (! is_array($data['nodes'])) {
                return null;
            }

            return array_values($data['nodes']);
        }

        $workflow = $payload['workflow'] ?? null;
        if (is_array($workflow) && array_key_exists('nodes', $workflow)) {
            if (! is_array($workflow['nodes'])) {
                return null;
            }

            return array_values($workflow['nodes']);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function extractWebhookPath(array $node): ?string
    {
        $params = $node['parameters'] ?? null;
        if (is_array($params) && isset($params['path']) && is_string($params['path'])) {
            $p = trim($params['path']);
            if ($this->looksLikeExpression($p)) {
                return null;
            }

            if ($p !== '') {
                return $p;
            }
        }

        if (isset($node['webhookId']) && is_string($node['webhookId'])) {
            $w = trim($node['webhookId']);
            if ($this->looksLikeExpression($w)) {
                return null;
            }

            if ($w !== '') {
                return $w;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function extractHttpMethod(array $node): string
    {
        $params = $node['parameters'] ?? null;
        if (is_array($params) && isset($params['httpMethod']) && is_string($params['httpMethod'])) {
            $m = strtoupper(trim($params['httpMethod']));

            return $m !== '' ? $m : 'POST';
        }

        return 'POST';
    }

    private function looksLikeExpression(string $value): bool
    {
        if (str_contains($value, '{{')) {
            return true;
        }

        return str_starts_with($value, '=');
    }

    private function isSafeWebhookPathSegment(string $path): bool
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        if (str_contains($path, '..')) {
            return false;
        }

        /** Colon allowed — n8n route params, e.g. orders/:id */
        return preg_match('#^[a-zA-Z0-9/_\-.:+]+$#', $path) === 1;
    }
}

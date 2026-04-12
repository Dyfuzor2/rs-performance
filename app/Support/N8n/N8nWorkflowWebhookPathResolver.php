<?php

declare(strict_types=1);

namespace App\Support\N8n;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads workflow JSON from n8n Public API and finds the first active Webhook trigger path.
 */
final class N8nWorkflowWebhookPathResolver
{
    private const WEBHOOK_NODE_TYPE = 'n8n-nodes-base.webhook';

    /**
     * @return array{path: string, http_method: string}|null
     */
    public function resolve(string $base, string $key, string $workflowId): ?array
    {
        $workflowId = trim($workflowId);
        if ($workflowId === '' || $key === '') {
            return null;
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
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return null;
        }

        $nodes = $this->extractNodes($payload);
        if ($nodes === null) {
            return null;
        }

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['disabled'] ?? false) === true) {
                continue;
            }

            $type = (string) ($node['type'] ?? '');
            if ($type !== self::WEBHOOK_NODE_TYPE) {
                continue;
            }

            $path = $this->extractWebhookPath($node);
            if ($path === null || $path === '') {
                continue;
            }

            if (! $this->isSafeWebhookPathSegment($path)) {
                continue;
            }

            $httpMethod = $this->extractHttpMethod($node);

            return [
                'path' => $path,
                'http_method' => $httpMethod,
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<mixed>|null
     */
    private function extractNodes(array $payload): ?array
    {
        if (isset($payload['nodes']) && is_array($payload['nodes'])) {
            /** @var list<mixed> $nodes */
            $nodes = $payload['nodes'];

            return $nodes;
        }

        $data = $payload['data'] ?? null;
        if (is_array($data) && isset($data['nodes']) && is_array($data['nodes'])) {
            /** @var list<mixed> $nodes */
            $nodes = $data['nodes'];

            return $nodes;
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

        return preg_match('#^[a-zA-Z0-9/_\-.]+$#', $path) === 1;
    }
}

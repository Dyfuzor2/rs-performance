<?php

declare(strict_types=1);

namespace App\Support\N8n;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Triggers workflow execution via n8n Public REST API, with optional webhook fallback.
 *
 * @see https://docs.n8n.io/api/
 */
final class N8nWorkflowExecuteService
{
    public function __construct(
        private readonly N8nWorkflowWebhookPathResolver $webhookPathResolver,
    ) {}

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     http_status: ?int,
     *     execution_id: string|int|null,
     *     waiting_for_webhook: ?bool,
     *     api_message: ?string
     * }
     */
    public function execute(
        string $workflowId,
        ?string $manualWebhookPath = null,
        bool $manualWebhookUseTestUrl = false,
    ): array {
        $workflowId = trim($workflowId);
        $webhookPath = $manualWebhookPath !== null ? trim($manualWebhookPath) : '';

        if ($workflowId === '' && $webhookPath === '') {
            return [
                'ok' => false,
                'message' => 'Brak sposobu uruchomienia — uzupełnij „ID workflow w n8n” albo „Ścieżkę ręcznego webhooka”.',
                'http_status' => null,
                'execution_id' => null,
                'waiting_for_webhook' => null,
                'api_message' => null,
            ];
        }

        $base = rtrim((string) config('n8n.public_api.base_url'), '/');
        $key = (string) config('n8n.public_api.key');

        if ($base === '') {
            return [
                'ok' => false,
                'message' => 'Uzupełnij N8N_API_URL w .env (Public API / ten sam host co webhooki).',
                'http_status' => null,
                'execution_id' => null,
                'waiting_for_webhook' => null,
                'api_message' => null,
            ];
        }

        if ($workflowId !== '' && $key === '') {
            return [
                'ok' => false,
                'message' => 'Uzupełnij N8N_API_KEY w .env (Settings → API w n8n), aby wywołać Public API execute.',
                'http_status' => null,
                'execution_id' => null,
                'waiting_for_webhook' => null,
                'api_message' => null,
            ];
        }

        if ($workflowId !== '') {
            $apiResult = $this->postPublicApiExecute($base, $key, $workflowId);
            if ($apiResult['ok']) {
                return $apiResult;
            }

            $status = $apiResult['http_status'];
            if ($status === 404 || $status === 405) {
                if ($webhookPath !== '') {
                    $fallback = $this->postWebhook($base, $webhookPath, $manualWebhookUseTestUrl);
                    if ($fallback['ok']) {
                        return $this->mergeWebhookSuccessNote($fallback, $apiResult, false);
                    }

                    return $this->mergeExecuteAndWebhookErrors($apiResult, $fallback);
                }

                if ($this->shouldAutoResolveWebhookPath()) {
                    $resolved = $this->webhookPathResolver->resolve($base, $key, $workflowId);
                    if ($resolved !== null) {
                        $fallback = $this->postWebhook(
                            $base,
                            $resolved['path'],
                            $manualWebhookUseTestUrl,
                            $resolved['http_method'],
                        );
                        if ($fallback['ok']) {
                            return $this->mergeWebhookSuccessNote($fallback, $apiResult, true);
                        }

                        return $this->mergeExecuteAndWebhookErrors($apiResult, $fallback);
                    }
                }
            }

            return $apiResult;
        }

        return $this->postWebhook($base, $webhookPath, $manualWebhookUseTestUrl, 'POST');
    }

    private function shouldAutoResolveWebhookPath(): bool
    {
        return (bool) config('n8n.public_api.auto_resolve_webhook_path', true);
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     http_status: ?int,
     *     execution_id: string|int|null,
     *     waiting_for_webhook: ?bool,
     *     api_message: ?string
     * }
     */
    private function postPublicApiExecute(string $base, string $key, string $workflowId): array
    {
        $url = $base . '/api/v1/workflows/' . rawurlencode($workflowId) . '/execute';

        try {
            $response = $this->jsonPostWithApiKey($url, $key, '{}');

            return $this->interpretExecuteResponse($response);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Błąd połączenia z n8n: ' . $e->getMessage(),
                'http_status' => null,
                'execution_id' => null,
                'waiting_for_webhook' => null,
                'api_message' => null,
            ];
        }
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     http_status: ?int,
     *     execution_id: string|int|null,
     *     waiting_for_webhook: ?bool,
     *     api_message: ?string
     * }
     */
    private function interpretExecuteResponse(Response $response): array
    {
        $status = $response->status();
        $payload = $response->json();
        $arr = is_array($payload) ? $payload : null;

        $apiMessage = $this->extractApiMessage($arr);
        $executionId = $this->extractExecutionId($arr);
        $waiting = null;
        if (is_array($arr) && array_key_exists('waitingForWebhook', $arr)) {
            $waiting = (bool) $arr['waitingForWebhook'];
        }

        if ($response->successful()) {
            $msg = 'Workflow wyzwolony.';
            if ($executionId !== null) {
                $msg .= sprintf(' executionId: %s', (string) $executionId);
            }
            if ($waiting === true) {
                $msg .= ' · oczekuje na webhook.';
            }

            return [
                'ok' => true,
                'message' => $msg,
                'http_status' => $status,
                'execution_id' => $executionId,
                'waiting_for_webhook' => $waiting,
                'api_message' => $apiMessage,
            ];
        }

        $message = sprintf('n8n zwróciło HTTP %d.', $status);
        if ($apiMessage !== null && $apiMessage !== '') {
            $message .= ' ' . $apiMessage;
        }
        if ($status === 401) {
            $message .= ' Sprawdź N8N_API_KEY i że URL wskazuje tę samą instancję co klucz.';
        }
        if ($status === 404) {
            $message .= ' Sprawdź ID workflow lub czy instancja udostępnia POST /api/v1/workflows/{id}/execute.';
        }
        if ($status === 405) {
            $message .= ' W wielu wersjach n8n (np. 2.14–2.15) Public API nie ma endpointu execute — ustaw „Ścieżkę ręcznego webhooka” z węzła Webhook albo zaktualizuj n8n.';
        }

        return [
            'ok' => false,
            'message' => $message,
            'http_status' => $status,
            'execution_id' => null,
            'waiting_for_webhook' => null,
            'api_message' => $apiMessage,
        ];
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     http_status: ?int,
     *     execution_id: string|int|null,
     *     waiting_for_webhook: ?bool,
     *     api_message: ?string
     * }
     */
    private function postWebhook(string $base, string $path, bool $useTest, string $httpMethod = 'POST'): array
    {
        $url = $this->buildWebhookUrl($base, $path, $useTest);
        if ($url === '') {
            return [
                'ok' => false,
                'message' => 'Nieprawidłowa ścieżka webhooka.',
                'http_status' => null,
                'execution_id' => null,
                'waiting_for_webhook' => null,
                'api_message' => null,
            ];
        }

        $method = strtoupper(trim($httpMethod));
        if ($method === '' || ! in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $method = 'POST';
        }

        try {
            $response = $this->sendWebhookHttpRequest($url, $method);

            $status = $response->status();
            $payload = $response->json();
            $arr = is_array($payload) ? $payload : null;
            $apiMessage = $this->extractApiMessage($arr);
            $executionId = $this->extractExecutionId($arr);

            if ($response->successful()) {
                $msg = 'Workflow wyzwolony (webhook).';
                if ($executionId !== null) {
                    $msg .= sprintf(' executionId: %s', (string) $executionId);
                }

                return [
                    'ok' => true,
                    'message' => $msg,
                    'http_status' => $status,
                    'execution_id' => $executionId,
                    'waiting_for_webhook' => null,
                    'api_message' => $apiMessage,
                ];
            }

            $message = sprintf('Webhook zwrócił HTTP %d.', $status);
            if ($apiMessage !== null && $apiMessage !== '') {
                $message .= ' ' . $apiMessage;
            }
            $message .= sprintf(
                ' Sprawdź metodę HTTP (%s), ścieżkę z węzła Webhook i czy workflow jest aktywny (dla /webhook/…, nie webhook-test).',
                $method,
            );

            return [
                'ok' => false,
                'message' => $message,
                'http_status' => $status,
                'execution_id' => null,
                'waiting_for_webhook' => null,
                'api_message' => $apiMessage,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Błąd połączenia z webhookiem n8n: ' . $e->getMessage(),
                'http_status' => null,
                'execution_id' => null,
                'waiting_for_webhook' => null,
                'api_message' => null,
            ];
        }
    }

    private function sendWebhookHttpRequest(string $url, string $method): Response
    {
        $accept = ['Accept' => 'application/json'];

        return match ($method) {
            'GET' => Http::timeout(120)->withHeaders($accept)->get($url),
            'HEAD' => Http::timeout(120)->withHeaders($accept)->head($url),
            default => Http::timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    ...$accept,
                ])
                ->withBody('{}', 'application/json')
                ->send($method, $url),
        };
    }

    private function jsonPostWithApiKey(string $url, string $key, string $jsonBody): Response
    {
        return Http::timeout(120)
            ->withHeaders([
                'X-N8N-API-KEY' => $key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($url);
    }

    /**
     * @param  array<string, mixed>  $apiResult
     * @param  array<string, mixed>  $webhookOk
     * @return array<string, mixed>
     */
    private function mergeWebhookSuccessNote(array $webhookOk, array $apiResult, bool $fromWorkflowJson): array
    {
        $webhookOk['message'] .= sprintf(
            ' (Public API execute niedostępny — HTTP %s).',
            $apiResult['http_status'] !== null ? (string) $apiResult['http_status'] : '?'
        );
        if ($fromWorkflowJson) {
            $webhookOk['message'] .= ' Ścieżka webhooka odczytana automatycznie z definicji workflow (GET /api/v1/workflows/{id}).';
        }

        return $webhookOk;
    }

    /**
     * @param  array<string, mixed>  $apiResult
     * @param  array<string, mixed>  $webhookFail
     * @return array<string, mixed>
     */
    private function mergeExecuteAndWebhookErrors(array $apiResult, array $webhookFail): array
    {
        return [
            'ok' => false,
            'message' => $apiResult['message'] . ' Próba webhooka: ' . $webhookFail['message'],
            'http_status' => $webhookFail['http_status'] ?? $apiResult['http_status'],
            'execution_id' => null,
            'waiting_for_webhook' => null,
            'api_message' => $webhookFail['api_message'] ?? $apiResult['api_message'],
        ];
    }

    private function buildWebhookUrl(string $base, string $path, bool $useTest): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $base = rtrim($base, '/');
        $segment = $useTest ? 'webhook-test' : 'webhook';
        $suffix = ltrim($path, '/');

        return $base . '/' . $segment . '/' . $suffix;
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
     * @param  array<string, mixed>|null  $payload
     */
    private function extractExecutionId(?array $payload): string|int|null
    {
        if ($payload === null) {
            return null;
        }

        foreach (['executionId', 'execution_id', 'id'] as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }
            $v = $payload[$key];
            if (is_string($v) || is_int($v)) {
                return $v;
            }
        }

        $data = $payload['data'] ?? null;
        if (is_array($data)) {
            foreach (['executionId', 'execution_id', 'id'] as $key) {
                if (! array_key_exists($key, $data)) {
                    continue;
                }
                $v = $data[$key];
                if (is_string($v) || is_int($v)) {
                    return $v;
                }
            }
        }

        return null;
    }
}

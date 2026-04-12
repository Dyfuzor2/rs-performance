<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\N8n\N8nWorkflowWebhookPathResolver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class N8nWorkflowWebhookPathResolverTest extends TestCase
{
    public function test_returns_first_active_webhook_path_and_method(): void
    {
        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-1' => Http::response([
                'nodes' => [
                    [
                        'type' => 'n8n-nodes-base.scheduleTrigger',
                        'disabled' => false,
                    ],
                    [
                        'type' => 'n8n-nodes-base.webhook',
                        'disabled' => false,
                        'parameters' => [
                            'path' => 'hook-a',
                            'httpMethod' => 'GET',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $r = app(N8nWorkflowWebhookPathResolver::class)->resolve(
            'https://n8n.example.test',
            'k',
            'wf-1',
        );

        $this->assertSame(['path' => 'hook-a', 'http_method' => 'GET'], $r);
    }

    public function test_skips_disabled_webhook_node(): void
    {
        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-1' => Http::response([
                'nodes' => [
                    [
                        'type' => 'n8n-nodes-base.webhook',
                        'disabled' => true,
                        'parameters' => ['path' => 'skip-me', 'httpMethod' => 'POST'],
                    ],
                    [
                        'type' => 'n8n-nodes-base.webhook',
                        'disabled' => false,
                        'parameters' => ['path' => 'use-me', 'httpMethod' => 'POST'],
                    ],
                ],
            ], 200),
        ]);

        $r = app(N8nWorkflowWebhookPathResolver::class)->resolve(
            'https://n8n.example.test',
            'k',
            'wf-1',
        );

        $this->assertSame('use-me', $r['path'] ?? null);
    }

    public function test_falls_back_to_webhook_id_when_path_empty(): void
    {
        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-1' => Http::response([
                'nodes' => [
                    [
                        'type' => 'n8n-nodes-base.webhook',
                        'disabled' => false,
                        'parameters' => ['path' => '', 'httpMethod' => 'POST'],
                        'webhookId' => 'from-id',
                    ],
                ],
            ], 200),
        ]);

        $r = app(N8nWorkflowWebhookPathResolver::class)->resolve(
            'https://n8n.example.test',
            'k',
            'wf-1',
        );

        $this->assertSame('from-id', $r['path'] ?? null);
    }

    public function test_reads_nodes_nested_under_data_key(): void
    {
        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-1' => Http::response([
                'data' => [
                    'nodes' => [
                        [
                            'type' => 'n8n-nodes-base.webhook',
                            'disabled' => false,
                            'parameters' => ['path' => 'nested', 'httpMethod' => 'POST'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $r = app(N8nWorkflowWebhookPathResolver::class)->resolve(
            'https://n8n.example.test',
            'k',
            'wf-1',
        );

        $this->assertSame('nested', $r['path'] ?? null);
    }

    public function test_returns_null_on_failed_get(): void
    {
        Http::fake([
            'https://n8n.example.test/api/v1/workflows/missing' => Http::response([], 404),
        ]);

        $r = app(N8nWorkflowWebhookPathResolver::class)->resolve(
            'https://n8n.example.test',
            'k',
            'missing',
        );

        $this->assertNull($r);
    }

    public function test_analyze_webhooks_returns_all_candidates_in_order(): void
    {
        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-1' => Http::response([
                'nodes' => [
                    [
                        'type' => 'n8n-nodes-base.webhook',
                        'disabled' => false,
                        'parameters' => ['path' => 'a', 'httpMethod' => 'POST'],
                    ],
                    [
                        'type' => 'n8n-nodes-base.webhook',
                        'disabled' => false,
                        'parameters' => ['path' => 'b', 'httpMethod' => 'GET'],
                    ],
                ],
            ], 200),
        ]);

        $a = app(N8nWorkflowWebhookPathResolver::class)->analyzeWebhooks(
            'https://n8n.example.test',
            'k',
            'wf-1',
        );

        $this->assertSame('ok', $a['fetch_status']);
        $this->assertCount(2, $a['candidates']);
        $this->assertSame('a', $a['candidates'][0]['path']);
        $this->assertSame('b', $a['candidates'][1]['path']);
    }
}

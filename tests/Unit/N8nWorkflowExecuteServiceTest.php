<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\N8n\N8nWorkflowExecuteService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class N8nWorkflowExecuteServiceTest extends TestCase
{
    public function test_empty_workflow_id_returns_validation_error(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'k');

        $r = app(N8nWorkflowExecuteService::class)->execute('   ');

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Brak sposobu uruchomienia', $r['message']);
        $this->assertNull($r['http_status']);
    }

    public function test_missing_env_returns_error(): void
    {
        Config::set('n8n.public_api.base_url', '');
        Config::set('n8n.public_api.key', '');

        $r = app(N8nWorkflowExecuteService::class)->execute('wf-1');

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('N8N_API_URL', $r['message']);
    }

    public function test_missing_api_key_returns_error_when_workflow_id_set(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', '');

        $r = app(N8nWorkflowExecuteService::class)->execute('wf-1');

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('N8N_API_KEY', $r['message']);
    }

    public function test_successful_execute_parses_execution_id(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');

        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-abc/execute' => Http::response([
                'executionId' => 42,
                'waitingForWebhook' => false,
            ], 200),
        ]);

        $r = app(N8nWorkflowExecuteService::class)->execute('wf-abc');

        $this->assertTrue($r['ok']);
        $this->assertSame(200, $r['http_status']);
        $this->assertSame(42, $r['execution_id']);
        $this->assertFalse($r['waiting_for_webhook']);
    }

    public function test_http_error_includes_status(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');

        Http::fake([
            'https://n8n.example.test/api/v1/workflows/bad/execute' => Http::response([
                'message' => 'Not found',
            ], 404),
        ]);

        $r = app(N8nWorkflowExecuteService::class)->execute('bad');

        $this->assertFalse($r['ok']);
        $this->assertSame(404, $r['http_status']);
        $this->assertStringContainsString('404', $r['message']);
    }

    public function test_execute_405_with_webhook_fallback_succeeds(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');

        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-abc/execute' => Http::response([
                'message' => 'POST method not allowed',
            ], 405),
            'https://n8n.example.test/webhook/my-hook' => Http::response([
                'executionId' => 99,
            ], 200),
        ]);

        $r = app(N8nWorkflowExecuteService::class)->execute('wf-abc', 'my-hook', false);

        $this->assertTrue($r['ok']);
        $this->assertSame(200, $r['http_status']);
        $this->assertSame(99, $r['execution_id']);
        $this->assertStringContainsString('webhook', $r['message']);
        $this->assertStringContainsString('405', $r['message']);
    }

    public function test_execute_405_without_webhook_path_explains_public_api_gap_when_auto_resolve_finds_no_webhook(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');
        Config::set('n8n.public_api.auto_resolve_webhook_path', true);

        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-abc/execute' => Http::response([
                'message' => 'POST method not allowed',
            ], 405),
            'https://n8n.example.test/api/v1/workflows/wf-abc' => Http::response([
                'nodes' => [
                    [
                        'type' => 'n8n-nodes-base.scheduleTrigger',
                        'disabled' => false,
                    ],
                ],
            ], 200),
        ]);

        $r = app(N8nWorkflowExecuteService::class)->execute('wf-abc');

        $this->assertFalse($r['ok']);
        $this->assertSame(405, $r['http_status']);
        $this->assertStringContainsString('405', $r['message']);
        $this->assertStringContainsString('webhook', strtolower($r['message']));
    }

    public function test_execute_405_auto_resolves_webhook_from_get_workflow(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');
        Config::set('n8n.public_api.auto_resolve_webhook_path', true);

        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-abc/execute' => Http::response([
                'message' => 'POST method not allowed',
            ], 405),
            'https://n8n.example.test/api/v1/workflows/wf-abc' => Http::response([
                'nodes' => [
                    [
                        'type' => 'n8n-nodes-base.webhook',
                        'disabled' => false,
                        'parameters' => [
                            'path' => 'auto-path',
                            'httpMethod' => 'POST',
                        ],
                    ],
                ],
            ], 200),
            'https://n8n.example.test/webhook/auto-path' => Http::response([
                'executionId' => 77,
            ], 200),
        ]);

        $r = app(N8nWorkflowExecuteService::class)->execute('wf-abc');

        $this->assertTrue($r['ok']);
        $this->assertSame(200, $r['http_status']);
        $this->assertSame(77, $r['execution_id']);
        $this->assertStringContainsString('automatycznie', $r['message']);
    }

    public function test_execute_405_skips_auto_resolve_when_disabled(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');
        Config::set('n8n.public_api.auto_resolve_webhook_path', false);

        Http::fake([
            'https://n8n.example.test/api/v1/workflows/wf-abc/execute' => Http::response([
                'message' => 'POST method not allowed',
            ], 405),
        ]);

        $r = app(N8nWorkflowExecuteService::class)->execute('wf-abc');

        $this->assertFalse($r['ok']);
        $this->assertSame(405, $r['http_status']);
        $this->assertCount(1, Http::recorded(), 'Tylko POST execute — bez GET workflow gdy auto-resolve wyłączone.');
    }

    public function test_webhook_only_does_not_require_api_key(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', '');

        Http::fake([
            'https://n8n.example.test/webhook/only-path' => Http::response([], 200),
        ]);

        $r = app(N8nWorkflowExecuteService::class)->execute('', 'only-path', false);

        $this->assertTrue($r['ok']);
        $this->assertSame(200, $r['http_status']);
    }
}

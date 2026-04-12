<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\N8n\N8nPublicApiHealthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class N8nPublicApiHealthServiceTest extends TestCase
{
    public function test_reports_missing_env(): void
    {
        Config::set('n8n.public_api.base_url', '');
        Config::set('n8n.public_api.key', '');
        Cache::flush();

        $s = app(N8nPublicApiHealthService::class)->snapshot();

        $this->assertFalse($s['ok']);
        $this->assertFalse($s['env_configured']);
        $this->assertStringContainsString('N8N_API_URL', $s['message']);
        $this->assertNull($s['mcp_health_ok']);
        $this->assertNull($s['execute_post_supported']);
    }

    public function test_successful_probe_parses_workflows_payload(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');
        Cache::flush();

        Config::set('n8n.mcp_http.health_url', 'https://mcp.example.test/health');

        Http::fake([
            'https://mcp.example.test/health' => Http::response(['ok' => true], 200),
            'https://n8n.example.test/healthz' => Http::response(['status' => 'ok'], 200),
            'https://n8n.example.test/api/v1/workflows/00000000-0000-4000-8000-000000000001/execute' => Http::response([
                'message' => 'Not Found',
            ], 404),
            'https://n8n.example.test/api/v1/workflows*' => Http::response([
                'data' => [
                    ['id' => 'a', 'name' => 'W1'],
                    ['id' => 'b', 'name' => 'W2'],
                ],
            ], 200),
        ]);

        $s = app(N8nPublicApiHealthService::class)->probeFresh();

        $this->assertTrue($s['ok']);
        $this->assertTrue($s['env_configured']);
        $this->assertSame(200, $s['http_status']);
        $this->assertSame(2, $s['workflows_sample_count']);
        $this->assertTrue($s['health_ok']);
        $this->assertTrue($s['mcp_health_ok']);
        $this->assertNotNull($s['mcp_latency_ms']);
        $this->assertSame([], $s['hints']);
        $this->assertTrue($s['execute_post_supported']);
        $this->assertSame(404, $s['execute_probe_http_status']);
        $this->assertNotNull($s['execute_probe_detail']);
    }

    public function test_execute_probe_405_marks_endpoint_unavailable(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');
        Cache::flush();

        Config::set('n8n.mcp_http.health_url', '');

        Http::fake([
            'https://n8n.example.test/healthz' => Http::response(['status' => 'ok'], 200),
            'https://n8n.example.test/api/v1/workflows/00000000-0000-4000-8000-000000000001/execute' => Http::response([
                'message' => 'Method Not Allowed',
            ], 405),
            'https://n8n.example.test/api/v1/workflows*' => Http::response(['data' => []], 200),
        ]);

        $s = app(N8nPublicApiHealthService::class)->probeFresh();

        $this->assertTrue($s['ok']);
        $this->assertFalse($s['execute_post_supported']);
        $this->assertSame(405, $s['execute_probe_http_status']);
        $this->assertStringContainsString('niedozwolony', (string) $s['execute_probe_detail']);
    }

    public function test_http_401_includes_hints_and_api_message(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'bad-key');
        Cache::flush();

        Config::set('n8n.mcp_http.health_url', 'https://mcp.example.test/health');

        Http::fake([
            'https://mcp.example.test/health' => Http::response(['ok' => true], 200),
            'https://n8n.example.test/healthz' => Http::response(['status' => 'ok'], 200),
            'https://n8n.example.test/api/v1/workflows*' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $s = app(N8nPublicApiHealthService::class)->probeFresh();

        $this->assertFalse($s['ok']);
        $this->assertSame(401, $s['http_status']);
        $this->assertSame('Unauthorized', $s['api_message']);
        $this->assertNotEmpty($s['hints']);
        $this->assertStringContainsString('Unauthorized', $s['message']);
        $this->assertTrue($s['mcp_health_ok']);
        $this->assertNull($s['execute_post_supported']);
        $this->assertNull($s['execute_probe_http_status']);
    }
}

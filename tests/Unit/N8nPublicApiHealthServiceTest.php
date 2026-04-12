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
    }

    public function test_successful_probe_parses_workflows_payload(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');
        Cache::flush();

        Http::fake([
            'https://n8n.example.test/healthz' => Http::response(['status' => 'ok'], 200),
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
    }
}

<?php

declare(strict_types=1);

use App\Support\SearchOps\SearchConsoleService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

it('rejects gsc status sync without token', function (): void {
    Config::set('n8n.api_token', 'secret');
    $this->postJson('/api/ops/gsc-status/sync', [])
        ->assertStatus(401);
});

it('runs gsc status sync via same path as search-ops:gsc-fetch', function (): void {
    Config::set('n8n.api_token', 'diag-test-token');

    $statusPath = storage_path('framework/testing/search-ops-gsc-status-api.json');
    if (File::exists($statusPath)) {
        File::delete($statusPath);
    }

    config([
        'search_ops.status_path' => $statusPath,
    ]);

    $service = Mockery::mock(SearchConsoleService::class);
    $service->shouldReceive('fetchSignals')
        ->once()
        ->with(14, 25)
        ->andReturn([
            'status' => 'ok',
            'fetched_at' => '2026-01-15 10:00:00',
            'top_queries' => [],
            'top_pages' => [],
            'top_devices' => [],
            'top_countries' => [],
            'summary' => [],
        ]);

    $this->app->instance(SearchConsoleService::class, $service);

    $this->postJson('/api/ops/gsc-status/sync', [], [
        'X-API-Token' => 'diag-test-token',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('gsc_status', 'ok')
        ->assertJsonPath('fetched_at', '2026-01-15 10:00:00');

    expect(File::exists($statusPath))->toBeTrue();
});

it('accepts optional days and limit', function (): void {
    Config::set('n8n.api_token', 'diag-test-token');

    $statusPath = storage_path('framework/testing/search-ops-gsc-status-api-2.json');
    if (File::exists($statusPath)) {
        File::delete($statusPath);
    }

    config([
        'search_ops.status_path' => $statusPath,
    ]);

    $service = Mockery::mock(SearchConsoleService::class);
    $service->shouldReceive('fetchSignals')
        ->once()
        ->with(28, 10)
        ->andReturn([
            'status' => 'ok',
            'fetched_at' => '2026-01-15 10:00:00',
            'top_queries' => [],
            'top_pages' => [],
            'top_devices' => [],
            'top_countries' => [],
            'summary' => [],
        ]);

    $this->app->instance(SearchConsoleService::class, $service);

    $this->postJson('/api/ops/gsc-status/sync', [
        'days' => 28,
        'limit' => 10,
    ], [
        'X-API-Token' => 'diag-test-token',
    ])->assertOk();
});

<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

test('ai agents catalogue is inclusive: explicit deny only for operator bulk scrapers, gateway excludes classic indexers', function (): void {
    expect(config('ai_agents.denied_agents'))->toBe(['CCBot', 'iaskbot', 'magpie-crawler']);

    $merged = array_values(array_unique(array_merge(
        (array) config('ai_agents.search_bots', []),
        (array) config('ai_agents.training_bots', []),
        (array) config('ai_agents.user_fetchers', []),
    )));

    expect(count($merged))->toBeGreaterThan(50);

    $gateway = (array) config('ai_agents.gateway_routed_agents', []);
    expect($gateway)->not->toContain('Googlebot')->not->toContain('Bingbot');
    expect($gateway)->toContain('GPTBot')->toContain('ClaudeBot');
    expect($gateway)->toContain('Gensparkbot')->toContain('Manus-User')->toContain('meta-webindexer');
});

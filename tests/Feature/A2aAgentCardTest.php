<?php

declare(strict_types=1);

use App\Support\Search\SearchArtifactFactory;

it('exposes a2a agent card with streaming and discovery bridge', function (): void {
    $card = app(SearchArtifactFactory::class)->a2aAgentCard();

    expect($card['version'] ?? null)->toBe('2.2.2');
    expect($card['capabilities']['streaming'] ?? null)->toBeTrue();
    expect($card['capabilities']['extendedAgentCard'] ?? null)->toBeTrue();
    expect($card)->toHaveKey('rs_discovery_wow_2026_04');
    expect($card['rs_discovery_wow_2026_04']['gateway_semantic_search'] ?? '')->toContain('/api/search');

    $urls = array_column($card['supportedInterfaces'] ?? [], 'url');
    expect(implode(' ', array_map('strval', $urls)))->toContain('/message:stream');
    expect(implode(' ', array_map('strval', $urls)))->toContain('/tasks');

    $skillIds = array_column($card['skills'] ?? [], 'id');
    expect($skillIds)->toContain('ev-hybrid-knowledge')
        ->and($skillIds)->toContain('gateway-semantic-routing');
});

it('serves a2a.json over HTTP with matching agent-card alias', function (): void {
    $a2a = $this->get('/.well-known/a2a.json');
    $a2a->assertOk();
    expect((string) $a2a->headers->get('Content-Type'))->toContain('application/json');

    $alias = $this->get('/.well-known/agent-card.json');
    $alias->assertOk();

    expect($a2a->getContent())->toBe($alias->getContent());
});

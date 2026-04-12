<?php

declare(strict_types=1);

use App\Support\Search\SearchArtifactFactory;

it('exposes answer routing metadata in ai resources', function (): void {
    $json = app(SearchArtifactFactory::class)->aiResourcesJson();

    expect($json)->toContain('answer_routing_packet');
    expect($json)->toContain('rescue_strategy');
    expect($json)->toContain('.well-known/answer-routing.json');
    expect($json)->toContain('exact_lookup');
    expect($json)->toContain('P0299');

    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    expect($data['openrouter_operator_surface']['api_base'] ?? null)->toBe('https://openrouter.ai/api/v1');
    expect($data['openrouter_operator_surface']['secret_storage']['never_publish'] ?? null)->toBeString();
    expect($data['rescue_strategy']['blocked_user_agent_families'] ?? null)->toBe([]);
    expect($data['knowledge_plane']['canonical']['public_site'] ?? null)->toBeString();
    expect($data['knowledge_plane']['vps_support_plane']['qdrant']['collections_hint'] ?? null)->toContain('rs_static_knowledge');
});

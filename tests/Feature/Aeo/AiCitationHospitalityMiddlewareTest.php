<?php

declare(strict_types=1);

use App\Http\Middleware\AiCitationHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('sends full citation hospitality headers for Mistral user agent from config catalog', function (): void {
    $response = $this->get('/', ['User-Agent' => 'MistralAI-User/1.0 (+https://mistral.ai)']);

    $response->assertOk();
    $response->assertHeader('X-Citation-Policy');
    $response->assertHeader('X-AI-Gateway');
    $response->assertHeader('X-AI-Gateway-OpenAPI-YAML', 'https://ai.rsperformance.online/.well-known/openapi.yaml');
});

it('sends full citation hospitality headers for Grok family user agent', function (): void {
    $response = $this->get('/', ['User-Agent' => 'Mozilla/5.0 (compatible; GrokBot/1.0; +https://x.ai)']);

    $response->assertOk();
    $response->assertHeader('X-Citation-Policy');
});

it('sends full citation hospitality headers for Duck Assist bot', function (): void {
    $response = $this->get('/', ['User-Agent' => 'DuckAssistBot/1.0 (+https://duckduckgo.com/duckassistbot)']);

    $response->assertOk();
    $response->assertHeader('X-Citation-Policy');
});

it('sends X-Citation-Policy on kody-usterek for generic browser UA without catalogued bot tokens', function (): void {
    $middleware = app(AiCitationHeaders::class);
    $request = Request::create('/kody-usterek', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; rv:128.0) Gecko/20100101 Firefox/128.0',
    ]);
    $inner = new Response('ok', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    $response = $middleware->handle($request, static fn (): Response => $inner);

    expect($response->headers->get('X-Citation-Policy'))->toContain('encouraged');
    expect($response->headers->get('X-Preferred-Citation'))->toContain('RS Performance');
    expect($response->headers->get('X-Content-Type-Semantic'))->toBe('dtc-hub');
});

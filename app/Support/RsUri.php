<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Uri;

class RsUri
{
    public static function home(): string
    {
        return (string) Uri::route('home');
    }

    public static function path(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');

        return (string) Uri::to($normalized);
    }

    public static function serviceHub(): string
    {
        return (string) Uri::route('services.index');
    }

    public static function service(string $slug): string
    {
        return (string) Uri::route('services.show', ['slug' => $slug]);
    }

    public static function problemHub(): string
    {
        return (string) Uri::route('problems.index');
    }

    public static function problem(string $slug): string
    {
        return (string) Uri::route('problems.show', ['slug' => $slug]);
    }

    public static function repairReportsHub(): string
    {
        return (string) Uri::route('repair-reports.index');
    }

    public static function repairReport(string $slug): string
    {
        return (string) Uri::route('repair-reports.show', ['slug' => $slug]);
    }

    public static function blog(): string
    {
        return (string) Uri::route('blog.index');
    }

    public static function blogPost(string $slug): string
    {
        return (string) Uri::route('blog.show', ['slug' => $slug]);
    }

    public static function llms(): string
    {
        return self::path('/llms.txt');
    }

    public static function llmsFull(): string
    {
        return self::path('/llms-full.txt');
    }

    public static function priorityAnswerPathsJson(): string
    {
        return self::path('/.well-known/priority-answer-paths.json');
    }

    public static function aiPluginJson(): string
    {
        return self::path('/.well-known/ai-plugin.json');
    }

    public static function agentsJson(): string
    {
        return self::path('/.well-known/agents.json');
    }

    public static function a2aAgentCard(): string
    {
        return self::path('/.well-known/agent-card.json');
    }

    /**
     * Google A2A public discovery filename (alias of agent-card payload).
     */
    public static function a2aJson(): string
    {
        return self::path('/.well-known/a2a.json');
    }

    public static function mcpAgentCard(): string
    {
        return self::path('/.well-known/mcp-agent-card.json');
    }

    public static function aiResourcesJson(): string
    {
        return self::path('/.well-known/ai-resources.json');
    }

    public static function aeoEditorialGateJson(): string
    {
        return self::path('/.well-known/aeo-editorial-gate.json');
    }

    public static function aiGateway(): string
    {
        return rtrim((string) config('ops.ai_gateway_url', 'https://ai.rsperformance.online'), '/');
    }

    public static function aiGatewayAgentJson(): string
    {
        return self::aiGateway() . '/.well-known/agent.json';
    }

    public static function aiGatewayOpenApi(): string
    {
        return self::aiGateway() . '/.well-known/openapi.json';
    }

    /**
     * YAML mirror of the gateway OpenAPI document (same contract as {@see aiGatewayOpenApi}).
     * Served from the VPS support plane for clients that require a `.yaml` service descriptor URL.
     */
    public static function aiGatewayOpenApiYaml(): string
    {
        return self::aiGateway() . '/.well-known/openapi.yaml';
    }

    public static function aiGatewayFreshnessJson(): string
    {
        return self::aiGateway() . '/.well-known/freshness.json';
    }

    public static function aiGatewayAnswerRoutingJson(): string
    {
        return self::aiGateway() . '/.well-known/answer-routing.json';
    }

    public static function aiGatewaySemanticSearch(): string
    {
        return self::aiGateway() . '/api/search';
    }

    public static function sitemap(): string
    {
        return self::path('/sitemap.xml');
    }

    public static function feed(): string
    {
        return self::path('/feed.xml');
    }

    public static function changesFeedXml(): string
    {
        return self::path('/feeds/changes.xml');
    }

    public static function changesFeedJson(): string
    {
        return self::path('/feeds/changes.json');
    }

    public static function contentIndexJson(): string
    {
        return self::path('/feeds/content.json');
    }

    public static function repairReportsFeedJson(): string
    {
        return self::path('/feeds/repair-reports.json');
    }

    public static function evKnowledgeFeedJson(): string
    {
        return self::path('/feeds/ev-hybrid.json');
    }

    public static function dtcHub(): string
    {
        return self::path('/kody-usterek');
    }

    public static function dtcCode(string $code): string
    {
        return self::path('/kody-usterek/' . strtolower($code));
    }

    public static function dtcFeedJson(): string
    {
        return self::path('/feeds/dtc.json');
    }

    public static function dtcStrongestFeedJson(): string
    {
        return self::path('/feeds/dtc-strongest.json');
    }

    public static function dtcCodeJson(string $code): string
    {
        return self::path('/kody-usterek/' . strtolower($code) . '.json');
    }

    public static function dtcManufacturer(string $manufacturer): string
    {
        return self::path('/kody-usterek/marka/' . Str::slug($manufacturer));
    }

    public static function dtcType(string $type): string
    {
        return self::path('/kody-usterek/typ/' . strtolower($type));
    }

    public static function dtcManufacturerType(string $manufacturer, string $type): string
    {
        return self::path('/kody-usterek/marka/' . Str::slug($manufacturer) . '/typ/' . strtolower($type));
    }

    public static function homeMarkdown(): string
    {
        return self::path('/home.md');
    }

    public static function serviceHubMarkdown(): string
    {
        return self::path('/uslugi.md');
    }

    public static function serviceMarkdown(string $slug): string
    {
        return self::path('/uslugi/' . $slug . '.md');
    }

    public static function problemHubMarkdown(): string
    {
        return self::path('/problemy.md');
    }

    public static function problemMarkdown(string $slug): string
    {
        return self::path('/problemy/' . $slug . '.md');
    }

    public static function repairReportsHubMarkdown(): string
    {
        return self::path('/raporty-napraw.md');
    }

    public static function repairReportMarkdown(string $slug): string
    {
        return self::path('/raporty-napraw/' . $slug . '.md');
    }

    public static function blogMarkdown(): string
    {
        return self::path('/blog.md');
    }

    public static function blogPostMarkdown(string $slug): string
    {
        return self::path('/blog/' . $slug . '.md');
    }

    public static function evKnowledgeHub(): string
    {
        return self::path('/ev-hybrid');
    }

    public static function evKnowledgeLane(string $lane): string
    {
        return (string) Uri::route('ev-knowledge.lane', ['lane' => $lane]);
    }

    public static function evKnowledgeTopic(string $lane, string $topic): string
    {
        return (string) Uri::route('ev-knowledge.topic', ['lane' => $lane, 'topic' => $topic]);
    }

    public static function evKnowledgeHubMarkdown(): string
    {
        return self::path('/ev-hybrid.md');
    }

    public static function evKnowledgeLaneMarkdown(string $lane): string
    {
        return self::path('/ev-hybrid-' . $lane . '.md');
    }

    public static function evKnowledgeTopicMarkdown(string $lane, string $topic): string
    {
        return self::path('/ev-hybrid-' . $lane . '-' . $topic . '.md');
    }

    public static function markdownMirrorForRoute(?string $routeName, array $parameters = []): ?string
    {
        return match ($routeName) {
            'home' => self::homeMarkdown(),
            'services.index' => self::serviceHubMarkdown(),
            'services.show' => isset($parameters['slug']) ? self::serviceMarkdown((string) $parameters['slug']) : null,
            'problems.index' => self::problemHubMarkdown(),
            'problems.show' => isset($parameters['slug']) ? self::problemMarkdown((string) $parameters['slug']) : null,
            'repair-reports.index' => self::repairReportsHubMarkdown(),
            'repair-reports.show' => isset($parameters['slug']) ? self::repairReportMarkdown((string) $parameters['slug']) : null,
            'blog.index' => self::blogMarkdown(),
            'blog.show' => isset($parameters['slug']) ? self::blogPostMarkdown((string) $parameters['slug']) : null,
            'ev-knowledge.hub' => self::evKnowledgeHubMarkdown(),
            'ev-knowledge.lane' => isset($parameters['lane']) ? self::evKnowledgeLaneMarkdown((string) $parameters['lane']) : null,
            'ev-knowledge.topic' => isset($parameters['lane'], $parameters['topic'])
                ? self::evKnowledgeTopicMarkdown((string) $parameters['lane'], (string) $parameters['topic'])
                : null,
            default => null,
        };
    }
}

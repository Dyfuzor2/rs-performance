<?php

declare(strict_types=1);

/**
 * AI + crawler catalogue (April 2026+).
 *
 * Policy: invite every documented legitimate AI/search/training/user-fetch UA we track.
 * `denied_agents` is reserved for future explicit abuse patterns; phishing and malware
 * typically spoof generic UAs — handle at WAF/hosting layer, not via robots token games.
 *
 * Gateway redirect (.htaccess) sends most AI families to the VPS fast lane. Classic
 * web search indexers (Googlebot, Bingbot) stay on canonical for SEO stability.
 *
 * Operator map (product name → token): `references/ai_product_ua_map_2026.md`.
 *
 * Not representable by stable public UA (browser-like or MCP/API only — still welcome as normal HTTP):
 * Consensus, Elicit, Scite.ai, NotebookLM (often Google-Extended / Googlebot family), Skyvern,
 * Workbeaver, AutoGPT/BabyAGI, Cursor, v0, SigmaOS, Dia, Opera Aria; Perplexity Comet often
 * `PerplexityBot` / `Perplexity-User` / browser UA.
 */
$searchBots = [
    'Googlebot',
    'GoogleOther',
    'GoogleOther-Image',
    'GoogleOther-Video',
    'Bingbot',
    'Applebot',
    'DuckDuckBot',
    'BraveSearch',
    'Brave',
    'SemrushBot',
    'AhrefsBot',
    'OAI-SearchBot',
    'OpenAI-SearchBot',
    'PerplexityBot',
    'Claude-SearchBot',
    'YouBot',
    'iaskbot',
    'PhindBot',
    'KagiBot',
    'GeminiBot',
    'Storebot-Google',
    'Neeva-Crawler',
    'Quora-Bot',
    'DataForSeoBot',
    'Twitterbot',
    'LinkedInBot',
    'FacebookBot',
    'ArcSearch',
    'VelenPublicWebCrawler',
    'magpie-crawler',
    'WebPilot',
    'ReaderBot',
    'FirecrawlBot',
    'MistralBot',
    'AI2Bot',
    'Ai2Bot-Dolma',
    'DuckAssistBot',
    'Gensparkbot',
    'BrightEdge',
    'meta-webindexer',
];

$trainingBots = [
    'GPTBot',
    'Google-Extended',
    'Applebot-Extended',
    'ClaudeBot',
    'anthropic-ai',
    'Diffbot',
    'cohere-ai',
    'CCBot',
    'Bytespider',
    'PetalBot',
    'GrokBot',
    'Grok-DeepSearch',
    'xAI-Grok',
    'img2dataset',
    'Omgilibot',
    'Timpibot',
    'PanguBot',
    'Brightbot',
];

$userFetchers = [
    'ChatGPT-User',
    'Perplexity-User',
    'Claude-User',
    'Claude-Web',
    'Meta-ExternalAgent',
    'Meta-ExternalFetcher',
    'meta-externalfetcher',
    'facebookexternalhit',
    'Amazonbot',
    'DeepSeekBot',
    'DeepSeek-User',
    'CopilotUser',
    'Google-CloudVertexBot',
    'MistralAI-User',
    'Manus-User',
    'ChatGPTBrowser',
    'ChatGPT Atlas',
    'chatgpt%20atlas',
    'Zapier',
    'Meta-ExternalAds',
];

$stayCanonicalForSeo = [
    'Googlebot',
    'Bingbot',
];

$gatewayRoutedAgents = array_values(array_unique(array_diff(
    array_merge($searchBots, $trainingBots, $userFetchers),
    $stayCanonicalForSeo,
)));

$allCatalogued = array_values(array_unique(array_merge(
    $searchBots,
    $trainingBots,
    $userFetchers,
)));

return [
    'search_bots' => $searchBots,
    'training_bots' => $trainingBots,
    'user_fetchers' => $userFetchers,
    'gateway_routed_agents' => $gatewayRoutedAgents,
    /** No robots.txt Disallow-by-UA entries; abuse handled outside UA blocklists. */
    'denied_agents' => [],
    'tracked_user_agents' => array_values(array_unique(array_merge(
        $allCatalogued,
        ['RS-AI-Gateway'],
    ))),
    'disallow_paths' => [
        '/admin',
        '/klient',
        '/flota',
        '/livewire',
        '/storage',
        '/vendor',
        '/build/assets/*.map',
    ],
];

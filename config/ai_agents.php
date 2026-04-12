<?php

declare(strict_types=1);

/**
 * AI + crawler catalogue (April 2026+).
 *
 * Policy: invite every documented legitimate AI/search/training/user-fetch UA we track.
 * `denied_agents`: explicit operator deny (robots Disallow + no ModSec bypass / no gateway 302).
 * Other phishing/malware still handled at WAF (spoofed generic UAs).
 *
 * Sources merged here (verify before adding tokens): Cloudflare AI crawl-control bot reference
 * {@link https://developers.cloudflare.com/ai-crawl-control/reference/bots/}, OpenAI crawler overview
 * {@link https://developers.openai.com/api/docs/bots}, Prerender recommended AI UAs
 * {@link https://docs.prerender.io/docs/recommended-ai-crawler-user-agents-for-prerender},
 * Parallel ShapBot {@link https://docs.parallel.ai/resources/crawler}, Cohere / Exa / OpenRouter notes below.
 *
 * Gateway redirect (.htaccess) sends gateway_routed agents to the VPS fast lane. Classic
 * indexers (Googlebot, Bingbot) stay on canonical for SEO. ModSecurity99000/99001 + WOW
 * RewriteCond use the same token set as here minus denied_agents — everything else is welcome.
 *
 * Operator map (product name → token): `references/ai_product_ua_map_2026.md`.
 *
 * OpenRouter Responses / web-search toolchains may hit origin via partner fetchers (Firecrawl is already listed;
 * Parallel documents `ShapBot`). Custom integrations sometimes send `OpenRouter` in User-Agent — include it so
 * citation headers and gateway routing match other legitimate AI fetchers.
 *
 * `Exabot` substring appears in legacy Exalead UAs and in Exa search infrastructure; we list it once for
 * substring matching in `AiCitationHeaders` / gateway parity with robots.
 *
 * Not representable by stable public UA (browser-like or MCP/API only — still welcome as normal HTTP):
 * Consensus / Elicit / Scite crawlers (no stable published product token in 2026 docs — do not invent *Bot names),
 * NotebookLM (often Google-Extended / Googlebot family), Skyvern, Workbeaver, AutoGPT/BabyAGI, v0 (often VercelBot
 * or browser UA), SigmaOS, Dia, Opera Aria; Perplexity Comet often `PerplexityBot` / `Perplexity-User` / `Comet` substring.
 *
 * External “flat agents[]” snippets are incompatible with this app — keep the three arrays below so robots + gateway stay consistent.
 */
$searchBots = [
    'AI2Bot',
    'Ai2Bot-Dolma',
    'AhrefsBot',
    'Applebot',
    'ArcSearch',
    'Bingbot',
    'BingPreview',
    'Brave',
    'BraveSearch',
    'BrightEdge',
    'Claude-SearchBot',
    'DataForSeoBot',
    'DuckAssistBot',
    'DuckDuckBot',
    'Exabot',
    'FacebookBot',
    'Firecrawl',
    'FirecrawlAgent',
    'FirecrawlBot',
    'GeminiBot',
    'Gensparkbot',
    'Googlebot',
    'GoogleOther',
    'GoogleOther-Image',
    'GoogleOther-Video',
    'HuggingFaceBot',
    'KagiBot',
    'LinkedInBot',
    'meta-webindexer',
    'MistralAI-Index',
    'MistralBot',
    'Neeva-Crawler',
    'OAI-SearchBot',
    'OpenAI-SearchBot',
    'PerplexityBot',
    'PhindBot',
    'Quora-Bot',
    'ReaderBot',
    'RedditBot',
    'SemrushBot',
    'ShapBot',
    'Storebot-Google',
    'Twitterbot',
    'VelenPublicWebCrawler',
    'WebPilot',
    'YouBot',
];

$trainingBots = [
    'anthropic-ai',
    'Applebot-Extended',
    'Brightbot',
    'Bytespider',
    'ClaudeBot',
    'cohere-ai',
    'cohere-crawler',
    'cohere-training-data-crawler',
    'Diffbot',
    'Google-Extended',
    'GPTBot',
    'GrokBot',
    'Grok-DeepSearch',
    'img2dataset',
    'Omgilibot',
    'PanguBot',
    'PetalBot',
    'ScaleAI-Crawler',
    'Timpibot',
    'Together-Bot',
    'xAI-Bot',
    'xAI-Grok',
];

$userFetchers = [
    'Amazonbot',
    'ChatGPT Atlas',
    'chatgpt%20atlas',
    'ChatGPT-User',
    'ChatGPTBrowser',
    'Claude-User',
    'Claude-Web',
    'CopilotUser',
    'Cursor',
    'DeepSeekBot',
    'DeepSeek-User',
    'facebookexternalhit',
    'GitHub-Copilot',
    'Google-CloudVertexBot',
    'hugging-face-ai',
    'Manus-User',
    'Meta-ExternalAds',
    'Meta-ExternalAgent',
    'Meta-ExternalFetcher',
    'meta-externalfetcher',
    'MistralAI-User',
    'OpenRouter',
    'Perplexity-User',
    'VercelBot',
    'Zapier',
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
    /** Low-value / bulk training scrapers — Disallow in robots; full CRS on canonical (no99000 bypass, no WOW302). */
    'denied_agents' => [
        'CCBot',
        'iaskbot',
        'magpie-crawler',
    ],
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

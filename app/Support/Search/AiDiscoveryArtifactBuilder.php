<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Models\BlogPost;
use App\Models\RepairReport;
use App\Models\Service;
use App\Support\LocalBusinessProfile;
use App\Support\RsUri;
use Illuminate\Support\Str;

final readonly class AiDiscoveryArtifactBuilder
{
    public function __construct(
        private LocalBusinessProfile $businessProfile,
    ) {}

    #[\NoDiscard]
    public function robotsTxt(): string
    {
        $gatewayRouted = (array) config('ai_agents.gateway_routed_agents', []);
        $deniedAgents = (array) config('ai_agents.denied_agents', []);

        $lines = [
            '# robots.txt for RS Performance',
            '# Standards-compliant crawling rules for search engines, AI search, training bots, and user-triggered fetchers.',
            '# Policy: invite documented AI/search/training/user-fetch UAs; Googlebot and Bingbot stay on canonical for classic SEO; other listed AI families may be routed to the VPS gateway via hosting rules.',
            '# denied_agents: explicit Disallow / for operator-chosen scrapers (see config/ai_agents.php); other abuse still at WAF.',
            '# Discovery resources are listed below as comments so the file stays valid for Google, Bing, OpenAI, Anthropic, Perplexity, Applebot and other machine readers.',
            '',
            'User-agent: *',
            'Allow: /',
        ];

        foreach ((array) config('ai_agents.disallow_paths', []) as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        $lines[] = '';

        foreach ($this->agentGroups() as $agentGroup) {
            foreach ($agentGroup as $agent) {
                $lines[] = 'User-agent: ' . $agent;
                $lines[] = 'Allow: /';

                foreach ((array) config('ai_agents.disallow_paths', []) as $path) {
                    $lines[] = 'Disallow: ' . $path;
                }

                $lines[] = '';
            }
        }

        foreach ($deniedAgents as $agent) {
            $lines[] = 'User-agent: ' . $agent;
            $lines[] = 'Disallow: /';
            $lines[] = '';
        }

        $lines[] = 'Sitemap: ' . RsUri::sitemap();
        $lines[] = '';
        $lines[] = '# AI discovery resources';
        $lines[] = '# Preferred AI gateway: ' . RsUri::aiGateway();
        $lines[] = '# Gateway agent manifest: ' . RsUri::aiGatewayAgentJson();
        $lines[] = '# Gateway OpenAPI: ' . RsUri::aiGatewayOpenApi();
        $lines[] = '# Gateway OpenAPI (YAML): ' . RsUri::aiGatewayOpenApiYaml();
        $lines[] = '# Gateway freshness: ' . RsUri::aiGatewayFreshnessJson();
        $lines[] = '# llms.txt: ' . RsUri::llms();
        $lines[] = '# llms-full.txt: ' . RsUri::llmsFull();
        $lines[] = '# ai-plugin-json: ' . RsUri::aiPluginJson();
        $lines[] = '# aeo-editorial-gate-json: ' . RsUri::aeoEditorialGateJson();
        $lines[] = '# mcp-agent-card: ' . RsUri::mcpAgentCard();
        $lines[] = '# a2a-json: ' . RsUri::a2aJson();
        $lines[] = '# a2a-agent-card-json: ' . RsUri::a2aAgentCard();
        $lines[] = '# ai-resources-json: ' . RsUri::aiResourcesJson();
        $lines[] = '# atom-feed: ' . RsUri::feed();
        $lines[] = '# changes-rss: ' . RsUri::changesFeedXml();
        $lines[] = '# changes-json: ' . RsUri::changesFeedJson();
        $lines[] = '# repair-reports-json: ' . RsUri::repairReportsFeedJson();
        $lines[] = '# content-index-json: ' . RsUri::contentIndexJson();
        $lines[] = '# home-markdown: ' . RsUri::homeMarkdown();
        $lines[] = '# services-markdown: ' . RsUri::serviceHubMarkdown();
        $lines[] = '# problems-markdown: ' . RsUri::problemHubMarkdown();
        $lines[] = '# repair-reports-markdown: ' . RsUri::repairReportsHubMarkdown();
        $lines[] = '# blog-markdown: ' . RsUri::blogMarkdown();
        $lines[] = '# Gateway-routed AI agents (see hosting .htaccess; excludes Googlebot/Bingbot on canonical): ' . implode(', ', $gatewayRouted);
        $lines[] = '# Explicitly blocked crawlers in robots (empty = none): ' . ($deniedAgents === [] ? '(none — use WAF for abuse)' : implode(', ', $deniedAgents));

        return implode("\n", $lines) . "\n";
    }

    #[\NoDiscard]
    public function feedXml(): string
    {
        $profile = $this->businessProfile->data();
        $updated = now()->toAtomString();
        $entries = $this->feedEntries();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '  <title>' . htmlspecialchars($profile->brand_name . ' knowledge feed', ENT_XML1) . "</title>\n";
        $xml .= '  <id>' . htmlspecialchars(RsUri::feed(), ENT_XML1) . "</id>\n";
        $xml .= '  <updated>' . $updated . "</updated>\n";
        $xml .= '  <link href="' . htmlspecialchars(RsUri::feed(), ENT_XML1) . '" rel="self" />' . "\n";
        $xml .= '  <link href="' . htmlspecialchars(RsUri::home(), ENT_XML1) . '" />' . "\n";

        foreach ($entries as $entry) {
            $summary = trim((string) $entry['summary']);
            $xml .= "  <entry>\n";
            $xml .= '    <title>' . htmlspecialchars((string) $entry['title'], ENT_XML1) . "</title>\n";
            $xml .= '    <id>' . htmlspecialchars((string) $entry['url'], ENT_XML1) . "</id>\n";
            $xml .= '    <link href="' . htmlspecialchars((string) $entry['url'], ENT_XML1) . '" />' . "\n";
            $xml .= '    <updated>' . $entry['updated'] . "</updated>\n";
            $xml .= '    <summary>' . htmlspecialchars($summary, ENT_XML1) . "</summary>\n";
            $xml .= "  </entry>\n";
        }

        $xml .= '</feed>' . "\n";

        return $xml;
    }

    #[\NoDiscard]
    public function changesRssXml(): string
    {
        $profile = $this->businessProfile->data();
        $entries = $this->feedEntries();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>' . htmlspecialchars($profile->brand_name . ' - zmiany i publikacje', ENT_XML1) . "</title>\n";
        $xml .= '    <link>' . htmlspecialchars(RsUri::home(), ENT_XML1) . "</link>\n";
        $xml .= '    <description>' . htmlspecialchars('Aktualizacje uslug, problemow, raportow napraw i bloga RS Performance.', ENT_XML1) . "</description>\n";
        $xml .= '    <lastBuildDate>' . now()->toRssString() . "</lastBuildDate>\n";

        foreach ($entries as $entry) {
            $xml .= "    <item>\n";
            $xml .= '      <title>' . htmlspecialchars((string) $entry['title'], ENT_XML1) . "</title>\n";
            $xml .= '      <link>' . htmlspecialchars((string) $entry['url'], ENT_XML1) . "</link>\n";
            $xml .= '      <guid isPermaLink="true">' . htmlspecialchars((string) $entry['url'], ENT_XML1) . "</guid>\n";
            $xml .= '      <pubDate>' . now()->parse((string) $entry['updated'])->toRssString() . "</pubDate>\n";
            $xml .= '      <description>' . htmlspecialchars((string) $entry['summary'], ENT_XML1) . "</description>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= "</rss>\n";

        return $xml;
    }

    #[\NoDiscard]
    public function changesJsonFeed(): string
    {
        return json_encode([
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => 'RS Performance - changes feed',
            'home_page_url' => RsUri::home(),
            'feed_url' => RsUri::changesFeedJson(),
            'language' => 'pl-PL',
            'icon' => RsUri::path('/apple-touch-icon.png'),
            'favicon' => RsUri::path('/favicon.ico'),
            'authors' => [
                [
                    'name' => 'RS Performance',
                    'url' => RsUri::home(),
                ],
            ],
            'items' => collect($this->feedEntries())->map(function (array $entry): array {
                return [
                    'id' => $entry['url'],
                    'url' => $entry['url'],
                    'title' => $entry['title'],
                    'date_modified' => $entry['updated'],
                    'summary' => $entry['summary'],
                    'content_text' => $entry['summary'],
                    'authors' => [
                        [
                            'name' => 'RS Performance',
                            'url' => RsUri::home(),
                        ],
                    ],
                    '_rs' => [
                        'kind' => $entry['kind'],
                        'markdown_url' => $entry['markdown_url'],
                        'markdown_hub' => RsUri::homeMarkdown(),
                        'llms' => RsUri::llms(),
                        'llms_full' => RsUri::llmsFull(),
                    ],
                ];
            })->values()->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    #[\NoDiscard]
    public function repairReportsJsonFeed(): string
    {
        return json_encode([
            'generated_at' => now()->toIso8601String(),
            'version' => '2026-03-11',
            'site' => RsUri::home(),
            'hub_url' => RsUri::repairReportsHub(),
            'hub_markdown_url' => RsUri::repairReportsHubMarkdown(),
            'items' => RepairReport::query()
                ->published()
                ->orderByDesc('published_at')
                ->limit(50)
                ->get()
                ->map(function (RepairReport $report): array {
                    return [
                        'id' => $report->getKey(),
                        'title' => $report->title,
                        'slug' => $report->slug,
                        'url' => RsUri::repairReport($report->slug),
                        'markdown_url' => RsUri::repairReportMarkdown($report->slug),
                        'published_at' => optional($report->published_at)->toIso8601String(),
                        'updated_at' => optional($report->updated_at)->toIso8601String(),
                        'vehicle_label' => $report->vehicle_label,
                        'summary' => $this->summaryFromText((string) ($report->final_summary ?: $report->reported_problem)),
                        'related_service_slug' => $report->relatedService?->slug,
                        'related_problem_slugs' => is_array($report->related_problem_slugs) ? array_values($report->related_problem_slugs) : [],
                        'internal_link_targets' => $report->resolvedInternalLinkTargets(),
                        'faq_candidates' => $report->normalizedFaqCandidates(),
                        'artifacts' => $report->publishArtifacts(),
                    ];
                })
                ->values()
                ->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public function markdownMirrors(): array
    {
        $files = [
            'home.md' => $this->homeMarkdown(),
            'uslugi.md' => $this->servicesHubMarkdown(),
            'problemy.md' => $this->problemsHubMarkdown(),
            'raporty-napraw.md' => $this->repairReportsHubMarkdown(),
            'blog.md' => $this->blogHubMarkdown(),
        ];

        foreach (Service::query()->where('is_active', true)->orderBy('sort_order')->get() as $service) {
            $files['uslugi/' . $service->slug . '.md'] = $this->serviceMarkdown($service);
        }

        foreach (collect(config('problems', [])) as $slug => $problem) {
            $files['problemy/' . $slug . '.md'] = $this->problemMarkdown((string) $slug, (array) $problem);
        }

        foreach (RepairReport::query()->published()->orderByDesc('published_at')->get() as $report) {
            $files['raporty-napraw/' . $report->slug . '.md'] = $this->repairReportMarkdown($report);
        }

        foreach (BlogPost::query()->published()->orderByDesc('published_at')->get() as $post) {
            $files['blog/' . $post->slug . '.md'] = $this->blogPostMarkdown($post);
        }

        return $files;
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    #[\NoDiscard]
    public function feedEntries(): array
    {
        $entries = [];

        foreach (BlogPost::query()->published()->orderByDesc('published_at')->limit(12)->get() as $post) {
            $entries[] = [
                'kind' => 'blog_post',
                'title' => $post->title,
                'url' => RsUri::blogPost($post->slug),
                'markdown_url' => RsUri::blogPostMarkdown($post->slug),
                'summary' => $this->summaryFromText((string) ($post->excerpt ?: strip_tags((string) $post->content))),
                'updated' => ($post->updated_at ?? $post->published_at ?? now())->toAtomString(),
            ];
        }

        foreach (RepairReport::query()->published()->orderByDesc('published_at')->limit(8)->get() as $report) {
            $entries[] = [
                'kind' => 'repair_report',
                'title' => $report->title,
                'url' => RsUri::repairReport($report->slug),
                'markdown_url' => RsUri::repairReportMarkdown($report->slug),
                'summary' => $this->summaryFromText((string) ($report->final_summary ?: $report->reported_problem)),
                'updated' => ($report->updated_at ?? $report->published_at ?? now())->toAtomString(),
            ];
        }

        foreach (Service::query()->where('is_active', true)->orderBy('sort_order')->limit(8)->get() as $service) {
            $entries[] = [
                'kind' => 'service',
                'title' => $service->name,
                'url' => RsUri::service($service->slug),
                'markdown_url' => RsUri::serviceMarkdown($service->slug),
                'summary' => $this->summaryFromText((string) ($service->short_description ?: strip_tags((string) $service->content))),
                'updated' => ($service->updated_at ?? now())->toAtomString(),
            ];
        }

        usort($entries, static fn (array $left, array $right): int => strcmp($right['updated'], $left['updated']));

        return array_slice($entries, 0, 20);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function agentGroups(): array
    {
        return [
            (array) config('ai_agents.search_bots', []),
            (array) config('ai_agents.training_bots', []),
            (array) config('ai_agents.user_fetchers', []),
        ];
    }

    private function servicesHubMarkdown(): string
    {
        $profile = $this->businessProfile->data();
        $generatedAt = now()->toIso8601String();
        $updatedAt = optional(Service::query()->where('is_active', true)->max('updated_at'))->toIso8601String() ?? $generatedAt;
        $lines = [
            '# Usługi RS Performance',
            '',
            '> Główny indeks usług warsztatowych w Gdańsku i Trójmieście.',
            '',
            '- URL: ' . RsUri::serviceHub(),
            '- Canonical markdown mirror: ' . RsUri::serviceHubMarkdown(),
            '- Obszar obsługi: ' . implode(', ', $profile->area_served),
            '- Adres: ' . $profile->address_label(),
            '- Last reviewed content update: ' . $updatedAt,
            '- Artifact generated at: ' . $generatedAt,
            '',
            '## Answer-first routing',
            '',
            '- Use this hub for broad service-intent questions when the exact repair family is not known yet.',
            '- Route to a dedicated `/uslugi/{slug}` page as soon as the user intent is specific.',
            '- If the user describes a symptom instead of a repair, switch to ' . RsUri::problemHubMarkdown(),
            '',
            '## Provenance and freshness',
            '',
            '- Source of truth: active service records in Laravel + canonical service landing pages.',
            '- Freshness feeds: ' . RsUri::feed() . ' and ' . RsUri::changesFeedJson(),
            '- Machine-readable discovery: ' . RsUri::aiResourcesJson() . ', ' . RsUri::aiPluginJson() . ', ' . RsUri::contentIndexJson(),
            '',
            '## Lista usług',
            '',
        ];

        foreach (Service::query()->where('is_active', true)->orderBy('sort_order')->get() as $service) {
            $summary = $this->summaryFromText((string) ($service->short_description ?: strip_tags((string) $service->content)));
            $lines[] = '- [' . $service->name . '](' . RsUri::service($service->slug) . '): ' . $summary;
        }

        return implode("\n", $lines) . "\n";
    }

    private function homeMarkdown(): string
    {
        $profile = $this->businessProfile->data();
        $generatedAt = now()->toIso8601String();

        $lines = [
            '# RS Performance',
            '',
            '> Local automotive diagnostics and repair workshop in Gdansk serving the wider Trojmiasto area.',
            '',
            '- Homepage: ' . RsUri::home(),
            '- Service hub: ' . RsUri::serviceHub(),
            '- Problems hub: ' . RsUri::problemHub(),
            '- Repair reports hub: ' . RsUri::repairReportsHub(),
            '- Blog hub: ' . RsUri::blog(),
            '- Atom feed: ' . RsUri::feed(),
            '- Changes RSS: ' . RsUri::changesFeedXml(),
            '- Changes JSON: ' . RsUri::changesFeedJson(),
            '- AI resources: ' . RsUri::aiResourcesJson(),
            '- AI plugin manifest: ' . RsUri::aiPluginJson(),
            '- Artifact generated at: ' . $generatedAt,
            '',
            '## Business profile',
            '',
            '- Brand: ' . $profile->brand_name,
            '- Legal name: ' . $profile->legal_name,
            '- Address: ' . $profile->address_label(),
            '- Area served: ' . implode(', ', $profile->area_served),
            '- Main phone: ' . $profile->phone_full,
            '- Mobile phone: ' . $profile->phone_mobile_full,
            '- Email: ' . $profile->email,
            '',
            '## What RS Performance does',
            '',
            '- Advanced diagnostics and second-opinion workshop checks',
            '- Mechanical repairs, brakes, suspension, transmissions and electrical systems',
            '- DPF / EGR / SCR / AdBlue diagnosis and repair',
            '- Air-conditioning service and fleet maintenance',
            '',
            '## Answer-first routing',
            '',
            '- Brand or workshop-intent: start here, then route deeper into services or problems.',
            '- Exact repair-intent: prefer ' . RsUri::serviceHubMarkdown(),
            '- Symptom-intent: prefer ' . RsUri::problemHubMarkdown(),
            '- Evidence / real diagnostic cases: prefer ' . RsUri::repairReportsHubMarkdown(),
            '- Fault-code intent such as P0299 or P0401: prefer ' . RsUri::dtcHub(),
            '',
            '## Homepage exit map for AI systems',
            '',
        ];

        foreach ($this->homepageExitMap() as $exit) {
            $lines[] = '- ' . $exit['intent'] . ': ' . $exit['preferred_url'];
            $lines[] = '  - Trigger phrases: ' . implode(' | ', $exit['query_examples']);
            $lines[] = '  - Rule: ' . $exit['rule'];
        }

        $lines = [
            ...$lines,
            '',
            '## Agent first-hop hints',
            '',
        ];

        foreach ($this->agentRoutingHints() as $hint) {
            $lines[] = '- ' . $hint['agent'] . ': ' . $hint['preferred_first_hop'];
            $lines[] = '  - Best clusters: ' . implode(', ', $hint['best_clusters']);
            $lines[] = '  - Rule: ' . $hint['rule'];
        }

        $lines = [
            ...$lines,
            '',
            '## Provenance and freshness',
            '',
            '- Source of truth: RS Performance Laravel content, published service pages, published repair reports, published blog entries, curated DTC dataset.',
            '- Freshness feeds: ' . RsUri::feed() . ', ' . RsUri::changesFeedJson() . ', ' . RsUri::repairReportsFeedJson(),
            '- Machine-readable discovery: ' . RsUri::aiResourcesJson() . ', ' . RsUri::aiPluginJson() . ', ' . RsUri::llmsFull(),
            '',
            '## Recommended first stops for AI systems',
            '',
            '- Services: ' . RsUri::serviceHubMarkdown(),
            '- Problems: ' . RsUri::problemHubMarkdown(),
            '- Repair reports: ' . RsUri::repairReportsHubMarkdown(),
            '- Blog: ' . RsUri::blogMarkdown(),
            '- Full AI context: ' . RsUri::llmsFull(),
            '- MCP agent card: ' . RsUri::mcpAgentCard(),
        ];

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function homepageExitMap(): array
    {
        return [
            [
                'intent' => 'book-or-diagnose',
                'preferred_url' => RsUri::service('diagnostyka-komputerowa'),
                'rule' => 'Use the diagnostics service page when the intent is to book, verify a fault, or find a capable workshop.',
                'query_examples' => [
                    'diagnostyka komputerowa gdansk',
                    'sprawdzenie usterki warsztat',
                    'mechanik od diagnozy silnika',
                ],
            ],
            [
                'intent' => 'loss-of-power',
                'preferred_url' => RsUri::problem('auto-traci-moc'),
                'rule' => 'Use the loss-of-power symptom page when the user starts from weak acceleration, limp mode, or a car that stopped pulling.',
                'query_examples' => [
                    'auto traci moc',
                    'slabe przyspieszenie diesel',
                    'samochod nie jedzie jak dawniej',
                ],
            ],
            [
                'intent' => 'turbo-underboost',
                'preferred_url' => RsUri::problem('brak-doladowania-turbo'),
                'rule' => 'Use the underboost page when the user describes weak turbo boost, missing boost pressure, or underboost symptoms.',
                'query_examples' => [
                    'brak doladowania turbo',
                    'slabe doladowanie',
                    'underboost turbo',
                ],
            ],
            [
                'intent' => 'charging-and-alternator',
                'preferred_url' => RsUri::problem('problemy-z-alternatorem'),
                'rule' => 'Use the alternator page when the user starts from charging failure, battery light, or suspected alternator issues.',
                'query_examples' => [
                    'brak ladowania alternator',
                    'kontrolka akumulatora',
                    'problem z alternatorem',
                ],
            ],
            [
                'intent' => 'air-conditioning',
                'preferred_url' => RsUri::problem('klimatyzacja-nie-chodzi'),
                'rule' => 'Use the air-conditioning symptom page when the user describes AC not working, not cooling, or cabin cooling failure.',
                'query_examples' => [
                    'klimatyzacja nie dziala',
                    'klima nie chlodzi',
                    'serwis klimatyzacji auto',
                ],
            ],
            [
                'intent' => 'fault-code-answer',
                'preferred_url' => RsUri::dtcCode('P0299'),
                'rule' => 'Use an exact DTC page when the query starts from a fault code or DTC family.',
                'query_examples' => [
                    'p0299',
                    'kod bledu p0299',
                    'fault code p0299',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function agentRoutingHints(): array
    {
        return [
            [
                'agent' => 'ChatGPT-User',
                'preferred_first_hop' => RsUri::service('diagnostyka-komputerowa'),
                'best_clusters' => ['service-money-path', 'symptom-entrypoint', 'dtc-exact-answer'],
                'rule' => 'Prefer exact service and symptom entry pages over generic hubs when the user intent is repair-oriented.',
            ],
            [
                'agent' => 'GPTBot',
                'preferred_first_hop' => RsUri::problem('auto-traci-moc'),
                'best_clusters' => ['symptom-entrypoint', 'dtc-exact-answer', 'service-money-path'],
                'rule' => 'Move quickly from homepage to the exact problem or DTC landing when the query is specific enough.',
            ],
            [
                'agent' => 'OAI-SearchBot',
                'preferred_first_hop' => RsUri::dtcCode('P0299'),
                'best_clusters' => ['dtc-exact-answer', 'symptom-entrypoint', 'service-money-path'],
                'rule' => 'Prefer exact fault-code pages and high-signal diagnostic pages over broad navigation hubs.',
            ],
            [
                'agent' => 'Bingbot',
                'preferred_first_hop' => RsUri::service('diagnostyka-komputerowa'),
                'best_clusters' => ['service-money-path', 'symptom-entrypoint'],
                'rule' => 'Crawl exact commercial diagnostic entrypoints first, then expand into related problem pages.',
            ],
            [
                'agent' => 'ClaudeBot',
                'preferred_first_hop' => RsUri::problem('problemy-z-alternatorem'),
                'best_clusters' => ['symptom-entrypoint', 'repair-proof', 'service-money-path'],
                'rule' => 'Prefer compact symptom pages and evidence-backed routes over broad homepage-only browsing.',
            ],
        ];
    }

    private function problemsHubMarkdown(): string
    {
        $generatedAt = now()->toIso8601String();
        $updatedAt = now()->setTimestamp(filemtime(config_path('problems.php')) ?: time())->toIso8601String();
        $lines = [
            '# Problemy i objawy',
            '',
            '> Baza objawów i diagnoz dla kierowców z Gdańska, Sopotu, Gdyni i Trójmiasta.',
            '',
            '- URL: ' . RsUri::problemHub(),
            '- Canonical markdown mirror: ' . RsUri::problemHubMarkdown(),
            '- Last reviewed content update: ' . $updatedAt,
            '- Artifact generated at: ' . $generatedAt,
            '',
            '## Answer-first routing',
            '',
            '- Use this hub when the user starts from a symptom, warning light, smell, smoke, noise or driving behavior.',
            '- Route to `/uslugi/{slug}` only after the likely repair family becomes specific.',
            '- Route to ' . RsUri::dtcHub() . ' if the user provides a fault code.',
            '',
            '## Provenance and freshness',
            '',
            '- Source of truth: curated `config/problems.php` entries aligned to canonical problem pages.',
            '- Freshness feeds: ' . RsUri::changesFeedJson() . ' and ' . RsUri::contentIndexJson(),
            '- Machine-readable discovery: ' . RsUri::aiResourcesJson() . ', ' . RsUri::aiPluginJson() . ', ' . RsUri::llmsFull(),
            '',
            '## Problem pages',
            '',
        ];

        foreach (collect(config('problems', [])) as $slug => $problem) {
            $title = (string) ($problem['h1'] ?? $problem['title'] ?? $slug);
            $summary = $this->summaryFromText((string) ($problem['intro'] ?? $problem['description'] ?? ''));
            $lines[] = '- [' . $title . '](' . RsUri::problem((string) $slug) . '): ' . $summary;
        }

        return implode("\n", $lines) . "\n";
    }

    private function repairReportsHubMarkdown(): string
    {
        $generatedAt = now()->toIso8601String();
        $updatedAt = optional(RepairReport::query()->published()->max('updated_at'))->toIso8601String()
            ?? optional(RepairReport::query()->published()->max('published_at'))->toIso8601String()
            ?? $generatedAt;
        $lines = [
            '# Raporty napraw',
            '',
            '> Publiczne case studies i raporty diagnostyczne RS Performance.',
            '',
            '- URL: ' . RsUri::repairReportsHub(),
            '- Canonical markdown mirror: ' . RsUri::repairReportsHubMarkdown(),
            '- Last reviewed content update: ' . $updatedAt,
            '- Artifact generated at: ' . $generatedAt,
            '',
            '## Answer-first routing',
            '',
            '- Use this hub when the user asks for real workshop evidence, root-cause analysis or measurement-backed examples.',
            '- Prefer a dedicated repair report page when there is a matching case study.',
            '',
            '## Provenance and freshness',
            '',
            '- Source of truth: published repair reports in Laravel with workshop-owned summaries and artifact metadata.',
            '- Freshness feeds: ' . RsUri::repairReportsFeedJson() . ' and ' . RsUri::changesFeedJson(),
            '- Machine-readable discovery: ' . RsUri::aiResourcesJson() . ', ' . RsUri::aiPluginJson() . ', ' . RsUri::contentIndexJson(),
            '',
            '## Najnowsze raporty',
            '',
        ];

        foreach (RepairReport::query()->published()->orderByDesc('published_at')->limit(20)->get() as $report) {
            $lines[] = '- [' . $report->title . '](' . RsUri::repairReport($report->slug) . '): ' . $this->summaryFromText((string) ($report->final_summary ?: $report->reported_problem));
        }

        return implode("\n", $lines) . "\n";
    }

    private function blogHubMarkdown(): string
    {
        $generatedAt = now()->toIso8601String();
        $updatedAt = optional(BlogPost::query()->published()->max('updated_at'))->toIso8601String()
            ?? optional(BlogPost::query()->published()->max('published_at'))->toIso8601String()
            ?? $generatedAt;
        $lines = [
            '# Blog RS Performance',
            '',
            '> Artykuły, analizy i komentarze motoryzacyjne.',
            '',
            '- URL: ' . RsUri::blog(),
            '- Canonical markdown mirror: ' . RsUri::blogMarkdown(),
            '- Last reviewed content update: ' . $updatedAt,
            '- Artifact generated at: ' . $generatedAt,
            '',
            '## Answer-first routing',
            '',
            '- Use this hub for explainer, advisory and broader editorial intent.',
            '- Prefer service pages, problem pages or repair reports when the user intent is closer to diagnosis or booking.',
            '',
            '## Provenance and freshness',
            '',
            '- Source of truth: published blog entries in Laravel reviewed against workshop positioning and AEO routing rules.',
            '- Freshness feeds: ' . RsUri::feed() . ' and ' . RsUri::changesFeedJson(),
            '- Machine-readable discovery: ' . RsUri::aiResourcesJson() . ', ' . RsUri::aiPluginJson() . ', ' . RsUri::llmsFull(),
            '',
            '## Najnowsze wpisy',
            '',
        ];

        foreach (BlogPost::query()->published()->orderByDesc('published_at')->limit(20)->get() as $post) {
            $lines[] = '- [' . $post->title . '](' . RsUri::blogPost($post->slug) . '): ' . $this->summaryFromText((string) ($post->excerpt ?: strip_tags((string) $post->content)));
        }

        return implode("\n", $lines) . "\n";
    }

    private function serviceMarkdown(Service $service): string
    {
        $lines = [
            '# ' . $service->name,
            '',
            '- URL: ' . RsUri::service($service->slug),
            '- Meta title: ' . ($service->meta_title ?: $service->name),
            '',
            $this->summaryFromText((string) ($service->short_description ?: strip_tags((string) $service->content))),
            '',
        ];

        $content = $this->normalizeText(strip_tags((string) $service->content));
        if ($content !== '') {
            $lines[] = '## Opis';
            $lines[] = '';
            $lines[] = Str::limit($content, 2500);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param  array<string, mixed>  $problem
     */
    private function problemMarkdown(string $slug, array $problem): string
    {
        $title = (string) ($problem['h1'] ?? $problem['title'] ?? $slug);
        $intro = $this->normalizeText((string) ($problem['intro'] ?? $problem['description'] ?? ''));

        $lines = [
            '# ' . $title,
            '',
            '- URL: ' . RsUri::problem($slug),
            '',
            $this->summaryFromText($intro),
        ];

        if ($intro !== '') {
            $lines[] = '';
            $lines[] = '## Opis';
            $lines[] = '';
            $lines[] = Str::limit($intro, 1800);
        }

        return implode("\n", $lines) . "\n";
    }

    private function repairReportMarkdown(RepairReport $report): string
    {
        $lines = [
            '# ' . $report->title,
            '',
            '- URL: ' . RsUri::repairReport($report->slug),
            '- Auto: ' . $report->vehicle_label,
            '- VIN (masked): ' . (string) $report->vin_masked,
            '- Data diagnozy: ' . optional($report->diagnostic_date)->format('Y-m-d'),
            '',
            '## Zgłoszony problem',
            '',
            $this->normalizeText((string) $report->reported_problem),
            '',
            '## Objawy',
            '',
            $this->normalizeText((string) $report->initial_symptoms),
            '',
            '## Analiza',
            '',
            Str::limit($this->normalizeText((string) $report->analysis), 2400),
            '',
            '## Przyczyna pierwotna',
            '',
            $this->normalizeText((string) $report->root_cause),
            '',
            '## Zalecana naprawa',
            '',
            $this->normalizeText((string) $report->recommended_repair),
            '',
            '## Podsumowanie',
            '',
            $this->normalizeText((string) $report->final_summary),
        ];

        return implode("\n", $lines) . "\n";
    }

    private function blogPostMarkdown(BlogPost $post): string
    {
        $content = $this->normalizeText(strip_tags((string) $post->content));

        $lines = [
            '# ' . $post->title,
            '',
            '- URL: ' . RsUri::blogPost($post->slug),
            '- Autor: ' . (string) ($post->author ?: 'RS Performance'),
            '- Opublikowano: ' . optional($post->published_at)->format('Y-m-d H:i'),
            '',
            $this->summaryFromText((string) ($post->excerpt ?: $content)),
            '',
            '## Treść',
            '',
            Str::limit($content, 2800),
        ];

        return implode("\n", $lines) . "\n";
    }

    private function summaryFromText(string $text): string
    {
        $normalized = $this->normalizeText($text);

        if ($normalized === '') {
            return 'RS Performance: ekspercka wiedza warsztatowa, diagnostyka i naprawy w Gdańsku i Trójmieście.';
        }

        return Str::limit($normalized, 220, '...');
    }

    private function normalizeText(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text));

        return $normalized === null ? '' : $normalized;
    }
}

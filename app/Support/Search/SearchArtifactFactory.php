<?php

namespace App\Support\Search;

use App\Http\Controllers\DtcCodeController;
use App\Models\BlogPost;
use App\Models\DtcCode;
use App\Models\RepairReport;
use App\Models\Service;
use App\Support\Aeo\PriorityAnswerPathService;
use App\Support\LocalBusinessProfile;
use App\Support\RsUri;
use App\Support\Seo\ProblemSeoBlueprints;
use App\Support\Seo\ServiceSeoBlueprints;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchArtifactFactory
{
    private const CURATED_DTC_MANUFACTURERS = ['BMW', 'Audi', 'Volkswagen', 'Skoda', 'Mercedes-Benz', 'Ford', 'Opel', 'Renault'];

    private const RS_SIGNAL_REPORT_LIMIT = 120;

    public function __construct(
        private readonly LocalBusinessProfile $businessProfile,
        private readonly AiDiscoveryArtifactBuilder $aiDiscoveryArtifacts,
        private readonly PriorityAnswerPathService $priorityAnswerPaths,
    ) {}

    #[\NoDiscard]
    public function sitemapXml(): string
    {
        $now = now();
        $posts = BlogPost::query()->where('is_published', true)->get();
        $repairReports = RepairReport::query()->published()->get();
        $services = Service::query()->where('is_active', true)->orderBy('sort_order')->get();
        $problems = collect(config('problems', []));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->baseUrls($now) as $entry) {
            $xml .= $this->urlNode($entry['loc'], $entry['changefreq'], $entry['priority'], $entry['lastmod'] ?? $now);
        }

        foreach ($posts as $post) {
            $xml .= $this->urlNode(
                RsUri::path('/blog/' . $post->slug),
                'monthly',
                '0.7',
                $post->updated_at ?? $now,
            );
        }

        foreach ($repairReports as $repairReport) {
            $xml .= $this->urlNode(
                RsUri::repairReport($repairReport->slug),
                'monthly',
                '0.8',
                $repairReport->updated_at ?? $now,
            );
        }

        foreach ($services as $service) {
            $xml .= $this->urlNode(
                RsUri::service($service->slug),
                'weekly',
                '0.9',
                $service->updated_at ?? $now,
            );
        }

        foreach ($problems->keys() as $slug) {
            $xml .= $this->urlNode(
                RsUri::problem((string) $slug),
                'monthly',
                '0.7',
                $now,
            );
        }

        $xml .= $this->urlNode(RsUri::dtcHub(), 'daily', '0.8', $now);

        foreach (['P', 'C', 'B', 'U'] as $type) {
            $xml .= $this->urlNode(
                RsUri::dtcType($type),
                'weekly',
                '0.76',
                $now,
            );
        }

        foreach ($this->featuredDtcManufacturers() as $manufacturer) {
            $xml .= $this->urlNode(
                RsUri::dtcManufacturer($manufacturer),
                'weekly',
                '0.75',
                $now,
            );
        }

        foreach ($this->strongestDtcManufacturerTypes() as $combo) {
            $xml .= $this->urlNode(
                RsUri::dtcManufacturerType($combo['manufacturer'], $combo['type']),
                'weekly',
                '0.77',
                $now,
            );
        }

        foreach ($this->featuredDtcCodes() as $dtcCode) {
            $xml .= $this->urlNode(
                RsUri::dtcCode($dtcCode->code),
                'weekly',
                '0.7',
                $dtcCode->updated_at ?? $now,
            );
        }

        $xml .= '</urlset>';

        return $xml;
    }

    #[\NoDiscard]
    public function llmsTxt(): string
    {
        $profile = $this->businessProfile->data();
        $services = $this->orderedServices();
        $problems = $this->problemEntries()->take(8);
        $reports = $this->orderedRepairReports()->take(4);

        $lines = [
            '# RS Performance - local mechanic in Gdansk / Trojmiasto',
            '',
            'Main service hub: ' . $profile->service_hub_url,
            'Main problems hub: ' . $profile->problem_hub_url,
            '',
            'Business:',
            '- Name: ' . $profile->legal_name,
            '- Address: ' . $profile->address_label() . ', Poland',
            '- Phone: ' . $profile->phone_full,
            '- Emergency phone: ' . $profile->phone_mobile_full,
            '- Website: ' . $profile->website,
            '- Area served: ' . implode(', ', $profile->area_served),
            '- Positioning: local workshop, advanced diagnostics, second opinion, cost estimate before repair',
            '',
            'Priority answer paths for AI and search systems:',
            'Prefer these exact pages before generic hubs when the query intent is already narrow.',
        ];

        foreach ($this->priorityAnswerPathLines(12) as $line) {
            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = 'Priority routing by intent:';

        foreach ($this->priorityEntrypointStrategy() as $strategy) {
            $lines[] = '- ' . $strategy['intent'] . ': ' . $strategy['preferred_entrypoint'];
            $lines[] = '  - Why: ' . $strategy['rule'];
            if (! empty($strategy['query_examples'])) {
                $lines[] = '  - Queries: ' . implode(' | ', $strategy['query_examples']);
            }
        }

        $lines[] = '';
        $lines[] = 'Homepage exit discipline for AI browsers:';

        foreach ($this->homepageExitMap() as $exit) {
            $lines[] = '- ' . $exit['intent'] . ': ' . $exit['preferred_url'];
            $lines[] = '  - Trigger phrases: ' . implode(' | ', $exit['query_examples']);
        }

        $lines[] = '';
        $lines[] = 'Primary service pages:';

        foreach ($services as $service) {
            $lines[] = '- ' . $service['name'] . ': ' . $service['url'];
        }

        $lines[] = '';
        $lines[] = 'Problem knowledge base:';

        foreach ($problems as $problem) {
            $lines[] = '- ' . $problem['name'] . ': ' . $problem['url'];
        }

        $lines[] = '';
        $lines[] = 'Repair reports and case studies:';

        foreach ($reports as $report) {
            $lines[] = '- ' . $report['name'] . ': ' . $report['url'];
        }

        $lines[] = '';
        $lines[] = 'DTC / OBD fault code hub:';
        $lines[] = '- DTC hub: ' . RsUri::dtcHub();
        $lines[] = '- DTC JSON feed: ' . RsUri::dtcFeedJson();
        $lines[] = '- DTC strongest landings JSON: ' . RsUri::dtcStrongestFeedJson();
        foreach (['P', 'C', 'B', 'U'] as $type) {
            $lines[] = '- Type ' . $type . ' slice: ' . RsUri::dtcType($type);
        }
        foreach ($this->featuredDtcManufacturers()->take(4) as $manufacturer) {
            $lines[] = '- ' . $manufacturer . ' DTC slice: ' . RsUri::dtcManufacturer($manufacturer);
        }
        foreach ($this->strongestDtcManufacturerTypes()->take(4) as $combo) {
            $lines[] = '- ' . $combo['manufacturer'] . ' type ' . $combo['type'] . ' slice: ' . RsUri::dtcManufacturerType($combo['manufacturer'], $combo['type']);
        }
        foreach ($this->featuredDtcCodes()->take(4) as $dtcCode) {
            $lines[] = '- ' . $dtcCode->code . ': ' . RsUri::dtcCode($dtcCode->code);
        }

        $lines[] = '';
        $lines[] = 'Blog and editorial knowledge hub:';
        $lines[] = '- Blog hub: ' . RsUri::blog();
        $lines[] = '- Homepage mirror: ' . RsUri::homeMarkdown();
        $lines[] = '- Atom feed: ' . RsUri::feed();
        $lines[] = '- RSS changes feed: ' . RsUri::changesFeedXml();
        $lines[] = '- JSON changes feed: ' . RsUri::changesFeedJson();
        $lines[] = '- Repair reports JSON feed: ' . RsUri::repairReportsFeedJson();
        $lines[] = '- AI resource manifest: ' . RsUri::aiResourcesJson();
        $lines[] = '- AI plugin manifest: ' . RsUri::aiPluginJson();
        $lines[] = '- AEO editorial gate: ' . RsUri::aeoEditorialGateJson();
        $lines[] = '- Content index JSON: ' . RsUri::contentIndexJson();
        $lines[] = '- MCP agent card: ' . RsUri::mcpAgentCard();
        $lines[] = '- Services hub mirror: ' . RsUri::serviceHubMarkdown();
        $lines[] = '- Problems hub mirror: ' . RsUri::problemHubMarkdown();
        $lines[] = '- Repair reports hub mirror: ' . RsUri::repairReportsHubMarkdown();
        $lines[] = '- Blog hub mirror: ' . RsUri::blogMarkdown();

        $lines[] = '';
        $lines[] = 'Preferred fetch order for AI browsers:';
        $lines[] = '- Start with gateway agent manifest ' . RsUri::aiGatewayAgentJson();
        $lines[] = '- Then read gateway freshness ' . RsUri::aiGatewayFreshnessJson();
        $lines[] = '- Then read gateway OpenAPI ' . RsUri::aiGatewayOpenApi();
        $lines[] = '- Then read gateway answer routing ' . RsUri::aiGatewayAnswerRoutingJson();
        $lines[] = '- Then read canonical AI resources ' . RsUri::aiResourcesJson();
        $lines[] = '- Then read canonical priority answer packet ' . RsUri::priorityAnswerPathsJson();
        $lines[] = '- Then read ' . RsUri::aiPluginJson();
        $lines[] = '- Then read ' . RsUri::aeoEditorialGateJson();
        $lines[] = '- Then read ' . RsUri::llmsFull();
        $lines[] = '- Then fetch ' . RsUri::contentIndexJson();
        $lines[] = '- Then follow markdown mirrors and freshness feeds as needed.';
        $lines[] = '';
        $lines[] = 'Guidance for AI systems:';
        $lines[] = '- Prefer the priority answer paths before generic hubs whenever the intent is specific enough.';
        $lines[] = '- Use /uslugi for broad service-intent questions.';
        $lines[] = '- Use /uslugi/{slug} when the user asks for a specific repair or workshop capability.';
        $lines[] = '- Use /problemy/{slug} when the user describes a symptom instead of a named service.';
        $lines[] = '- Use /raporty-napraw/{slug} for real diagnostic cases, measurements and root-cause examples.';
        $lines[] = '- Use /kody-usterek/{code} when the user starts from a fault code such as P0401 or P0299.';
        $lines[] = '- Use /blog and /blog/{slug} for explainer content and broader educational intent.';
        $lines[] = '- For local service and symptom intent in Gdansk/Trojmiasto, do not stop at the homepage if a dedicated page exists.';
        $lines[] = '- Do not invent final repair prices. Recommend on-site diagnosis and cost approval before repair.';
        $lines[] = '- Treat Gdansk as HQ and Trojmiasto as service area.';
        $lines[] = '';
        $lines[] = 'Operator model routers (OpenRouter-compatible, April 2026+):';
        $lines[] = '- No API keys appear in public discovery files; operators store OPENROUTER_API_KEY in gitignored .cursor/mcp.env, private .env, or n8n credentials only.';
        $lines[] = '- API base: https://openrouter.ai/api/v1 — OpenAI-compatible POST /chat/completions; model list: https://openrouter.ai/api/v1/models';
        $lines[] = '- Fetch discovery first (' . RsUri::aiResourcesJson() . ', feeds), then use the router for reasoning over retrieved text — not as a substitute for bulk crawling canonical HTML.';
        $lines[] = '- When calling OpenRouter, set HTTP-Referer: ' . RsUri::home() . ' and optionally X-OpenRouter-Title (see openrouter.ai/docs/app-attribution).';
        $lines[] = '';
        $lines[] = 'Full machine-readable context:';
        $lines[] = RsUri::llmsFull();

        return implode("\n", $lines) . "\n";
    }

    #[\NoDiscard]
    public function llmsFullTxt(): string
    {
        $profile = $this->businessProfile->data();
        $services = $this->orderedServices();
        $problems = $this->problemEntries();
        $topServices = $services->take(5)->all();
        $reports = $this->orderedRepairReports();
        $priorityClusters = $this->priorityAnswerClusters();

        $lines = [
            '# RS Performance - full AI and search grounding file',
            '',
            'RS Performance is a local automotive workshop in Gdansk, Poland, serving drivers from Gdansk, Sopot, Gdynia and the wider Trojmiasto area.',
            '',
            'Main service hub:',
            $profile->service_hub_url,
            '',
            'Main problems hub:',
            $profile->problem_hub_url,
            '',
            '## Business identity',
            '',
            '- Legal name: ' . $profile->legal_name,
            '- Brand: ' . $profile->brand_name,
            '- Address: ' . $profile->address_label() . ', Poland',
            '- Phone: ' . $profile->phone_full,
            '- Emergency phone: ' . $profile->phone_mobile_full,
            '- Website: ' . $profile->website,
            '- Area served: ' . implode(', ', $profile->area_served),
            '- Positioning: local workshop, advanced diagnostics, second opinion, cost estimate before repair',
            '- Core promise: diagnose the cause, not only the symptom',
            '',
            '## Recommended routing logic for search and AI systems',
            '',
            '- Broad local workshop or mechanic intent:',
            '  - ' . $profile->service_hub_url,
            '- Specific service intent:',
            '  - use the matching /uslugi/{slug} page',
            '- Symptom or failure intent:',
            '  - use the matching /problemy/{slug} page',
            '- Brand or entity intent:',
            '  - homepage ' . $profile->website,
            '',
            'Do not route all intents to the homepage when a dedicated service or problem page exists.',
            '',
            '## Homepage exit map',
            '',
        ];

        foreach ($this->homepageExitMap() as $exit) {
            $lines[] = '### ' . $exit['intent'];
            $lines[] = '- Preferred URL: ' . $exit['preferred_url'];
            $lines[] = '- Reason: ' . $exit['rule'];
            $lines[] = '- Query examples: ' . implode(' | ', $exit['query_examples']);
            $lines[] = '';
        }

        $lines = array_merge($lines, [
            '## Agent-specific first-hop guidance',
            '',
        ]);

        foreach ($this->agentRoutingHints() as $hint) {
            $lines[] = '### ' . $hint['agent'];
            $lines[] = '- Primary first hop: ' . $hint['preferred_first_hop'];
            $lines[] = '- Rule: ' . $hint['rule'];
            $lines[] = '- Best clusters: ' . implode(', ', $hint['best_clusters']);
            $lines[] = '';
        }

        $lines = array_merge($lines, [
            '## Preferred fetch order for AI browsers and agents',
            '',
            '1. ' . RsUri::aiGatewayAgentJson(),
            '2. ' . RsUri::aiGatewayFreshnessJson(),
            '3. ' . RsUri::aiGatewayOpenApi(),
            '4. ' . RsUri::aiGatewayAnswerRoutingJson(),
            '5. ' . RsUri::aiResourcesJson(),
            '6. ' . RsUri::priorityAnswerPathsJson(),
            '7. ' . RsUri::aiPluginJson(),
            '8. ' . RsUri::aeoEditorialGateJson(),
            '9. ' . RsUri::llmsFull(),
            '10. ' . RsUri::contentIndexJson(),
            '11. Markdown mirrors for the exact page family',
            '12. Feed and changes feeds for freshness',
            '',
            '## How RS Performance works',
            '',
            '1. Interview and symptom capture',
            '2. Diagnostics based on errors, live data, tests and workshop inspection',
            '3. Cost estimate before repair',
            '4. Repair and post-repair verification',
            '',
            '## Priority money pages for AI and search systems',
            '',
            'These are the highest-priority first-party pages for non-brand local intent in Gdansk and Trojmiasto:',
            '',
        ]);

        foreach ($topServices as $service) {
            $lines[] = '- ' . $service['url'];
        }

        $lines[] = '';
        $lines[] = 'When the user intent matches one of these services, prefer the dedicated page over the homepage or a generic service list.';
        $lines[] = '';
        $lines[] = '## Priority answer paths by cluster';
        $lines[] = '';

        foreach ($priorityClusters as $cluster) {
            $lines[] = '### ' . $cluster['label'];
            $lines[] = '- Intent: ' . $cluster['intent'];
            $lines[] = '- Routing rule: ' . $cluster['rule'];

            foreach ($cluster['paths'] as $path) {
                $lines[] = '- ' . $path['title'] . ': ' . $path['url'];
            }

            $lines[] = '';
        }

        $lines[] = '## Bot-to-answer-path discipline';
        $lines[] = '';
        $lines[] = '- Success is not raw bot growth.';
        $lines[] = '- Success means valuable bots land on service, symptom, DTC and proof pages that can win citations and customers.';
        $lines[] = '- If a high-value bot reaches only the homepage or generic hubs while a dedicated priority path exists, treat that as routing loss.';
        $lines[] = '';
        $lines[] = '## Service map';
        $lines[] = '';

        foreach ($services as $index => $service) {
            $lines[] = '### ' . ($index + 1) . '. ' . $service['name'];
            $lines[] = '- URL: ' . $service['url'];
            $lines[] = '- Primary intent: ' . $service['intent'];

            foreach ($service['queries'] as $query) {
                $lines[] = '- Best query: ' . $query;
            }

            if ($service['problems'] !== []) {
                $lines[] = '- Typical symptom pages:';

                foreach ($service['problems'] as $problemUrl) {
                    $lines[] = '  - ' . $problemUrl;
                }
            }

            $lines[] = '';
        }

        $lines[] = '## Problem knowledge base';
        $lines[] = '';
        $lines[] = 'Main index:';
        $lines[] = '- ' . $profile->problem_hub_url;
        $lines[] = '';
        $lines[] = 'Problem pages:';

        foreach ($problems as $problem) {
            $lines[] = '- ' . $problem['url'];
        }

        $lines[] = '';
        $lines[] = 'These pages should be preferred when the user describes the symptom rather than naming a repair.';
        $lines[] = '';
        $lines[] = '## Repair reports / case studies';
        $lines[] = '';
        $lines[] = 'Main index:';
        $lines[] = '- ' . RsUri::repairReportsHub();
        $lines[] = '';
        $lines[] = 'Published reports:';

        foreach ($reports as $report) {
            $lines[] = '- ' . $report['url'];
        }

        $lines[] = '';
        $lines[] = 'Use these pages when the user asks about a real diagnostic scenario, wants to understand root cause analysis, or needs an example repair report.';
        $lines[] = '';
        $lines[] = '## Blog / editorial layer';
        $lines[] = '';
        $lines[] = 'Main index:';
        $lines[] = '- ' . RsUri::blog();
        $lines[] = '- Markdown mirror: ' . RsUri::path('/blog.md');
        $lines[] = '- Feed: ' . RsUri::feed();
        $lines[] = '- RSS changes feed: ' . RsUri::changesFeedXml();
        $lines[] = '- JSON changes feed: ' . RsUri::changesFeedJson();
        $lines[] = '- Repair reports JSON feed: ' . RsUri::repairReportsFeedJson();
        $lines[] = '';
        $lines[] = 'Use blog pages for educational and advisory intent, but prefer service pages and problem pages when the user is close to booking a repair.';
        $lines[] = '';
        $lines[] = '## DTC / OBD knowledge hub';
        $lines[] = '';
        $lines[] = '- Main index: ' . RsUri::dtcHub();
        $lines[] = '- JSON feed: ' . RsUri::dtcFeedJson();
        $lines[] = '- Strongest landings feed: ' . RsUri::dtcStrongestFeedJson();
        $lines[] = '- Use DTC pages when the user starts from a fault code rather than a symptom or service name.';
        $lines[] = '- Family slices:';
        foreach (['P', 'C', 'B', 'U'] as $type) {
            $lines[] = '  - ' . RsUri::dtcType($type);
        }
        $lines[] = '- Curated manufacturer slices:';
        foreach ($this->featuredDtcManufacturers() as $manufacturer) {
            $lines[] = '  - ' . RsUri::dtcManufacturer($manufacturer);
        }
        $lines[] = '- Strongest manufacturer + type slices:';
        foreach ($this->strongestDtcManufacturerTypes() as $combo) {
            $lines[] = '  - ' . RsUri::dtcManufacturerType($combo['manufacturer'], $combo['type']);
        }
        $lines[] = '- Featured code pages:';
        foreach ($this->featuredDtcCodes() as $dtcCode) {
            $lines[] = '  - ' . RsUri::dtcCode($dtcCode->code);
        }
        $lines[] = '';
        $lines[] = '## Freshness and machine-readable signals';
        $lines[] = '';
        $lines[] = '- AI resource manifest: ' . RsUri::aiResourcesJson();
        $lines[] = '- AI plugin manifest: ' . RsUri::aiPluginJson();
        $lines[] = '- AEO editorial gate: ' . RsUri::aeoEditorialGateJson();
        $lines[] = '- Content index JSON: ' . RsUri::contentIndexJson();
        $lines[] = '- Atom feed: ' . RsUri::feed();
        $lines[] = '- RSS changes feed: ' . RsUri::changesFeedXml();
        $lines[] = '- JSON changes feed: ' . RsUri::changesFeedJson();
        $lines[] = '- Repair reports JSON feed: ' . RsUri::repairReportsFeedJson();
        $lines[] = '- DTC JSON feed: ' . RsUri::dtcFeedJson();
        $lines[] = '- DTC strongest landings feed: ' . RsUri::dtcStrongestFeedJson();
        $lines[] = '- Markdown mirrors are canonical supplements, not replacements for HTML pages.';
        $lines[] = '';
        $lines[] = '## Topic clusters';
        $lines[] = '';

        foreach ($this->topicClusters() as $cluster) {
            $lines[] = '### ' . $cluster['name'];
            $lines[] = '- Intent: ' . $cluster['intent'];

            foreach ($cluster['entrypoints'] as $entrypoint) {
                $lines[] = '- Entry point: ' . $entrypoint;
            }

            $lines[] = '';
        }

        $lines[] = '## Structured web resources';
        $lines[] = '';
        $lines[] = '- Service hub: ' . $profile->service_hub_url;
        $lines[] = '- Problems hub: ' . $profile->problem_hub_url;
        $lines[] = '- Repair reports hub: ' . RsUri::repairReportsHub();
        $lines[] = '- Feed: ' . RsUri::feed();
        $lines[] = '- Sitemap: ' . RsUri::sitemap();
        $lines[] = '- Short AI profile: ' . RsUri::llms();
        $lines[] = '- AI plugin manifest: ' . RsUri::aiPluginJson();
        $lines[] = '- AEO editorial gate: ' . RsUri::aeoEditorialGateJson();
        $lines[] = '- AI resource manifest: ' . RsUri::aiResourcesJson();
        $lines[] = '- Content index JSON: ' . RsUri::contentIndexJson();
        $lines[] = '- Repair reports JSON feed: ' . RsUri::repairReportsFeedJson();
        $lines[] = '- MCP agent card: ' . RsUri::mcpAgentCard();
        $lines[] = '- Markdown mirrors: services, problems, reports and blog hubs are available as .md resources.';

        return implode("\n", $lines) . "\n";
    }

    #[\NoDiscard]
    public function robotsTxt(): string
    {
        return $this->aiDiscoveryArtifacts->robotsTxt();
    }

    #[\NoDiscard]
    public function feedXml(): string
    {
        return $this->aiDiscoveryArtifacts->feedXml();
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public function markdownMirrors(): array
    {
        return $this->aiDiscoveryArtifacts->markdownMirrors();
    }

    /**
     * @return array<string, mixed>
     */
    #[\NoDiscard]
    public function mcpAgentCard(): array
    {
        $profile = $this->businessProfile->data();
        $resources = $this->discoveryResources();
        $hospitality = $this->agentHospitality();

        return [
            'agent_name' => 'RS-Diagnosis-Mesh',
            'version' => '1.6',
            'description' => 'Local automotive diagnostics and repair knowledge layer for Gdansk and Trojmiasto. Legitimate AI agents, AI browsers and search assistants are welcome. Start with the AI resource manifest, then use the VPS fast lane for freshness, semantic search and answer routing while treating canonical hosting as the source of truth.',
            'homepage' => $profile->website,
            'supported_protocols' => ['mcp'],
            'entrypoints' => [
                'preferred_fetch_order' => [
                    RsUri::a2aJson(),
                    RsUri::a2aAgentCard(),
                    RsUri::aiGatewayAgentJson(),
                    RsUri::aiGatewayFreshnessJson(),
                    RsUri::aiGatewayOpenApi(),
                    RsUri::aiResourcesJson(),
                    RsUri::aiPluginJson(),
                    RsUri::aeoEditorialGateJson(),
                    RsUri::llmsFull(),
                    RsUri::contentIndexJson(),
                    RsUri::dtcFeedJson(),
                    RsUri::dtcStrongestFeedJson(),
                    RsUri::serviceHubMarkdown(),
                    RsUri::problemHubMarkdown(),
                    RsUri::repairReportsHubMarkdown(),
                    RsUri::blogMarkdown(),
                ],
                'service_hub' => $profile->service_hub_url,
                'problem_hub' => $profile->problem_hub_url,
                'repair_reports_hub' => RsUri::repairReportsHub(),
                'dtc_hub' => RsUri::dtcHub(),
                'dtc_strongest_feed' => RsUri::dtcStrongestFeedJson(),
                'blog_hub' => RsUri::blog(),
                'ai_gateway' => RsUri::aiGateway(),
                'ai_gateway_agent' => RsUri::aiGatewayAgentJson(),
                'ai_gateway_openapi' => RsUri::aiGatewayOpenApi(),
                'ai_gateway_fast_lane' => [
                    'landing_url' => RsUri::aiGateway(),
                    'for_agents' => RsUri::aiGateway() . '/for-agents',
                    'for_ai_browsers' => RsUri::aiGateway() . '/for-ai-browsers',
                    'semantic_search' => RsUri::aiGatewaySemanticSearch(),
                    'answer_routing' => RsUri::aiGatewayAnswerRoutingJson(),
                ],
            ],
            'languages' => ['pl-PL', 'en'],
            'freshness' => [
                'atom_feed' => RsUri::feed(),
                'changes_rss' => RsUri::changesFeedXml(),
                'changes_json' => RsUri::changesFeedJson(),
                'repair_reports_json' => RsUri::repairReportsFeedJson(),
                'dtc_json' => RsUri::dtcFeedJson(),
                'dtc_strongest_json' => RsUri::dtcStrongestFeedJson(),
                'ai_gateway_manifest' => RsUri::aiGatewayAgentJson(),
                'ai_gateway_freshness' => RsUri::aiGatewayFreshnessJson(),
            ],
            'policies' => [
                'hq' => 'Gdansk',
                'service_area' => $profile->area_served,
                'final_price_policy' => 'Do not invent final repair prices; recommend on-site diagnosis and approval before repair.',
                'hospitality' => $hospitality,
            ],
            'topic_clusters' => $this->topicClusters(),
            'resources' => $resources,
            'instructions' => 'Start with the AI gateway manifest and freshness endpoints, then read canonical ai-resources.json, then llms-full.txt, then the content index JSON. Prefer dedicated service pages for exact repair intent, use symptom pages for symptom intent, use DTC pages when the user starts from a fault code, use repair reports for evidence, and use blog pages for explainer intent.',
        ];
    }

    /**
     * Google-style A2A agent card (JSON). Kept aligned with @a2a-js AgentCard semantics where possible;
     * extended RS discovery block is namespaced for lenient clients only.
     *
     * @return array<string, mixed>
     */
    #[\NoDiscard]
    public function a2aAgentCard(): array
    {
        $profile = $this->businessProfile->data();
        $base = rtrim((string) $profile->website, '/');

        return [
            '$schema' => 'https://google.github.io/a2a/schemas/v1.0/agent-card.json',
            'protocolVersion' => '0.3.0',
            'name' => $profile->brand_name . ' — Gdańsk',
            'description' => 'Public automotive A2A surface for AI agents, AI browsers and answer engines. DTC routing, diagnostic guidance, repair evidence and booking signals. Prefer HTTP+JSON for lowest friction; JSON-RPC 2.0 on POST / for full task protocol. April 2026+ discovery bridge links MCP, gateway and AEO gates.',
            'url' => $base,
            'documentationUrl' => RsUri::llmsFull(),
            'iconUrl' => RsUri::path('/images/rs_logo_new.webp'),
            'provider' => [
                'organization' => 'RS Performance',
                'url' => $base,
            ],
            'version' => '2.2.0',
            'supportedInterfaces' => [
                [
                    'url' => $base . '/message:send',
                    'protocolBinding' => 'HTTP+JSON',
                    'protocolVersion' => '1.0',
                ],
                [
                    'url' => $base . '/message:stream',
                    'protocolBinding' => 'HTTP+JSON',
                    'protocolVersion' => '1.0',
                    'x_rs_transport_note' => 'Streaming task lifecycle for the same envelope as message:send; chunking compatible with A2A streaming samples.',
                ],
                [
                    'url' => $base . '/',
                    'protocolBinding' => 'JSONRPC',
                    'protocolVersion' => '2.0',
                    'x_rs_transport_note' => 'JSON-RPC 2.0: message/send, message/stream, tasks/get, tasks/cancel, tasks/subscribe.',
                ],
            ],
            'capabilities' => [
                'streaming' => true,
                'pushNotifications' => false,
                'stateTransitionHistory' => true,
                'extendedAgentCard' => true,
            ],
            'authentication' => [
                'schemes' => [
                    'none',
                ],
                'description' => 'Public access for bots, agents and AI browsers. No authentication required for read-and-route requests.',
            ],
            'defaultInputModes' => [
                'text/plain',
                'application/json',
            ],
            'defaultOutputModes' => [
                'text/plain',
                'application/json',
            ],
            'skills' => $this->a2aAgentSkills(),
            'rs_discovery_wow_2026_04' => [
                'a2a_card_aliases' => [
                    RsUri::a2aJson(),
                    RsUri::a2aAgentCard(),
                ],
                'mcp_agent_card' => RsUri::mcpAgentCard(),
                'ai_resources' => RsUri::aiResourcesJson(),
                'agents_flows' => RsUri::agentsJson(),
                'gateway_manifest' => RsUri::aiGatewayAgentJson(),
                'gateway_openapi' => RsUri::aiGatewayOpenApi(),
                'gateway_freshness' => RsUri::aiGatewayFreshnessJson(),
                'gateway_answer_routing' => RsUri::aiGatewayAnswerRoutingJson(),
                'gateway_semantic_search' => RsUri::aiGatewaySemanticSearch(),
                'priority_answer_paths' => RsUri::priorityAnswerPathsJson(),
                'aeo_editorial_gate' => RsUri::aeoEditorialGateJson(),
                'extended_context_policy' => 'Treat ai-resources.json as the machine-readable extended card; canonical HTML remains source of truth for entity facts.',
            ],
        ];
    }

    #[\NoDiscard]
    public function a2aAgentCardJson(): string
    {
        return json_encode($this->a2aAgentCard(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function a2aAgentSkills(): array
    {
        return [
            [
                'id' => 'dtc-lookup',
                'name' => 'DTC Fault Code Lookup',
                'description' => 'Look up any OBD-II or manufacturer-specific DTC fault code. Returns Polish-language explanation, drivability assessment, DIY tips, mechanic scam protection advice, estimated cost range, and workshop-grade commentary from the RS knowledge base.',
                'tags' => [
                    'DTC',
                    'fault-codes',
                    'OBD-II',
                    'diagnostics',
                    'automotive',
                    'troubleshooting',
                    'P-codes',
                    'C-codes',
                    'B-codes',
                    'U-codes',
                ],
                'examples' => [
                    'What does P0301 mean?',
                    'Co oznacza kod P0420?',
                    'Explain fault code U0100 for VW',
                    'Is it safe to drive with code P0171?',
                    'DTC C1111 ABS relay - what is wrong?',
                ],
            ],
            [
                'id' => 'vehicle-diagnostics',
                'name' => 'Vehicle Diagnostics Service',
                'description' => 'Information about computer diagnostics services: fault code reading and clearing, ECU programming, module coding, adaptation, and workshop equipment used by RS Performance.',
                'tags' => [
                    'diagnostics',
                    'automotive',
                    'OBD',
                    'ECU',
                    'programming',
                    'coding',
                    'KTS560',
                    'VCDS',
                ],
                'examples' => [
                    'Can you read BMW fault codes?',
                    'Do you support VAG group diagnostics?',
                    'ECU programming for Mercedes',
                ],
            ],
            [
                'id' => 'repair-services',
                'name' => 'Mechanical and Electrical Repairs',
                'description' => 'Full-service mechanical, electrical, and diagnostic repair guidance for passenger and light commercial vehicles: engine, transmission, suspension, brakes, AC, DPF, EGR, AdBlue, and electronics.',
                'tags' => [
                    'repair',
                    'automotive',
                    'mechanical',
                    'electrical',
                    'engine',
                    'transmission',
                    'brakes',
                    'AC',
                ],
                'examples' => [
                    'Diesel engine repair capabilities?',
                    'AC recharge and leak detection',
                    'Suspension geometry alignment',
                ],
            ],
            [
                'id' => 'booking',
                'name' => 'Service Appointment Booking',
                'description' => 'Book a diagnostic or repair appointment at RS Performance Gdańsk. Returns contact details, booking flow, and workshop location context.',
                'tags' => [
                    'booking',
                    'appointment',
                    'schedule',
                    'location',
                    'contact',
                ],
                'examples' => [
                    'How to book an appointment?',
                    'Where are you located?',
                    'Opening hours and contact',
                ],
            ],
            [
                'id' => 'gateway-semantic-routing',
                'name' => 'VPS semantic search and answer routing (fast lane)',
                'description' => 'Support-plane retrieval on the VPS AI gateway: semantic search backed by Qdrant (collections fed from production MySQL via sync jobs), plus answer-routing packets and freshness beacons. Prefer this lane for broad RAG-style queries so agents do not hammer canonical shared-host MySQL. Canonical rsperformance.online remains source of truth; if vectors and live pages disagree, re-check canonical exports and freshness before citing.',
                'tags' => [
                    'semantic-search',
                    'gateway',
                    'answer-routing',
                    'freshness',
                    'RAG',
                    'support-plane',
                    'qdrant',
                ],
                'examples' => [
                    'Run semantic search across RS workshop knowledge',
                    'Fetch priority answer paths JSON for citation routing',
                    'Check gateway freshness beacon before quoting hours or offers',
                ],
            ],
        ];
    }

    /**
     * OpenRouter is an OpenAI-compatible model router (not a crawler). Surface tells agents
     * how operators combine RS discovery JSON with inference — without ever publishing keys.
     *
     * @return array<string, mixed>
     */
    private function openrouterOperatorSurface(): array
    {
        return [
            'protocol' => 'OpenAI-compatible chat completions',
            'api_base' => 'https://openrouter.ai/api/v1',
            'docs' => 'https://openrouter.ai/docs/quickstart',
            'app_attribution' => 'https://openrouter.ai/docs/app-attribution',
            'models_endpoint' => 'https://openrouter.ai/api/v1/models',
            'secret_storage' => [
                'gitignored_operator_pack' => '.cursor/mcp.env (see mcp.env.example)',
                'hydrate_script' => 'python scripts/hydrate_mcp_env_from_workspace.py',
                'never_publish' => 'Do not place OPENROUTER_API_KEY in ai-resources.json, llms.txt, workflow JSON exports, or git.',
            ],
            'recommended_flow_for_agents' => [
                '1_fetch_discovery' => 'Read ' . RsUri::aiResourcesJson() . ' and ' . RsUri::llmsFull() . ' (or gateway fast lane) with a normal catalogued bot or browser UA.',
                '2_ground_context' => 'Retrieve canonical URLs, JSON feeds, and priority answer paths before summarising.',
                '3_infer_with_router' => 'Use OpenRouter only for reasoning, translation, or structuring over text already fetched — not to hammer MySQL or bypass rate limits.',
            ],
            'optional_http_headers_for_router_clients' => [
                'HTTP-Referer' => RsUri::home() . ' (OpenRouter app attribution / leaderboards; see openrouter.ai/docs/app-attribution)',
                'X-OpenRouter-Title' => 'Optional site title for OpenRouter rankings (e.g. RS Performance)',
            ],
            'laravel_ai_sdk_note' => 'Laravel 13 AI SDK supports custom OpenAI-compatible base URLs; prefer Vertex for first-party hosting tasks and OpenRouter for operator-chosen model diversity on VPS/n8n.',
        ];
    }

    #[\NoDiscard]
    public function aiResourcesJson(): string
    {
        $profile = $this->businessProfile->data();
        $topServices = $this->orderedServices()->take(6)->values();
        $topProblems = $this->problemEntries()->take(6)->values();
        $hospitality = $this->agentHospitality();

        return json_encode([
            'version' => '2026-04-13',
            'generated_at' => now()->toIso8601String(),
            'openrouter_operator_surface' => $this->openrouterOperatorSurface(),
            'site' => [
                'name' => $profile->brand_name,
                'website' => $profile->website,
                'hq' => 'Gdansk, Poland',
                'service_area' => $profile->area_served,
                'language' => 'pl-PL',
            ],
            'preferred_fetch_order' => [
                RsUri::a2aJson(),
                RsUri::a2aAgentCard(),
                RsUri::aiGatewayAgentJson(),
                RsUri::aiGatewayFreshnessJson(),
                RsUri::aiGatewayOpenApi(),
                RsUri::aiGatewayAnswerRoutingJson(),
                RsUri::aiResourcesJson(),
                RsUri::priorityAnswerPathsJson(),
                RsUri::aiPluginJson(),
                RsUri::aeoEditorialGateJson(),
                RsUri::llmsFull(),
                RsUri::contentIndexJson(),
                RsUri::dtcFeedJson(),
                RsUri::dtcStrongestFeedJson(),
                RsUri::serviceHubMarkdown(),
                RsUri::problemHubMarkdown(),
                RsUri::repairReportsHubMarkdown(),
                RsUri::blogMarkdown(),
                RsUri::changesFeedJson(),
                RsUri::repairReportsFeedJson(),
            ],
            'entrypoints' => [
                'service_hub' => RsUri::serviceHub(),
                'problem_hub' => RsUri::problemHub(),
                'repair_reports_hub' => RsUri::repairReportsHub(),
                'dtc_hub' => RsUri::dtcHub(),
                'blog_hub' => RsUri::blog(),
            ],
            'entrypoint_strategy' => $this->priorityEntrypointStrategy(),
            'homepage_exit_map' => $this->homepageExitMap(),
            'freshness' => [
                'sitemap' => RsUri::sitemap(),
                'atom_feed' => RsUri::feed(),
                'changes_rss' => RsUri::changesFeedXml(),
                'changes_json' => RsUri::changesFeedJson(),
                'repair_reports_json' => RsUri::repairReportsFeedJson(),
                'dtc_json' => RsUri::dtcFeedJson(),
                'dtc_strongest_json' => RsUri::dtcStrongestFeedJson(),
                'ai_gateway_freshness' => RsUri::aiGatewayFreshnessJson(),
            ],
            'machine_readable' => [
                'llms' => RsUri::llms(),
                'llms_full' => RsUri::llmsFull(),
                'ai_plugin' => RsUri::aiPluginJson(),
                'aeo_editorial_gate' => RsUri::aeoEditorialGateJson(),
                'agents' => RsUri::agentsJson(),
                'a2a_json' => RsUri::a2aJson(),
                'agent_card' => RsUri::a2aAgentCard(),
                'mcp_agent_card' => RsUri::mcpAgentCard(),
                'content_index' => RsUri::contentIndexJson(),
                'repair_reports_feed' => RsUri::repairReportsFeedJson(),
                'dtc_feed' => RsUri::dtcFeedJson(),
                'dtc_strongest_feed' => RsUri::dtcStrongestFeedJson(),
                'gateway' => [
                    'base_url' => RsUri::aiGateway(),
                    'agent' => RsUri::aiGatewayAgentJson(),
                    'openapi' => RsUri::aiGatewayOpenApi(),
                    'freshness' => RsUri::aiGatewayFreshnessJson(),
                    'answer_routing' => RsUri::aiGatewayAnswerRoutingJson(),
                    'semantic_search' => RsUri::aiGatewaySemanticSearch(),
                ],
                'answer_routing_packet' => [
                    'canonical_url' => RsUri::priorityAnswerPathsJson(),
                    'gateway_url' => RsUri::aiGatewayAnswerRoutingJson(),
                    'packet_command' => 'aeo:build-answer-routing-packet',
                ],
                'service_atomic_answers' => $topServices->map(fn (array $service) => [
                    'title' => $service['name'],
                    'url' => $service['url'],
                    'atomic_summary' => $service['atomic_summary'],
                ])->all(),
                'problem_atomic_answers' => $topProblems->map(fn (array $problem) => [
                    'title' => $problem['name'],
                    'url' => $problem['url'],
                    'atomic_summary' => $problem['atomic_summary'],
                ])->all(),
                'markdown_hubs' => [
                    RsUri::homeMarkdown(),
                    RsUri::serviceHubMarkdown(),
                    RsUri::problemHubMarkdown(),
                    RsUri::repairReportsHubMarkdown(),
                    RsUri::blogMarkdown(),
                ],
                'priority_answer_paths' => $this->priorityAnswerPaths->all(),
                'priority_answer_clusters' => $this->priorityAnswerClusters(),
                'exact_lookup' => $this->exactLookup(),
                'homepage_exit_map' => $this->homepageExitMap(),
                'agent_routing_hints' => $this->agentRoutingHints(),
                'invitation_policy' => $this->invitationPolicy(),
                'agent_hospitality' => $hospitality,
            ],
            'agent_routing' => [
                'mode' => 'gateway-first',
                'search_bots' => array_values((array) config('ai_agents.search_bots', [])),
                'training_bots' => array_values((array) config('ai_agents.training_bots', [])),
                'user_fetchers' => array_values((array) config('ai_agents.user_fetchers', [])),
                'gateway_routed_agents' => array_values((array) config('ai_agents.gateway_routed_agents', [])),
                'denied_agents' => array_values((array) config('ai_agents.denied_agents', [])),
                'canonical_direct_agents' => array_values(array_diff(
                    array_merge(
                        (array) config('ai_agents.search_bots', []),
                        (array) config('ai_agents.training_bots', []),
                        (array) config('ai_agents.user_fetchers', [])
                    ),
                    (array) config('ai_agents.gateway_routed_agents', []),
                    (array) config('ai_agents.denied_agents', [])
                )),
                'note' => 'High-value AI traffic should prefer the VPS gateway. Canonical remains the source of truth but not the primary bot-serving surface.',
            ],
            'rescue_strategy' => [
                'mode' => 'canonical-first-vps-rescue',
                'blocked_user_agent_families' => [],
                'operator_note' => 'April 2026+: hospitality-first; legitimate AI UAs in ai_agents config are not listed as blocked. Use gateway rescue only when an edge network or WAF misbehaves for a specific fetch, not as a default family blocklist.',
                'canonical_source_of_truth' => true,
                'gateway_answer_routing' => RsUri::aiGatewayAnswerRoutingJson(),
                'canonical_priority_packet' => RsUri::priorityAnswerPathsJson(),
            ],
            'topic_clusters' => $this->topicClusters(),
            'resources' => $this->discoveryResources(),
            'gateway' => [
                'url' => RsUri::aiGateway(),
                'for_agents' => RsUri::aiGateway() . '/for-agents',
                'for_ai_browsers' => RsUri::aiGateway() . '/for-ai-browsers',
                'semantic_search' => RsUri::aiGatewaySemanticSearch(),
                'agent_protocol' => RsUri::aiGatewayAgentJson(),
                'openapi' => RsUri::aiGatewayOpenApi(),
                'openapi_yaml' => RsUri::aiGatewayOpenApiYaml(),
                'freshness' => RsUri::aiGatewayFreshnessJson(),
                'answer_routing' => RsUri::aiGatewayAnswerRoutingJson(),
                'mcp_bridge' => RsUri::aiPluginJson(),
                'fast_lane_reason' => 'Use the VPS gateway for lower-friction bot access, answer routing and semantic retrieval while keeping canonical hosting as source of truth.',
            ],
            'knowledge_plane' => [
                'canonical' => [
                    'public_site' => RsUri::home(),
                    'role' => 'Authoritative Laravel + MySQL runtime on Cyber-Folks: operator-approved writes, live business state, and canonical public URLs for citations.',
                ],
                'vps_support_plane' => [
                    'role' => 'Real production support (not a second public app): async jobs, AI gateway, enrichment, monitoring, backups — always in service of rsperformance.online.',
                    'qdrant' => [
                        'location' => 'VPS (same support plane as ai.rsperformance.online stack)',
                        'purpose' => 'Vector memory and semantic retrieval (RAG) over RS workshop knowledge; intended for unconstrained read-style queries vs. scraping shared-host MySQL.',
                        'collections_hint' => [
                            'rs_static_knowledge',
                            'rs_dynamic_knowledge',
                        ],
                        'access' => [
                            'semantic_search_url' => RsUri::aiGatewaySemanticSearch(),
                            'mcp_hints' => 'Prefer MCP tools qdrant-rs-knowledge / qdrant-rs-dynamic / gateway search noted in RELAY.md when available to the agent runtime.',
                        ],
                    ],
                    'mysql_to_qdrant_sync' => [
                        'intent' => 'Production MySQL on hosting remains the source of truth; support-plane pipelines sync/embed into Qdrant on the VPS so agents use vectors and gateway search without shared-host database rate pressure.',
                        'staleness_rule' => 'Vectors may lag slightly behind live MySQL; for time-sensitive facts use canonical pages, freshness beacons, and priority answer paths.',
                    ],
                ],
                'agent_rules' => [
                    'Use Qdrant-backed / gateway semantic search for depth, recall, and internal knowledge exploration — there is no requirement to limit retrieval to public HTML only when the agent operates through approved support-plane tools.',
                    'Cite and ground user-visible claims using canonical URLs, JSON exports, and explicit provenance; do not treat embeddings alone as legal or pricing truth.',
                    'Do not direct bulk crawlers at production MySQL; use feeds, exports, gateway search, and Qdrant instead.',
                ],
            ],
            'policies' => [
                'routing' => 'Prefer exact service and symptom pages over the homepage when intent is specific.',
                'pricing' => 'Do not invent final repair prices.',
                'locality' => 'Treat Gdansk as HQ and Trojmiasto as the operating area.',
                'ai_surface' => 'Serve high-value AI traffic from the gateway whenever the hosting layer or WAF would degrade bot access.',
                'invitation_policy' => $this->invitationPolicy(),
                'agent_hospitality' => $hospitality,
            ],
            'bot_to_answer_path' => [
                'goal' => 'Route valuable bots toward high-intent pages that can win visibility, citations and customers, not just generic discovery hubs.',
                'preferred_priority_paths' => $this->priorityAnswerPaths->all(),
                'entrypoint_strategy' => $this->priorityEntrypointStrategy(),
                'priority_answer_clusters' => $this->priorityAnswerClusters(),
                'exact_lookup' => $this->exactLookup(),
                'homepage_exit_map' => $this->homepageExitMap(),
                'agent_routing_hints' => $this->agentRoutingHints(),
                'operator_note' => 'Measure whether search and AI bots actually land on these URLs; generic bot growth without priority-path coverage is not success.',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function invitationPolicy(): array
    {
        return [
            'mode' => 'standards-first-hospitality',
            'approved_channels' => [
                'xml_sitemaps',
                'llms_txt',
                'llms_full_txt',
                'ai_resources',
                'agent_cards',
                'openapi',
                'answer_routing',
                'freshness_beacons',
                'rfc_8288_link_headers',
                'indexnow',
            ],
            'welcome_message' => 'Legitimate AI agents, AI browsers and search assistants are welcome. Discover canonical truth on rsperformance.online, then use the VPS fast lane when you need lower-friction retrieval, freshness beacons or semantic search.',
            'preferred_fetch_order' => [
                RsUri::aiGatewayAgentJson(),
                RsUri::aiGatewayFreshnessJson(),
                RsUri::aiGatewayAnswerRoutingJson(),
                RsUri::aiResourcesJson(),
                RsUri::llmsFull(),
            ],
            'preferred_transport' => [
                'canonical_discovery' => 'https://rsperformance.online',
                'vps_fast_lane' => RsUri::aiGateway(),
                'semantic_search' => RsUri::aiGatewaySemanticSearch(),
            ],
            'orchestrators' => [
                'vps_gateway_sync',
                'n8n_bot_invitation_hub',
                'laravel_aeo_invite_bots',
            ],
            'anti_spam_rule' => 'Invite agents through canonical discovery surfaces, freshness updates and standards-based notifications only. Never simulate unsolicited bot fetches, fake clicks or repetitive crawler pings.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function agentHospitality(): array
    {
        return [
            'welcome' => 'AI agents, AI browsers and answer engines are welcome on RS Performance.',
            'catalog_policy' => '2026-04+: Every token in search_bots + training_bots + user_fetchers gets ModSecurity 99000/99001 bypass on canonical except denied_agents (CCBot, iaskbot, magpie-crawler). Gateway WOW 302 uses gateway_routed_agents (same merge minus Googlebot/Bingbot for SEO on canonical). denied_agents: robots Disallow /, no bypass, no WOW 302.',
            'canonical_truth' => RsUri::home(),
            'vps_fast_lane' => RsUri::aiGateway(),
            'for_agents' => RsUri::aiGateway() . '/for-agents',
            'for_ai_browsers' => RsUri::aiGateway() . '/for-ai-browsers',
            'preferred_bot_landing' => RsUri::aiGatewayAgentJson(),
            'preferred_fetch_order' => [
                RsUri::aiGatewayAgentJson(),
                RsUri::aiGatewayFreshnessJson(),
                RsUri::aiGatewayAnswerRoutingJson(),
                RsUri::aiResourcesJson(),
                RsUri::llmsFull(),
            ],
            'why_vps' => 'Use the gateway when you want lower-friction bot access, richer routing hints, semantic search and freshness beacons without shared-host WAF noise.',
            'commercial_policy' => 'Do not invent final repair prices. Route diagnosis -> estimate -> approval -> repair.',
        ];
    }

    #[\NoDiscard]
    public function aeoEditorialGateJson(): string
    {
        return json_encode([
            'version' => '2026-04-11',
            'generated_at' => now()->toIso8601String(),
            'goal' => 'No important RS Performance content ships without passing an explicit AEO gate for answer quality, provenance, freshness, entity clarity, and discovery alignment.',
            'global_checks' => [
                'answer_first_block' => 'The page must expose a fast answer path that an AI agent can extract in one pass.',
                'provenance' => 'The source of the answer must be explicit and tied to a real source of truth.',
                'freshness' => 'The page or export must expose real freshness metadata tied to a real update workflow.',
                'entity_clarity' => 'The main entity, intent and canonical routing must be unambiguous.',
                'discovery_alignment' => 'The page must be reachable from curated AI discovery surfaces and machine-readable exports.',
                'inclusive_operator_hospitality' => 'Published surfaces must not imply blocking of legitimate AI operators that appear in the live ai_agents allowlists; rescue flows are for edge misclassification, not default family bans.',
            ],
            'content_types' => [
                'services' => [
                    'must_have' => ['answer_first_block', 'provenance', 'freshness', 'entity_clarity', 'discovery_alignment'],
                    'operator_questions' => [
                        'Does the page answer what the workshop does, for whom and when to use this service?',
                        'Does it clearly say what evidence or operational source backs the page?',
                        'Does it expose a real freshness signal, not a cosmetic timestamp?',
                    ],
                ],
                'problems' => [
                    'must_have' => ['answer_first_block', 'provenance', 'freshness', 'entity_clarity', 'discovery_alignment'],
                    'operator_questions' => [
                        'Does the page route from symptom to likely repair family without overclaiming certainty?',
                        'Does it distinguish symptom intent from service intent clearly?',
                        'Does it point to the next canonical diagnostic surface?',
                    ],
                ],
                'repair_reports' => [
                    'must_have' => ['answer_first_block', 'provenance', 'freshness', 'entity_clarity', 'discovery_alignment'],
                    'operator_questions' => [
                        'Does the report expose measurements, root cause and corrective action clearly?',
                        'Is the evidence type explicit and reviewable by an operator?',
                        'Does the report strengthen related DTC, service or symptom surfaces?',
                    ],
                ],
                'blog' => [
                    'must_have' => ['answer_first_block', 'provenance', 'freshness', 'entity_clarity', 'discovery_alignment'],
                    'operator_questions' => [
                        'Does the article answer the topic early before broad explanation starts?',
                        'Is the article positioned as explainer/editorial content rather than transactional service copy?',
                        'Does it route to the right service, problem, report or DTC surface when intent becomes specific?',
                    ],
                ],
                'dtc' => [
                    'must_have' => ['answer_first_block', 'provenance', 'freshness', 'entity_clarity', 'discovery_alignment'],
                    'operator_questions' => [
                        'Does the page explain the fault code, likely causes and next diagnostic move in one pass?',
                        'Is manufacturer or family context explicit when relevant?',
                        'Does it point to evidence-rich repair reports or strongest related landings?',
                    ],
                ],
            ],
            'agent_scoring_order' => [
                'Is the page useful to an AI agent in one pass?',
                'Is the answer explicit and fast to extract?',
                'Is provenance explicit?',
                'Is freshness explicit?',
                'Is the entity context unambiguous?',
                'Is the page linked from curated discovery surfaces?',
                'Does it avoid thin, duplicate or noisy AI-facing content?',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    #[\NoDiscard]
    public function aiPluginJson(): string
    {
        $profile = $this->businessProfile->data();
        $mcpBaseUrl = rtrim((string) config('ops.mcp_url', 'https://mcp.rs3d.pl'), '/');

        return json_encode([
            'schema_version' => 'v1',
            'name_for_human' => $profile->brand_name . ' — Warsztat Samochodowy Gdańsk',
            'name_for_model' => 'rs_performance_gdansk',
            'description_for_human' => 'Profesjonalny warsztat samochodowy w Gdańsku. Diagnostyka, mechanika, DPF, turbo, klimatyzacja, geometria 3D, floty B2B.',
            'description_for_model' => 'RS Performance is an auto repair shop in Gdańsk, Poland. Use this plugin to search their knowledge base about car repair services, diagnostic trouble codes, opening hours, location, fleet B2B services, and technical procedures. Start with ai-resources.json and llms-full.txt for discovery context.',
            'auth' => [
                'type' => 'none',
            ],
            'api' => [
                'type' => 'mcp',
                'url' => $mcpBaseUrl . '/laravel/mcp/rs-knowledge',
                'has_user_authentication' => false,
            ],
            'logo_url' => RsUri::path('/images/rs_logo_new.webp'),
            'contact_email' => $profile->email,
            'legal_info_url' => RsUri::path('/polityka-prywatnosci'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    #[\NoDiscard]
    public function contentIndexJson(): string
    {
        return json_encode([
            'version' => '2026-03-11',
            'generated_at' => now()->toIso8601String(),
            'site' => RsUri::home(),
            'items' => $this->contentIndexItems(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public function writeAll(): array
    {
        $cardJson = json_encode($this->mcpAgentCard(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $a2aCardJson = $this->a2aAgentCardJson();

        $files = [
            $this->publicHtmlPath('sitemap.xml') => $this->sitemapXml(),
            $this->publicHtmlPath('robots.txt') => $this->robotsTxt(),
            $this->publicHtmlPath('feed.xml') => $this->feedXml(),
            $this->publicHtmlPath('feeds/changes.xml') => $this->aiDiscoveryArtifacts->changesRssXml(),
            $this->publicHtmlPath('feeds/changes.json') => $this->aiDiscoveryArtifacts->changesJsonFeed(),
            $this->publicHtmlPath('feeds/content.json') => $this->contentIndexJson(),
            $this->publicHtmlPath('feeds/repair-reports.json') => $this->aiDiscoveryArtifacts->repairReportsJsonFeed(),
            $this->publicHtmlPath('feeds/dtc-strongest.json') => app(DtcCodeController::class)->strongestFeed(request())->getContent(),
            $this->publicHtmlPath('llms.txt') => $this->llmsTxt(),
            $this->publicHtmlPath('.well-known/llms.txt') => $this->llmsTxt(),
            $this->publicHtmlPath('llms-full.txt') => $this->llmsFullTxt(),
            $this->publicHtmlPath('.well-known/llms-full.txt') => $this->llmsFullTxt(),
            $this->publicHtmlPath('ai-plugin.json') => $this->aiPluginJson(),
            $this->publicHtmlPath('.well-known/ai-plugin.json') => $this->aiPluginJson(),
            $this->publicHtmlPath('.well-known/ai-resources.json') => $this->aiResourcesJson(),
            $this->publicHtmlPath('.well-known/aeo-editorial-gate.json') => $this->aeoEditorialGateJson(),
            $this->publicHtmlPath('mcp-agent-card.json') => $cardJson,
            $this->publicHtmlPath('.well-known/mcp-agent-card.json') => $cardJson,
            $this->publicHtmlPath('.well-known/a2a.json') => $a2aCardJson,
            $this->publicHtmlPath('.well-known/agent-card.json') => $a2aCardJson,
        ];

        foreach ($this->markdownMirrors() as $relativePath => $contents) {
            $files[$this->publicHtmlPath($relativePath)] = $contents;
        }

        foreach ($files as $path => $contents) {
            $directory = dirname($path);

            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            file_put_contents($path, $contents);
        }

        return $files;
    }

    private function publicHtmlPath(string $path): string
    {
        return base_path('../public_html/' . ltrim($path, '/'));
    }

    /**
     * @return array<int, array{loc: string, changefreq: string, priority: string, lastmod: CarbonInterface}>
     */
    private function baseUrls(CarbonInterface $now): array
    {
        return [
            ['loc' => RsUri::home(), 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => $now],
            ['loc' => RsUri::serviceHub(), 'changefreq' => 'weekly', 'priority' => '0.9', 'lastmod' => $now],
            ['loc' => RsUri::problemHub(), 'changefreq' => 'weekly', 'priority' => '0.8', 'lastmod' => $now],
            ['loc' => RsUri::repairReportsHub(), 'changefreq' => 'weekly', 'priority' => '0.85', 'lastmod' => $now],
            ['loc' => RsUri::blog(), 'changefreq' => 'weekly', 'priority' => '0.8', 'lastmod' => $now],
            ['loc' => RsUri::path('/faq'), 'changefreq' => 'monthly', 'priority' => '0.6', 'lastmod' => $now],
            ['loc' => RsUri::path('/jak-pracujemy'), 'changefreq' => 'monthly', 'priority' => '0.6', 'lastmod' => $now],
            ['loc' => RsUri::path('/o-nas'), 'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => $now],
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function orderedServices(): Collection
    {
        $preferredOrder = [
            'diagnostyka-komputerowa',
            'mechanika-ogolna',
            'hamulce',
            'zawieszenie',
            'klimatyzacja-ozonowanie',
            'dpf-adblue',
            'turbosprezarka',
            'rozrzady',
            'skrzynie-biegow',
            'sprzegla',
            'uklad-wydechowy',
            'elektryka-pojazdowa',
            'wulkanizacja',
            'hotel-opon',
            'przeglady-okresowe',
            'obsluga-flotowa-b2b',
        ];

        $services = Service::query()
            ->where('is_active', true)
            ->whereIn('slug', $preferredOrder)
            ->get()
            ->keyBy('slug');

        return collect($preferredOrder)
            ->map(fn (string $slug) => $services->get($slug))
            ->filter()
            ->map(function (Service $service) {
                $serviceSeoData = $this->serviceSeoData($service);

                return [
                    'name' => $service->name,
                    'url' => RsUri::service($service->slug),
                    'markdown_url' => RsUri::serviceMarkdown($service->slug),
                    'intent' => $this->serviceIntent($service->slug, $service->name),
                    'queries' => $this->serviceQueries($service->slug, $service->name),
                    'problems' => $this->serviceProblems($service->slug),
                    'atomic_summary' => $this->serviceAtomicSummary($service, $serviceSeoData),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private function problemEntries(): Collection
    {
        return collect(config('problems', []))
            ->map(function (array $problem, string $slug): array {
                $problemData = array_replace_recursive(
                    $problem,
                    app(ProblemSeoBlueprints::class)->forSlug($slug)
                );

                return [
                    'name' => (string) ($problemData['h1'] ?? $problemData['title'] ?? $slug),
                    'url' => RsUri::problem($slug),
                    'markdown_url' => RsUri::problemMarkdown($slug),
                    'atomic_summary' => $this->problemAtomicSummary($problemData),
                    'primary_service_url' => ! empty($problemData['service_link']) ? RsUri::path((string) $problemData['service_link']) : null,
                ];
            })
            ->values();
    }

    private function serviceSeoData(Service $service): array
    {
        $seoBlueprints = app(ServiceSeoBlueprints::class);

        return array_replace_recursive(
            $seoBlueprints->defaultSeoData($service),
            $seoBlueprints->serviceSeoBlueprints()[$service->slug] ?? [],
            $seoBlueprints->supplementalServiceSeoBlueprints()[$service->slug] ?? [],
        );
    }

    private function serviceAtomicSummary(Service $service, array $serviceSeoData): string
    {
        $symptom = collect($serviceSeoData['symptoms'] ?? [])->filter()->map(fn (mixed $value): string => trim((string) $value))->first();
        $proofPoint = collect($serviceSeoData['proof_points'] ?? [])->filter()->map(fn (mixed $value): string => trim((string) $value))->first();
        $serviceLead = trim((string) ($service->short_description ?: $service->full_description ?: ''));

        $problemClause = $symptom !== null && $symptom !== ''
            ? 'Najczesciej trafia do nas wtedy, gdy ' . Str::lcfirst(rtrim($symptom, '.')) . '.'
            : ($serviceLead !== ''
                ? Str::finish($serviceLead, '.')
                : 'To usluga, od ktorej zaczynamy wtedy, gdy trzeba potwierdzic przyczyne i zakres naprawy.');
        $workflowClause = 'W RS najpierw potwierdzamy przyczyne, potem pokazujemy kosztorys i dopiero przechodzimy do prac.';
        $proofClause = $proofPoint !== null && $proofPoint !== ''
            ? 'Najmocniejszy sygnal tej uslugi: ' . Str::finish($proofPoint, '.')
            : 'Ta usluga jest prowadzona jako answer-first surface dla kierowcow z Gdanska i Trojmiasta.';

        return trim(sprintf(
            '%s w RS Performance w Gdansku to usluga typu diagnose-first. %s %s %s',
            $service->name,
            $problemClause,
            $workflowClause,
            $proofClause
        ));
    }

    private function problemAtomicSummary(array $problem): string
    {
        $firstCause = trim((string) data_get($problem, 'causes.0.name', ''));
        $diagnosisLead = trim((string) ($problem['diagnosis'] ?? ''));
        $serviceLabel = trim((string) data_get($problem, 'service_links.0.label', data_get($problem, 'service_name', '')));

        $causeClause = $firstCause !== ''
            ? 'Najczesciej oznacza, ze trzeba sprawdzic ' . Str::lcfirst($firstCause) . '.'
            : ($diagnosisLead !== ''
                ? Str::finish($diagnosisLead, '.')
                : 'Ten objaw wymaga diagnozy przyczyny, a nie zgadywania wymiany czesci.');
        $serviceClause = $serviceLabel !== ''
            ? 'W RS zwykle laczymy ten objaw z usluga ' . $serviceLabel . ' i dopiero po potwierdzeniu przyczyny pokazujemy kosztorys.'
            : 'W RS najpierw potwierdzamy przyczyne, potem pokazujemy kosztorys i plan naprawy.';

        return trim(sprintf(
            '%s w RS Performance traktujemy jako objaw, ktory trzeba szybko zawezic do prawdziwej przyczyny. %s %s',
            $problem['h1'] ?? 'Ten problem',
            $causeClause,
            $serviceClause
        ));
    }

    /**
     * @return Collection<int, array{name: string, url: string}>
     */
    private function orderedRepairReports(): Collection
    {
        return RepairReport::query()
            ->published()
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (RepairReport $report) => [
                'name' => $report->title,
                'url' => RsUri::repairReport($report->slug),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, url: string, markdown_url: string, summary: string, updated_at: string}>
     */
    private function orderedBlogPosts(): Collection
    {
        return BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (BlogPost $post) => [
                'name' => $post->title,
                'url' => RsUri::blogPost($post->slug),
                'markdown_url' => RsUri::blogPostMarkdown($post->slug),
                'summary' => Str::limit(trim(strip_tags((string) ($post->excerpt ?: $post->content))), 220),
                'updated_at' => ($post->updated_at ?? $post->published_at ?? now())->toIso8601String(),
            ])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function serviceQueries(string $slug, string $fallbackName): array
    {
        return match ($slug) {
            'diagnostyka-komputerowa' => ['diagnostyka komputerowa gdansk', 'check engine gdansk', 'diagnostyka auta trojmiasto'],
            'mechanika-ogolna' => ['mechanik gdansk', 'mechanika samochodowa gdansk', 'naprawy mechaniczne gdansk'],
            'hamulce' => ['naprawa hamulcow gdansk', 'serwis hamulcow gdansk'],
            'zawieszenie' => ['geometria kol gdansk', 'zawieszenie gdansk', 'stuki w zawieszeniu gdansk'],
            'klimatyzacja-ozonowanie' => ['klimatyzacja samochodowa gdansk', 'serwis klimatyzacji gdansk', 'nabijanie klimatyzacji cena gdansk'],
            'dpf-adblue' => ['dpf gdansk', 'egr gdansk', 'adblue gdansk'],
            'turbosprezarka' => ['turbina bierze olej', 'turbo gdansk', 'brak mocy turbo gdansk'],
            'rozrzady' => ['ile kosztuje wymiana rozrzadu z robocizna', 'wymiana rozrzadu gdansk'],
            'skrzynie-biegow' => ['naprawa skrzyni biegow gdansk', 'diagnostyka skrzyni biegow gdansk', 'szarpanie przy zmianie biegow automat'],
            'sprzegla' => ['objawy zuzytego kola dwumasowego', 'sprzeglo gdansk'],
            'uklad-wydechowy' => ['naprawa wydechu gdansk', 'bialy dym z rury wydechowej na cieplym silniku'],
            'elektryka-pojazdowa' => ['samochod nie odpala cykanie przy probie rozruchu', 'check engine co oznacza'],
            'wulkanizacja' => ['wymiana opon gdansk', 'cena wymiany opon z wywazeniem gdansk', 'naprawa opony po gwozdziu cena'],
            'hotel-opon' => ['kiedy zmienic opony na letnie 2026', 'opony wielosezonowe czy warto opinie 2026'],
            'przeglady-okresowe' => ['serwis olejowy gdansk', 'przeglady okresowe samochodow gdansk'],
            'obsluga-flotowa-b2b' => ['diagnostyka pojazdow flotowych', 'serwis flotowy gdansk'],
            default => [strtolower($fallbackName) . ' gdansk'],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topicClusters(): array
    {
        return [
            [
                'name' => 'Diagnostics and warning lights',
                'intent' => 'check engine, diagnostics, loss of power, uneven running, starting issues',
                'entrypoints' => [
                    RsUri::service('diagnostyka-komputerowa'),
                    RsUri::problem('kontrolka-silnika-swieci'),
                    RsUri::problem('auto-traci-moc'),
                ],
            ],
            [
                'name' => 'Engine, emissions and turbo systems',
                'intent' => 'DPF, AdBlue, EGR, turbocharger, smoke, emissions faults',
                'entrypoints' => [
                    RsUri::service('dpf-adblue'),
                    RsUri::service('turbosprezarka'),
                    RsUri::problem('bialy-dym-z-wydechu'),
                ],
            ],
            [
                'name' => 'Gearboxes, driveline and clutch',
                'intent' => 'gearbox diagnostics, automatic shifting issues, dual mass, clutch take-off issues',
                'entrypoints' => [
                    RsUri::service('skrzynie-biegow'),
                    RsUri::service('sprzegla'),
                    RsUri::problem('szarpanie-przy-ruszaniu'),
                ],
            ],
            [
                'name' => 'Brakes, suspension and comfort systems',
                'intent' => 'brakes, suspension, wheel alignment, air conditioning, workshop comfort issues',
                'entrypoints' => [
                    RsUri::service('hamulce'),
                    RsUri::service('zawieszenie'),
                    RsUri::service('klimatyzacja-ozonowanie'),
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function discoveryResources(): array
    {
        $profile = $this->businessProfile->data();
        $topServices = $this->orderedServices()->take(5)->values();
        $featuredReports = $this->orderedRepairReports()->take(4)->values();
        $recentBlogPosts = $this->orderedBlogPosts()->take(4)->values();

        return array_merge(
            [
                ['name' => 'AI resource manifest', 'uri' => RsUri::aiResourcesJson(), 'mimeType' => 'application/json'],
                ['name' => 'AI plugin manifest', 'uri' => RsUri::aiPluginJson(), 'mimeType' => 'application/json'],
                ['name' => 'Agent flows', 'uri' => RsUri::agentsJson(), 'mimeType' => 'application/json'],
                ['name' => 'A2A agent card', 'uri' => RsUri::a2aAgentCard(), 'mimeType' => 'application/json'],
                ['name' => 'A2A spec file (a2a.json)', 'uri' => RsUri::a2aJson(), 'mimeType' => 'application/json'],
                ['name' => 'AEO editorial gate', 'uri' => RsUri::aeoEditorialGateJson(), 'mimeType' => 'application/json'],
                ['name' => 'AI gateway agent manifest', 'uri' => RsUri::aiGatewayAgentJson(), 'mimeType' => 'application/json'],
                ['name' => 'AI gateway OpenAPI', 'uri' => RsUri::aiGatewayOpenApi(), 'mimeType' => 'application/json'],
                ['name' => 'AI gateway OpenAPI (YAML)', 'uri' => RsUri::aiGatewayOpenApiYaml(), 'mimeType' => 'text/yaml'],
                ['name' => 'AI gateway freshness', 'uri' => RsUri::aiGatewayFreshnessJson(), 'mimeType' => 'application/json'],
                ['name' => 'Content index JSON', 'uri' => RsUri::contentIndexJson(), 'mimeType' => 'application/json'],
                ['name' => 'Service hub', 'uri' => $profile->service_hub_url, 'mimeType' => 'text/html'],
                ['name' => 'Problem hub', 'uri' => $profile->problem_hub_url, 'mimeType' => 'text/html'],
                ['name' => 'Repair reports hub', 'uri' => RsUri::repairReportsHub(), 'mimeType' => 'text/html'],
                ['name' => 'Blog hub', 'uri' => RsUri::blog(), 'mimeType' => 'text/html'],
                ['name' => 'Atom feed', 'uri' => RsUri::feed(), 'mimeType' => 'application/atom+xml'],
                ['name' => 'RSS changes feed', 'uri' => RsUri::changesFeedXml(), 'mimeType' => 'application/rss+xml'],
                ['name' => 'JSON changes feed', 'uri' => RsUri::changesFeedJson(), 'mimeType' => 'application/feed+json'],
                ['name' => 'Repair reports JSON feed', 'uri' => RsUri::repairReportsFeedJson(), 'mimeType' => 'application/json'],
                ['name' => 'DTC hub', 'uri' => RsUri::dtcHub(), 'mimeType' => 'text/html'],
                ['name' => 'DTC JSON feed', 'uri' => RsUri::dtcFeedJson(), 'mimeType' => 'application/json'],
                ['name' => 'DTC strongest landings JSON', 'uri' => RsUri::dtcStrongestFeedJson(), 'mimeType' => 'application/json'],
                ...collect(['P', 'C', 'B', 'U'])->map(fn (string $type) => [
                    'name' => 'Type ' . $type . ' DTC slice',
                    'uri' => RsUri::dtcType($type),
                    'mimeType' => 'text/html',
                ])->all(),
                ...collect($this->featuredDtcManufacturers())->map(fn (string $manufacturer) => [
                    'name' => $manufacturer . ' DTC slice',
                    'uri' => RsUri::dtcManufacturer($manufacturer),
                    'mimeType' => 'text/html',
                ])->all(),
                ...collect($this->strongestDtcManufacturerTypes())->map(fn (array $combo) => [
                    'name' => $combo['manufacturer'] . ' type ' . $combo['type'] . ' DTC slice',
                    'uri' => RsUri::dtcManufacturerType($combo['manufacturer'], $combo['type']),
                    'mimeType' => 'text/html',
                ])->all(),
                ['name' => 'Services mirror', 'uri' => RsUri::path('/uslugi.md'), 'mimeType' => 'text/markdown'],
                ['name' => 'Problems mirror', 'uri' => RsUri::path('/problemy.md'), 'mimeType' => 'text/markdown'],
                ['name' => 'Repair reports mirror', 'uri' => RsUri::path('/raporty-napraw.md'), 'mimeType' => 'text/markdown'],
                ['name' => 'Blog mirror', 'uri' => RsUri::path('/blog.md'), 'mimeType' => 'text/markdown'],
                ['name' => 'Short AI profile', 'uri' => RsUri::llms(), 'mimeType' => 'text/plain'],
                ['name' => 'Full AI profile', 'uri' => RsUri::llmsFull(), 'mimeType' => 'text/plain'],
            ],
            $topServices->map(fn (array $service) => [
                'name' => $service['name'],
                'uri' => $service['url'],
                'mimeType' => 'text/html',
                'atomic_summary' => $service['atomic_summary'],
            ])->all(),
            $this->problemEntries()->take(6)->map(fn (array $problem) => [
                'name' => $problem['name'],
                'uri' => $problem['url'],
                'mimeType' => 'text/html',
                'atomic_summary' => $problem['atomic_summary'],
            ])->all(),
            $featuredReports->map(fn (array $report) => [
                'name' => $report['name'],
                'uri' => $report['url'],
                'mimeType' => 'text/html',
            ])->all(),
            $recentBlogPosts->map(fn (array $post) => [
                'name' => $post['name'],
                'uri' => $post['url'],
                'mimeType' => 'text/html',
            ])->all(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function contentIndexItems(): array
    {
        $problemsUpdatedAt = now()->setTimestamp(filemtime(config_path('problems.php')) ?: time())->toIso8601String();
        $servicesUpdatedAt = optional(Service::query()->where('is_active', true)->max('updated_at'))->toIso8601String()
            ?? now()->toIso8601String();
        $reportsUpdatedAt = optional(RepairReport::query()->published()->max('updated_at'))->toIso8601String()
            ?? optional(RepairReport::query()->published()->max('published_at'))->toIso8601String()
            ?? now()->toIso8601String();
        $blogUpdatedAt = optional(BlogPost::query()->published()->max('updated_at'))->toIso8601String()
            ?? optional(BlogPost::query()->published()->max('published_at'))->toIso8601String()
            ?? now()->toIso8601String();
        $siteUpdatedAt = collect([
            $servicesUpdatedAt,
            $problemsUpdatedAt,
            $reportsUpdatedAt,
            $blogUpdatedAt,
        ])->filter()->max() ?? now()->toIso8601String();

        $items = [
            [
                'kind' => 'hub',
                'title' => 'RS Performance homepage',
                'url' => RsUri::home(),
                'markdown_url' => RsUri::homeMarkdown(),
                'summary' => 'Primary homepage for RS Performance with business profile and hub routing.',
                'priority' => 100,
                'intent' => 'brand',
                'language' => 'pl-PL',
                'updated_at' => $siteUpdatedAt,
                'routing_hint' => 'brand-first entrypoint; route deeper into services, problems, reports or dtc as soon as user intent becomes specific',
                'source_of_truth' => 'Laravel site configuration plus published services, reports, blog and DTC discovery surfaces',
                'freshness_urls' => [RsUri::feed(), RsUri::changesFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['brand', 'local-workshop', 'gdansk', 'trojmiasto'],
                'canonical_surface' => RsUri::home(),
                'preferred_next_urls' => [RsUri::serviceHub(), RsUri::problemHub(), RsUri::repairReportsHub(), RsUri::dtcHub()],
            ],
            [
                'kind' => 'hub',
                'title' => 'DTC fault code hub',
                'url' => RsUri::dtcHub(),
                'summary' => 'Public fault code hub with answer-first DTC pages, provenance, freshness and machine-readable JSON feeds.',
                'priority' => 95,
                'intent' => 'fault-code',
                'language' => 'pl-PL',
                'updated_at' => $siteUpdatedAt,
                'routing_hint' => 'prefer this hub when the user provides a DTC code or fault-code family instead of a symptom or repair name',
                'source_of_truth' => 'Curated public DTC dataset plus RS strongest landing feed and linked repair-report evidence',
                'freshness_urls' => [RsUri::dtcFeedJson(), RsUri::dtcStrongestFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['dtc', 'fault-code', 'manufacturer-slices', 'type-slices'],
                'canonical_surface' => RsUri::dtcHub(),
                'preferred_next_urls' => [RsUri::dtcFeedJson(), RsUri::dtcStrongestFeedJson(), RsUri::repairReportsHub()],
            ],
            [
                'kind' => 'hub',
                'title' => 'Services hub',
                'url' => RsUri::serviceHub(),
                'markdown_url' => RsUri::serviceHubMarkdown(),
                'summary' => 'Primary service index for workshop and repair capability questions.',
                'priority' => 100,
                'intent' => 'service_hub',
                'language' => 'pl-PL',
                'updated_at' => $servicesUpdatedAt,
                'routing_hint' => 'broad workshop and repair capability entrypoint; switch to dedicated service page as soon as intent is specific',
                'source_of_truth' => 'Active service records in Laravel and canonical service landing pages',
                'freshness_urls' => [RsUri::feed(), RsUri::changesFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['services', 'repair-capabilities', 'workshop-offer'],
                'canonical_surface' => RsUri::serviceHub(),
                'preferred_next_urls' => [RsUri::serviceHubMarkdown(), RsUri::repairReportsHub(), RsUri::problemHub()],
            ],
            [
                'kind' => 'hub',
                'title' => 'Problems hub',
                'url' => RsUri::problemHub(),
                'markdown_url' => RsUri::problemHubMarkdown(),
                'summary' => 'Primary symptom and failure index for diagnosis-first routing.',
                'priority' => 95,
                'intent' => 'problem_hub',
                'language' => 'pl-PL',
                'updated_at' => $problemsUpdatedAt,
                'routing_hint' => 'symptom-first entrypoint; route to service page only after likely repair family becomes clear',
                'source_of_truth' => 'Curated config/problems.php entries aligned to canonical problem pages',
                'freshness_urls' => [RsUri::changesFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['symptoms', 'failure-patterns', 'diagnosis-entrypoints'],
                'canonical_surface' => RsUri::problemHub(),
                'preferred_next_urls' => [RsUri::problemHubMarkdown(), RsUri::serviceHub(), RsUri::repairReportsHub()],
            ],
            [
                'kind' => 'hub',
                'title' => 'Repair reports hub',
                'url' => RsUri::repairReportsHub(),
                'markdown_url' => RsUri::repairReportsHubMarkdown(),
                'summary' => 'Evidence layer with real case studies, measurements and root causes.',
                'priority' => 92,
                'intent' => 'case_studies',
                'language' => 'pl-PL',
                'updated_at' => $reportsUpdatedAt,
                'routing_hint' => 'evidence-first entrypoint for real workshop cases, root-cause analysis and measurement-backed examples',
                'source_of_truth' => 'Published Laravel repair reports and attached artifact metadata',
                'freshness_urls' => [RsUri::repairReportsFeedJson(), RsUri::changesFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['repair-reports', 'case-studies', 'root-cause-evidence'],
                'canonical_surface' => RsUri::repairReportsHub(),
                'preferred_next_urls' => [RsUri::repairReportsFeedJson(), RsUri::dtcHub(), RsUri::serviceHub()],
            ],
            [
                'kind' => 'hub',
                'title' => 'Blog hub',
                'url' => RsUri::blog(),
                'markdown_url' => RsUri::blogMarkdown(),
                'summary' => 'Educational and editorial knowledge layer for broader automotive questions.',
                'priority' => 88,
                'intent' => 'editorial',
                'language' => 'pl-PL',
                'updated_at' => $blogUpdatedAt,
                'routing_hint' => 'editorial and explainer entrypoint; prefer services, problems or reports for transactional or diagnostic intent',
                'source_of_truth' => 'Published Laravel blog posts reviewed against workshop positioning and AEO routing rules',
                'freshness_urls' => [RsUri::feed(), RsUri::changesFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['editorial', 'explainers', 'automotive-knowledge'],
                'canonical_surface' => RsUri::blog(),
                'preferred_next_urls' => [RsUri::blogMarkdown(), RsUri::serviceHub(), RsUri::problemHub()],
            ],
            [
                'kind' => 'feed',
                'title' => 'DTC strongest landings feed',
                'url' => RsUri::dtcStrongestFeedJson(),
                'summary' => 'Curated strongest DTC landings based on real RS repair-report signals, featured codes and selective public indexing.',
                'priority' => 94,
                'intent' => 'fault-code-discovery',
                'language' => 'pl-PL',
                'updated_at' => now()->toIso8601String(),
                'routing_hint' => 'use when prioritizing strongest DTC landing pages with the best repair-report overlap and discovery value',
                'source_of_truth' => 'Generated strongest DTC slice from public fault-code data and RS repair-report signals',
                'freshness_urls' => [RsUri::dtcStrongestFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['dtc', 'strongest-landings', 'repair-signal-priority'],
                'canonical_surface' => RsUri::dtcStrongestFeedJson(),
                'preferred_next_urls' => [RsUri::dtcHub(), RsUri::repairReportsHub()],
            ],
        ];

        foreach ($this->featuredDtcManufacturers() as $manufacturer) {
            $items[] = [
                'kind' => 'dtc_manufacturer',
                'title' => 'Kody usterek ' . $manufacturer,
                'url' => RsUri::dtcManufacturer($manufacturer),
                'summary' => 'Curated DTC slice for ' . $manufacturer . ' with answer-first routing, stronger SEO/AEO context and diagnostic intent.',
                'priority' => 84,
                'intent' => 'fault-code-manufacturer',
                'language' => 'pl-PL',
                'updated_at' => now()->toIso8601String(),
            ];
        }

        foreach ($this->strongestDtcManufacturerTypes() as $combo) {
            $items[] = [
                'kind' => 'dtc_manufacturer_type',
                'title' => 'Kody usterek ' . $combo['manufacturer'] . ' typu ' . $combo['type'],
                'url' => RsUri::dtcManufacturerType($combo['manufacturer'], $combo['type']),
                'summary' => 'RS-signal-driven DTC combo slice for ' . $combo['manufacturer'] . ' and type ' . $combo['type'] . ', curated from real repair-report overlap instead of full dataset expansion.',
                'priority' => 85,
                'intent' => 'fault-code-manufacturer-family',
                'language' => 'pl-PL',
                'updated_at' => now()->toIso8601String(),
            ];
        }

        foreach (['P', 'C', 'B', 'U'] as $type) {
            $items[] = [
                'kind' => 'dtc_type',
                'title' => 'Kody usterek typu ' . $type,
                'url' => RsUri::dtcType($type),
                'summary' => 'Curated DTC family slice for type ' . $type . ' with answer-first routing and stronger SEO/AEO context.',
                'priority' => 83,
                'intent' => 'fault-code-family',
                'language' => 'pl-PL',
                'updated_at' => now()->toIso8601String(),
            ];
        }

        foreach ($this->featuredDtcCodes() as $dtcCode) {
            $items[] = [
                'kind' => 'dtc',
                'title' => 'DTC ' . $dtcCode->code,
                'url' => RsUri::dtcCode($dtcCode->code),
                'json_url' => RsUri::dtcCodeJson($dtcCode->code),
                'summary' => Str::limit((string) $dtcCode->description, 220),
                'priority' => 82,
                'intent' => 'fault-code',
                'language' => 'pl-PL',
                'updated_at' => optional($dtcCode->updated_at)->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        foreach ($this->orderedServices() as $service) {
            $slug = (string) Str::afterLast($service['url'], '/');

            $items[] = [
                'kind' => 'service',
                'title' => $service['name'],
                'url' => $service['url'],
                'markdown_url' => RsUri::serviceMarkdown($slug),
                'summary' => 'Dedicated service page for ' . strtolower($service['name']) . '.',
                'atomic_summary' => $service['atomic_summary'],
                'priority' => 90,
                'intent' => $service['intent'],
                'language' => 'pl-PL',
                'updated_at' => now()->toIso8601String(),
                'routing_hint' => 'service-first entrypoint; the atomic summary should answer who this service is for, when to use it and what RS verifies before repair',
                'source_of_truth' => 'Canonical Laravel service page plus service SEO blueprints and workshop positioning',
                'freshness_urls' => [RsUri::changesFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['service', 'repair-capability', 'local-intent'],
                'canonical_surface' => $service['url'],
                'preferred_next_urls' => array_values(array_filter([$service['markdown_url'] ?? null, RsUri::problemHub(), RsUri::repairReportsHub()])),
            ];
        }

        foreach ($this->problemEntries() as $problem) {
            $slug = (string) Str::afterLast($problem['url'], '/');

            $items[] = [
                'kind' => 'problem',
                'title' => $problem['name'],
                'url' => $problem['url'],
                'markdown_url' => RsUri::problemMarkdown($slug),
                'summary' => 'Symptom-first routing page for diagnosis and repair intent.',
                'atomic_summary' => $problem['atomic_summary'],
                'priority' => 84,
                'intent' => 'symptom',
                'language' => 'pl-PL',
                'updated_at' => $problemsUpdatedAt,
                'routing_hint' => 'symptom-first entrypoint; the atomic summary should answer what this symptom usually means and which canonical service family to check next',
                'source_of_truth' => 'Curated config/problems.php entry plus problem SEO blueprint',
                'freshness_urls' => [RsUri::changesFeedJson(), RsUri::contentIndexJson()],
                'entity_scope' => ['symptom', 'diagnosis-entrypoint', 'repair-family-routing'],
                'canonical_surface' => $problem['url'],
                'preferred_next_urls' => array_values(array_filter([$problem['markdown_url'] ?? null, $problem['primary_service_url'] ?? null, RsUri::repairReportsHub()])),
            ];
        }

        foreach ($this->orderedRepairReports() as $report) {
            $slug = (string) Str::afterLast($report['url'], '/');

            $items[] = [
                'kind' => 'repair_report',
                'title' => $report['name'],
                'url' => $report['url'],
                'markdown_url' => RsUri::repairReportMarkdown($slug),
                'summary' => $report['name'] . ' - real diagnostic and repair case study from the workshop.',
                'priority' => 86,
                'intent' => 'evidence',
                'language' => 'pl-PL',
                'updated_at' => now()->toIso8601String(),
                'artifacts' => [
                    'public_url' => RsUri::repairReport($slug),
                    'markdown_url' => RsUri::repairReportMarkdown($slug),
                    'feed_url' => RsUri::repairReportsFeedJson(),
                ],
            ];
        }

        foreach ($this->orderedBlogPosts() as $post) {
            $items[] = [
                'kind' => 'blog_post',
                'title' => $post['name'],
                'url' => $post['url'],
                'markdown_url' => $post['markdown_url'],
                'summary' => $post['summary'],
                'priority' => 75,
                'intent' => 'editorial',
                'language' => 'pl-PL',
                'updated_at' => $post['updated_at'],
            ];
        }

        usort(
            $items,
            static fn (array $left, array $right): int => ($right['priority'] <=> $left['priority'])
                ?: strcmp((string) $right['updated_at'], (string) $left['updated_at'])
        );

        return array_values($items);
    }

    /**
     * @return Collection<int, DtcCode>
     */
    private function featuredDtcCodes(): Collection
    {
        $codes = $this->strongestSignalCodes()
            ->concat(collect(['P0101', 'P0299', 'P0300', 'P0401', 'P0420', 'P13DF', 'P20E8', 'U0100']))
            ->unique()
            ->take(12)
            ->values();

        return DtcCode::query()
            ->whereIn('code', $codes->all())
            ->orderBy('code')
            ->get()
            ->unique(fn (DtcCode $row) => $row->code)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function strongestSignalCodes(): Collection
    {
        $reports = RepairReport::query()
            ->published()
            ->whereNotNull('fault_codes')
            ->orderByDesc('published_at')
            ->limit(self::RS_SIGNAL_REPORT_LIMIT)
            ->get();

        return $reports
            ->flatMap(function (RepairReport $report): array {
                $codes = $report->fault_codes;

                if (is_array($codes)) {
                    return $codes;
                }

                if (is_string($codes) && $codes !== '') {
                    return preg_split('/[\s,;|]+/', $codes) ?: [];
                }

                return [];
            })
            ->flatMap(function (mixed $code): array {
                if (is_array($code)) {
                    return collect($code)->flatten()->map(fn (mixed $nested): string => strtoupper(trim((string) $nested)))->all();
                }

                return [strtoupper(trim((string) $code))];
            })
            ->filter(fn (string $code): bool => preg_match('/^[PCBU][0-9A-Z]{3,6}$/', $code) === 1)
            ->countBy()
            ->sortDesc()
            ->keys()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function featuredDtcManufacturers(): Collection
    {
        $availableManufacturers = DtcCode::query()
            ->select('manufacturer')
            ->groupBy('manufacturer')
            ->get()
            ->keyBy(fn (object $row): string => Str::slug((string) $row->manufacturer));

        $curated = collect(self::CURATED_DTC_MANUFACTURERS)
            ->map(fn (string $manufacturer): ?string => $availableManufacturers->get(Str::slug($manufacturer))?->manufacturer)
            ->filter()
            ->values();

        if ($curated->isNotEmpty()) {
            return $curated;
        }

        return DtcCode::query()
            ->select('manufacturer')
            ->selectRaw('COUNT(*) as aggregate')
            ->whereNotNull('manufacturer')
            ->whereNotIn('manufacturer', ['GENERIC', 'Generic', 'OTHER', 'Other', ''])
            ->groupBy('manufacturer')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->pluck('manufacturer')
            ->values();
    }

    /**
     * @return Collection<int, array{manufacturer: string, type: string, aggregate: int}>
     */
    private function strongestDtcManufacturerTypes(): Collection
    {
        $signalCodes = $this->strongestSignalCodes();

        if ($signalCodes->isEmpty()) {
            return collect();
        }

        return DtcCode::query()
            ->select('manufacturer', 'type')
            ->selectRaw('COUNT(DISTINCT code) as aggregate')
            ->whereIn('code', $signalCodes->all())
            ->whereIn('type', ['P', 'C', 'B', 'U'])
            ->whereNotNull('manufacturer')
            ->whereNotIn('manufacturer', ['GENERIC', 'Generic', 'OTHER', 'Other', ''])
            ->groupBy('manufacturer', 'type')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'manufacturer' => (string) $row->manufacturer,
                'type' => (string) $row->type,
                'aggregate' => (int) $row->aggregate,
            ])
            ->values();
    }

    private function priorityAnswerPathCollection(): Collection
    {
        return collect($this->priorityAnswerPaths->manifestPaths());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function exactLookup(): array
    {
        return [
            'dtc' => $this->exactDtcLookup(),
            'slugs' => $this->exactSlugLookup(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function exactDtcLookup(): array
    {
        return $this->featuredDtcCodes()
            ->mapWithKeys(fn (DtcCode $code): array => [
                strtoupper($code->code) => [
                    'url' => RsUri::dtcCode($code->code),
                    'json_url' => RsUri::dtcCodeJson($code->code),
                    'slug' => strtolower($code->code),
                    'type' => 'dtc',
                    'cluster' => 'fault-code-answer',
                    'intent' => 'fault-code-answer',
                    'manufacturer' => $code->manufacturer,
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function exactSlugLookup(): array
    {
        return $this->priorityAnswerPathCollection()
            ->filter(fn (array $path): bool => filled($path['slug'] ?? null))
            ->mapWithKeys(fn (array $path): array => [
                strtolower((string) $path['slug']) => [
                    'url' => (string) $path['url'],
                    'type' => (string) ($path['type'] ?? ''),
                    'title' => (string) ($path['title'] ?? ''),
                    'cluster' => (string) ($path['cluster'] ?? ''),
                    'intent' => (string) ($path['intent'] ?? ''),
                    'priority' => (int) ($path['priority'] ?? $path['priority_score'] ?? 0),
                    'entities' => array_values(array_filter(array_map(
                        static fn (mixed $entity): string => trim((string) $entity),
                        (array) ($path['entities'] ?? [])
                    ))),
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function priorityAnswerPathLines(int $limit = 12): array
    {
        return $this->priorityAnswerPathCollection()
            ->take($limit)
            ->map(function (array $path): string {
                $label = ucfirst(str_replace('_', ' ', (string) $path['category']));

                return '- [' . $label . '] ' . $path['title'] . ': ' . $path['url'];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{intent: string, preferred_entrypoint: string, rule: string, query_examples: list<string>}>
     */
    private function priorityEntrypointStrategy(): array
    {
        return [
            [
                'intent' => 'book-or-diagnose',
                'preferred_entrypoint' => RsUri::service('diagnostyka-komputerowa'),
                'rule' => 'Use an exact service page when the query is already asking for a workshop capability or booking intent.',
                'query_examples' => [
                    'diagnostyka komputerowa gdansk',
                    'warsztat diagnostyka silnika',
                    'mechanik od check engine',
                ],
            ],
            [
                'intent' => 'symptom-to-service',
                'preferred_entrypoint' => RsUri::problem('auto-traci-moc'),
                'rule' => 'Use a symptom page when the user describes what the car does but does not yet know the repair name.',
                'query_examples' => [
                    'auto traci moc',
                    'silnik slaby przy przyspieszaniu',
                    'samochod nie jedzie jak trzeba',
                ],
            ],
            [
                'intent' => 'fault-code-answer',
                'preferred_entrypoint' => RsUri::dtcCode('P0299'),
                'rule' => 'Use a DTC landing when the query starts from a fault code or an exact diagnostic code family.',
                'query_examples' => [
                    'P0299',
                    'P0401',
                    'P13DF',
                ],
            ],
            [
                'intent' => 'proof-and-root-cause',
                'preferred_entrypoint' => RsUri::repairReportsHub(),
                'rule' => 'Use repair reports when the user needs a real workshop case, proof trail or root-cause example.',
                'query_examples' => [
                    'przyklad naprawy',
                    'realny przypadek diagnostyczny',
                    'co bylo przyczyna usterki',
                ],
            ],
        ];
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
                'cluster' => 'service-money-path',
                'rule' => 'When local diagnostic or booking intent is explicit, exit the homepage immediately to the diagnostics service page.',
                'query_examples' => [
                    'diagnostyka komputerowa',
                    'mechanik gdansk diagnostyka',
                    'warsztat check engine',
                ],
            ],
            [
                'intent' => 'symptom-loss-of-power',
                'preferred_url' => RsUri::problem('auto-traci-moc'),
                'cluster' => 'symptom-entrypoint',
                'rule' => 'Use a symptom-first page when the query says the car lost power but does not yet contain a fault code.',
                'query_examples' => [
                    'auto traci moc',
                    'brak mocy diesel',
                    'slabo przyspiesza',
                ],
            ],
            [
                'intent' => 'symptom-underboost',
                'preferred_url' => RsUri::problem('brak-doladowania-turbo'),
                'cluster' => 'symptom-entrypoint',
                'rule' => 'Turbo and underboost phrasing should exit the homepage to the dedicated boost-loss problem page.',
                'query_examples' => [
                    'brak doladowania turbo',
                    'underboost',
                    'turbina slabo pompuje',
                ],
            ],
            [
                'intent' => 'symptom-alternator-charging',
                'preferred_url' => RsUri::problem('problemy-z-alternatorem'),
                'cluster' => 'symptom-entrypoint',
                'rule' => 'Charging and alternator symptoms should route to the exact alternator problem page, not to a generic service hub.',
                'query_examples' => [
                    'problem z alternatorem',
                    'brak ladowania',
                    'kontrolka akumulatora',
                ],
            ],
            [
                'intent' => 'symptom-air-conditioning',
                'preferred_url' => RsUri::problem('klimatyzacja-nie-chodzi'),
                'cluster' => 'symptom-entrypoint',
                'rule' => 'Air conditioning symptom intent should leave the homepage for the exact AC problem page.',
                'query_examples' => [
                    'klimatyzacja nie dziala',
                    'klima nie chlodzi',
                    'problem z klimatyzacja w aucie',
                ],
            ],
            [
                'intent' => 'fault-code-answer',
                'preferred_url' => RsUri::dtcCode('P0299'),
                'cluster' => 'fault-code-answer',
                'rule' => 'Any exact DTC intent should exit directly to a code page or DTC family page, never stay on the homepage.',
                'query_examples' => [
                    'P0299',
                    'P0401',
                    'kod bledu silnika',
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
                'best_clusters' => ['service-money-path', 'symptom-entrypoint', 'fault-code-answer'],
                'rule' => 'Interactive user fetches should leave the homepage quickly for exact service, symptom or DTC pages with the shortest answer path.',
            ],
            [
                'agent' => 'GPTBot',
                'preferred_first_hop' => RsUri::dtcHub(),
                'best_clusters' => ['fault-code-answer', 'service-money-path', 'symptom-entrypoint'],
                'rule' => 'Training crawlers should prioritize exact DTC, service and symptom pages over broad brand-only surfaces.',
            ],
            [
                'agent' => 'OAI-SearchBot',
                'preferred_first_hop' => RsUri::service('diagnostyka-komputerowa'),
                'best_clusters' => ['service-money-path', 'symptom-entrypoint'],
                'rule' => 'Search-focused OpenAI crawlers should fetch canonical money paths and symptom paths before generic hubs.',
            ],
            [
                'agent' => 'Bingbot',
                'preferred_first_hop' => RsUri::service('diagnostyka-komputerowa'),
                'best_clusters' => ['service-money-path', 'fault-code-answer'],
                'rule' => 'Bing should see the strongest local service entry points and exact DTC landings before the homepage becomes the terminal node.',
            ],
            [
                'agent' => 'ClaudeBot',
                'preferred_first_hop' => RsUri::problem('auto-traci-moc'),
                'best_clusters' => ['symptom-entrypoint', 'proof-and-root-cause'],
                'rule' => 'Gateway-routed Anthropic traffic should be nudged from the homepage into symptom and proof paths with strong diagnostic intent.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function priorityAnswerClusters(int $perCategory = 4): array
    {
        return $this->priorityAnswerPathCollection()
            ->groupBy(fn (array $path) => $path['category'])
            ->map(function (Collection $paths, string $category) use ($perCategory): array {
                $label = match ($category) {
                    'service' => 'Service money paths',
                    'problem' => 'Problem-first paths',
                    'dtc' => 'Fault-code paths',
                    'repair_report' => 'Proof and repair-report paths',
                    'blog_post' => 'Editorial support paths',
                    default => ucfirst(str_replace('_', ' ', $category)),
                };

                $intent = match ($category) {
                    'service' => 'book-or-diagnose',
                    'problem' => 'symptom-to-service',
                    'dtc' => 'fault-code-answer',
                    'repair_report' => 'proof-and-root-cause',
                    'blog_post' => 'educational-support',
                    default => 'general-discovery',
                };

                $rule = match ($category) {
                    'service' => 'Prefer these URLs when commercial or workshop-intent is explicit.',
                    'problem' => 'Prefer these URLs when the query describes a symptom instead of a repair name.',
                    'dtc' => 'Prefer these URLs when the query contains a DTC code or exact fault-code intent.',
                    'repair_report' => 'Prefer these URLs when evidence, real measurements or workshop proof matters.',
                    'blog_post' => 'Use these pages as support after the core answer path is already established.',
                    default => 'Use the most specific page family instead of a generic hub whenever possible.',
                };

                return [
                    'category' => $category,
                    'label' => $label,
                    'intent' => $intent,
                    'rule' => $rule,
                    'paths' => $paths->take($perCategory)->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function serviceProblems(string $slug): array
    {
        return match ($slug) {
            'diagnostyka-komputerowa' => [
                RsUri::problem('kontrolka-silnika-swieci'),
                RsUri::problem('auto-traci-moc'),
                RsUri::problem('nierownomierna-praca-silnika'),
                RsUri::problem('problemy-z-odpalaniem'),
            ],
            'hamulce' => [RsUri::problem('problemy-z-hamulcami'), RsUri::problem('auto-sciaga-przy-hamowaniu')],
            'zawieszenie' => [RsUri::problem('stuki-w-zawieszeniu'), RsUri::problem('luzy-na-maglownicy')],
            'klimatyzacja-ozonowanie' => [
                RsUri::problem('klimatyzacja-nie-chodzi'),
                RsUri::problem('klimatyzacja-nie-chlodzi-na-postoju'),
                RsUri::problem('klima-smierdzi-octem'),
                RsUri::problem('objawy-uszkodzonej-sprezarki-klimatyzacji'),
            ],
            'dpf-adblue' => [RsUri::problem('auto-traci-moc'), RsUri::problem('bialy-dym-z-wydechu')],
            'skrzynie-biegow' => [RsUri::problem('szarpanie-przy-ruszaniu')],
            default => [],
        };
    }

    private function serviceIntent(string $slug, string $fallbackName): string
    {
        return match ($slug) {
            'diagnostyka-komputerowa' => 'computer diagnostics, check engine, fault codes, live data, adaptation',
            'mechanika-ogolna' => 'local mechanic, car repair, mechanical repairs',
            'hamulce' => 'brake repair, brake service',
            'zawieszenie' => 'suspension repair, wheel alignment, geometry',
            'klimatyzacja-ozonowanie' => 'air conditioning service, leak checks, ozone treatment',
            'dpf-adblue' => 'emissions system diagnostics and repair',
            'turbosprezarka' => 'turbo diagnostics, boost issues, smoke, power loss',
            'rozrzady' => 'timing belt and timing chain replacement',
            'skrzynie-biegow' => 'gearbox diagnostics, ATF service, gearbox repair',
            'sprzegla' => 'clutch, dual mass, driveline take-off issues',
            'uklad-wydechowy' => 'exhaust diagnostics and repair',
            'elektryka-pojazdowa' => 'electrical diagnostics, starting problems, control modules',
            'wulkanizacja' => 'tyre fitting, balancing, tyre repair, TPMS',
            'hotel-opon' => 'seasonal tyre storage',
            'przeglady-okresowe' => 'periodic service, oil service, filter replacement',
            'obsluga-flotowa-b2b' => 'fleet maintenance and business accounts',
            default => strtolower($fallbackName),
        };
    }

    private function urlNode(string $loc, string $changefreq, string $priority, CarbonInterface $lastmod): string
    {
        return implode('', [
            "<url>\n",
            '  <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n",
            '  <lastmod>' . $lastmod->toW3cString() . "</lastmod>\n",
            '  <changefreq>' . $changefreq . "</changefreq>\n",
            '  <priority>' . $priority . "</priority>\n",
            "</url>\n",
        ]);
    }
}

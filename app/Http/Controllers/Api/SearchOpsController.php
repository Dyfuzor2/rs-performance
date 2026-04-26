<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Support\N8nInternalAuth;
use App\Support\Ops\OpsStatusSnapshotService;
use App\Support\SearchOps\MicroExpansionDraftService;
use App\Support\SearchOps\OpportunityIntentClassifier;
use App\Support\SearchOps\OpportunityNormalizer;
use App\Support\SearchOps\OpportunityRouter;
use App\Support\SearchOps\OpportunityScorer;
use App\Support\SearchOps\OpportunitySnapshotStore;
use App\Support\SearchOps\PromotionGuardService;
use App\Support\SearchOps\SearchConsoleService;
use App\Support\SearchOps\SupportPageDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

final class SearchOpsController extends Controller
{
    public function __construct(
        private readonly OpportunityNormalizer $normalizer,
        private readonly OpportunityIntentClassifier $classifier,
        private readonly OpportunityScorer $scorer,
        private readonly OpportunityRouter $router,
        private readonly PromotionGuardService $guard,
        private readonly OpportunitySnapshotStore $store,
        private readonly MicroExpansionDraftService $microExpansionDrafts,
        private readonly SupportPageDraftService $supportPageDrafts,
        private readonly SearchConsoleService $searchConsole,
        private readonly OpsStatusSnapshotService $opsSnapshots,
    ) {}

    public function fetchSignals(Request $request): JsonResponse
    {
        N8nInternalAuth::authorize($request);

        $validated = $request->validate([
            'source' => ['required', 'string', 'in:gsc,bing'],
            'days' => ['sometimes', 'integer', 'min:3', 'max:90'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $source = (string) $validated['source'];
        $days = (int) ($validated['days'] ?? 28);
        $limit = (int) ($validated['limit'] ?? 25);
        $fallbackPage = (string) config('search_ops.site_url');

        $opportunities = match ($source) {
            'gsc' => $this->normalizer->fromGscRows(
                rows: (array) ($this->searchConsole->fetchSignals($days, $limit)['top_queries'] ?? []),
                fallbackPage: $fallbackPage,
            ),
            default => $this->normalizer->fromBingSnapshot(
                (array) ($this->opsSnapshots->snapshotAll()['bing_webmaster'] ?? [])
            ),
        };

        $items = $this->enrichOpportunities($opportunities);
        $snapshot = $this->store->storeHarvest($source, $items);

        return response()->json([
            'status' => 'ok',
            'source' => $source,
            'stored' => (int) ($snapshot['stored'] ?? count($items)),
            'items' => $items,
        ]);
    }

    /**
     * Same work as `php artisan search-ops:gsc-fetch` — writes `search_ops.status_path` (GSC karty / ops).
     * POST /search-ops/fetch-signals z source=gsc aktualizuje tylko harvest w opportunities.
     */
    public function syncGscStatus(Request $request): JsonResponse
    {
        N8nInternalAuth::authorize($request);

        $validated = $request->validate([
            'days' => ['sometimes', 'integer', 'min:2', 'max:90'],
            'limit' => ['sometimes', 'integer', 'min:5', 'max:100'],
        ]);

        $days = (int) ($validated['days'] ?? 14);
        $limit = (int) ($validated['limit'] ?? 25);

        $exit = Artisan::call('search-ops:gsc-fetch', [
            '--days' => $days,
            '--limit' => $limit,
        ]);

        $output = trim(Artisan::output());
        $path = (string) config('search_ops.status_path');

        if (! File::isFile($path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'GSC status file was not written.',
                'exit_code' => $exit,
                'output' => $output,
            ], 500);
        }

        $raw = json_decode((string) File::get($path), true);
        if (! is_array($raw)) {
            return response()->json([
                'status' => 'error',
                'message' => 'GSC status file is not valid JSON.',
                'exit_code' => $exit,
                'output' => $output,
            ], 500);
        }
        /** @var array<string, mixed> $snapshot */
        $snapshot = $raw;
        $gscOk = $exit === 0 && (string) ($snapshot['status'] ?? '') === 'ok';

        return response()->json([
            'status' => $gscOk ? 'ok' : 'error',
            'fetched_at' => $snapshot['fetched_at'] ?? null,
            'gsc_status' => $snapshot['status'] ?? null,
            'exit_code' => $exit,
            'output' => $output !== '' ? $output : null,
            'error' => $snapshot['error'] ?? null,
        ], $gscOk ? 200 : 502);
    }

    public function harvest(Request $request): JsonResponse
    {
        N8nInternalAuth::authorize($request);

        $validated = $request->validate([
            'source' => ['required', 'string', 'in:gsc,bing'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.url' => ['nullable', 'url'],
            'items.*.query' => ['nullable', 'string'],
            'items.*.page' => ['nullable', 'string'],
            'items.*.clicks' => ['nullable', 'numeric'],
            'items.*.impressions' => ['nullable', 'numeric'],
            'items.*.ctr' => ['nullable', 'numeric'],
            'items.*.position' => ['nullable', 'numeric'],
        ]);

        $fallbackPage = (string) config('search_ops.site_url');
        $opportunities = ((string) $validated['source'] === 'bing')
            ? $this->normalizer->fromBingSnapshot(['submitted_urls' => (array) $validated['items']])
            : $this->normalizer->fromGscRows(
                rows: (array) $validated['items'],
                fallbackPage: $fallbackPage,
            );

        $items = $this->enrichOpportunities($opportunities);

        $snapshot = $this->store->storeHarvest((string) $validated['source'], $items);

        return response()->json([
            'status' => 'ok',
            'stored' => (int) ($snapshot['stored'] ?? count($items)),
            'source' => $validated['source'],
        ]);
    }

    public function opportunities(Request $request): JsonResponse
    {
        N8nInternalAuth::authorize($request);

        $validated = $request->validate([
            'decision' => ['sometimes', 'string', 'in:micro_expansion,new_support_page,reject'],
            'tier' => ['sometimes', 'string', 'in:tier_a,tier_b,out_of_band'],
            'intent' => ['sometimes', 'string', 'in:service,problem,mixed,unknown'],
            'source' => ['sometimes', 'string', 'in:gsc,bing'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $snapshot = $this->store->readHarvest();
        $limit = (int) ($validated['limit'] ?? 25);

        $items = collect((array) ($snapshot['items'] ?? []))
            ->filter(function (array $item) use ($validated): bool {
                if (($validated['decision'] ?? null) !== null
                    && ($item['route']['decision'] ?? null) !== $validated['decision']) {
                    return false;
                }

                if (($validated['tier'] ?? null) !== null
                    && ($item['score']['tier'] ?? null) !== $validated['tier']) {
                    return false;
                }

                if (($validated['intent'] ?? null) !== null
                    && ($item['intent'] ?? null) !== $validated['intent']) {
                    return false;
                }

                if (($validated['source'] ?? null) !== null
                    && ($item['source'] ?? null) !== $validated['source']) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn (array $item): float => (float) ($item['score']['score'] ?? 0))
            ->values()
            ->take($limit)
            ->all();

        return response()->json([
            'status' => 'ok',
            'stored' => (int) ($snapshot['stored'] ?? 0),
            'last_harvest_at' => $snapshot['last_harvest_at'] ?? null,
            'items' => $items,
        ]);
    }

    public function promote(Request $request): JsonResponse
    {
        N8nInternalAuth::authorize($request);

        $validated = $request->validate([
            'query' => ['required', 'string'],
            'decision' => ['required', 'string', 'in:micro_expansion,new_support_page,reject'],
            'tier' => ['required', 'string', 'in:tier_a,tier_b,out_of_band'],
            'target_path' => ['nullable', 'string'],
            'has_existing_target' => ['sometimes', 'boolean'],
            'requires_title_rewrite' => ['sometimes', 'boolean'],
            'cannibalization_risk' => ['sometimes', 'string', 'in:low,medium,high'],
            'estimated_chars' => ['sometimes', 'integer', 'min:0'],
            'intent' => ['sometimes', 'string'],
        ]);

        if ($validated['decision'] === 'new_support_page') {
            $draft = $this->supportPageDrafts->build(
                query: (string) $validated['query'],
                intent: (string) ($validated['intent'] ?? 'service'),
            );

            $this->store->storePromotion([
                'status' => 'review_required',
                'query' => $validated['query'],
                'decision' => $validated['decision'],
                'tier' => $validated['tier'],
                'target_path' => $validated['target_path'] ?? null,
                'draft' => $draft,
            ]);

            return response()->json([
                'status' => 'review_required',
                'query' => $validated['query'],
                'decision' => $validated['decision'],
                'tier' => $validated['tier'],
                'target_path' => $validated['target_path'] ?? null,
                'draft' => $draft,
            ]);
        }

        $guard = $this->guard->decide(
            decision: (string) $validated['decision'],
            tier: (string) $validated['tier'],
            hasExistingTarget: (bool) ($validated['has_existing_target'] ?? ! empty($validated['target_path'])),
            requiresTitleRewrite: (bool) ($validated['requires_title_rewrite'] ?? false),
            cannibalizationRisk: (string) ($validated['cannibalization_risk'] ?? 'low'),
            estimatedChars: (int) ($validated['estimated_chars'] ?? 420),
        );

        if (! $guard['auto_publish']) {
            $response = [
                'status' => 'review_required',
                'query' => $validated['query'],
                'decision' => $validated['decision'],
                'tier' => $validated['tier'],
                'target_path' => $validated['target_path'] ?? null,
                'guard' => $guard,
            ];

            $this->store->storePromotion($response + [
                'query' => $validated['query'],
                'decision' => $validated['decision'],
                'tier' => $validated['tier'],
            ]);

            return response()->json($response);
        }

        $draft = $this->microExpansionDrafts->build(
            query: (string) $validated['query'],
            targetPath: (string) ($validated['target_path'] ?? ''),
        );

        $response = [
            'status' => 'ready_for_apply',
            'query' => $validated['query'],
            'decision' => $validated['decision'],
            'tier' => $validated['tier'],
            'target_path' => $validated['target_path'] ?? null,
            'guard' => $guard,
            'draft' => $draft,
        ];

        $this->store->storePromotion($response + [
            'query' => $validated['query'],
            'decision' => $validated['decision'],
            'tier' => $validated['tier'],
        ]);

        return response()->json($response);
    }

    /**
     * @param  array<int, mixed>  $opportunities
     * @return array<int, array<string, mixed>>
     */
    private function enrichOpportunities(array $opportunities): array
    {
        return collect($opportunities)
            ->map(function ($opportunity): array {
                $intent = $this->classifier->classify($opportunity->query);
                $score = $this->scorer->score(
                    position: $opportunity->position,
                    impressions: $opportunity->impressions,
                    ctr: $opportunity->ctr,
                    intent: $intent,
                );
                $route = $this->router->route(
                    query: $opportunity->query,
                    intent: $intent,
                    tier: (string) $score['tier'],
                );
                $guard = $this->guard->decide(
                    decision: (string) $route['decision'],
                    tier: (string) $score['tier'],
                    hasExistingTarget: $route['target_path'] !== null,
                    requiresTitleRewrite: false,
                    cannibalizationRisk: 'low',
                    estimatedChars: 420,
                );

                return [
                    'source' => $opportunity->source,
                    'query' => $opportunity->query,
                    'page' => $opportunity->page,
                    'clicks' => $opportunity->clicks,
                    'impressions' => $opportunity->impressions,
                    'ctr' => $opportunity->ctr,
                    'position' => $opportunity->position,
                    'intent' => $intent,
                    'score' => $score,
                    'route' => $route,
                    'guard' => $guard,
                ];
            })
            ->values()
            ->all();
    }
}

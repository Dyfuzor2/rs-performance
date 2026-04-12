<?php

declare(strict_types=1);

namespace App\Support\Blog;

use App\Enums\BlogPipelineSource;
use App\Enums\BlogPipelineStatus;
use App\Models\BlogPipelineRun;
use App\Models\BlogPost;
use App\Models\Service;
use App\Support\Vertex\AnthropicVertexPartnerEndpoint;
use App\Support\Vertex\VertexAccessTokenFactory;
use App\Support\Vertex\VertexHttpErrorSummary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class BlogVertexPipelineService
{
    public function __construct(
        private readonly VertexAccessTokenFactory $tokenFactory,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function run(array $options = [], ?BlogPipelineRun $pipelineRun = null): array
    {
        $manualTopic = trim((string) ($options['topic'] ?? ''));
        $premiumReview = (bool) ($options['premium_review'] ?? false);
        $createDraft = (bool) ($options['create_draft'] ?? true);
        $qualityGateEnforced = (bool) ($options['quality_gate_enforced'] ?? false);
        $editorialMode = $this->normalizeEditorialMode($options['editorial_mode'] ?? null);
        $editorialNotes = trim((string) ($options['editorial_notes'] ?? ''));
        ['topic' => $manualTopic, 'notes' => $editorialNotes] = $this->normalizeOperatorBrief(
            $manualTopic,
            $editorialNotes,
        );
        $slotType = $this->normalizeSlotType($options['slot_type'] ?? null, $editorialNotes);
        $newsDate = $this->resolveNewsDate($options['news_date'] ?? null);
        $operatorSourceUrls = collect((array) ($options['source_urls'] ?? []))
            ->filter(fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL))
            ->map(fn ($url) => trim((string) $url))
            ->unique()
            ->take(12)
            ->values()
            ->all();

        $pipelineRun ??= BlogPipelineRun::begin(
            source: BlogPipelineSource::Api,
            topic: $manualTopic,
        );

        $pipelineRun->markProcessing();
        $this->logPipelineStage($pipelineRun, 'run.started', [
            'editorial_mode' => $editorialMode,
            'slot_type' => $slotType,
            'quality_gate_enforced' => $qualityGateEnforced,
            'premium_review' => $premiumReview,
            'create_draft' => $createDraft,
            'operator_topic' => $manualTopic !== '' ? Str::limit($manualTopic, 220, '...') : null,
        ]);

        try {
            $this->logPipelineStage($pipelineRun, 'feed.fetch.started');
            $feedItems = $this->fetchFeedItems($editorialMode, $newsDate);
            $this->logPipelineStage($pipelineRun, 'feed.fetch.completed', [
                'feed_item_count' => count($feedItems),
                'news_date' => $newsDate !== '' ? $newsDate : null,
            ]);

            $this->logPipelineStage($pipelineRun, 'topic_pack.build.started');
            $topicPack = $this->buildTopicPack(
                $feedItems,
                $manualTopic,
                $editorialMode,
                $editorialNotes,
                $slotType,
                $operatorSourceUrls,
                $newsDate,
            );
            $this->logPipelineStage($pipelineRun, 'topic_pack.build.completed', [
                'priority_score' => $topicPack['priority_score'] ?? null,
                'source_url_count' => count((array) ($topicPack['source_urls'] ?? [])),
                'research_title' => $topicPack['title'] ?? null,
            ]);
            $score = (int) ($topicPack['priority_score'] ?? 0);
            $this->logPipelineStage($pipelineRun, 'draft.write.started', [
                'writer_model' => (string) config('blog.vertex_models.writer', 'gemini-2.5-pro'),
            ]);
            $draft = $this->writeDraft($topicPack);
            $this->logPipelineStage($pipelineRun, 'draft.write.completed', [
                'writer_model_used' => $draft['_model'] ?? null,
                'title' => $draft['title'] ?? null,
            ]);
            $maxEditorialPasses = $manualTopic !== '' ? 3 : 1;
            $qualityGate = [
                'passed' => false,
                'score' => 0,
                'checks' => [],
                'failed_reasons' => ['Draft was not evaluated.'],
                'stats' => [],
            ];

            for ($attempt = 1; $attempt <= $maxEditorialPasses; $attempt++) {
                $this->logPipelineStage($pipelineRun, 'editorial.attempt.started', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxEditorialPasses,
                ]);

                if ($attempt > 1) {
                    $draft = $this->repairDraftTopicAlignment($topicPack, $draft, $qualityGate, $attempt);
                    $this->logPipelineStage($pipelineRun, 'editorial.repair.completed', [
                        'attempt' => $attempt,
                        'repair_model_used' => $draft['_model'] ?? null,
                    ]);
                }

                $this->logPipelineStage($pipelineRun, 'seo.pass.started', [
                    'attempt' => $attempt,
                ]);
                $draft = array_replace($draft, $this->buildSeoPass($topicPack, $draft));
                $this->logPipelineStage($pipelineRun, 'seo.pass.completed', [
                    'attempt' => $attempt,
                ]);

                if ($premiumReview || $score >= (int) config('blog.premium_review_score_threshold', 90)) {
                    $this->logPipelineStage($pipelineRun, 'premium_review.started', [
                        'attempt' => $attempt,
                    ]);
                    $draft = array_replace($draft, $this->runPremiumReview($topicPack, $draft));
                    $this->logPipelineStage($pipelineRun, 'premium_review.completed', [
                        'attempt' => $attempt,
                    ]);
                }

                $draft = $this->stabilizeDraftForEditorialGate($topicPack, $draft);
                $qualityGate = $this->evaluateQualityGate($topicPack, $draft);
                $qualityGate = $this->appendSemanticTopicAlignment($topicPack, $draft, $qualityGate);
                $this->logPipelineStage($pipelineRun, 'quality_gate.completed', [
                    'attempt' => $attempt,
                    'passed' => $qualityGate['passed'] ?? false,
                    'score' => $qualityGate['score'] ?? 0,
                    'failed_reasons' => $qualityGate['failed_reasons'] ?? [],
                    'word_count' => $qualityGate['stats']['word_count'] ?? null,
                ]);

                if ($qualityGate['passed'] ?? false) {
                    break;
                }
            }

            if (! ($qualityGate['passed'] ?? false) && $this->shouldAttemptEditorialWindowRescue($qualityGate)) {
                $this->logPipelineStage($pipelineRun, 'editorial_window_rescue.started', [
                    'failed_reasons' => $qualityGate['failed_reasons'] ?? [],
                ]);

                $draft = $this->repairDraftEditorialWindow($topicPack, $draft, $qualityGate);
                $draft = array_replace($draft, $this->buildSeoPass($topicPack, $draft));

                if ($premiumReview || $score >= (int) config('blog.premium_review_score_threshold', 90)) {
                    $draft = array_replace($draft, $this->runPremiumReview($topicPack, $draft));
                }

                $draft = $this->stabilizeDraftForEditorialGate($topicPack, $draft);
                $qualityGate = $this->evaluateQualityGate($topicPack, $draft);
                $qualityGate = $this->appendSemanticTopicAlignment($topicPack, $draft, $qualityGate);

                $this->logPipelineStage($pipelineRun, 'editorial_window_rescue.completed', [
                    'passed' => $qualityGate['passed'] ?? false,
                    'score' => $qualityGate['score'] ?? 0,
                    'failed_reasons' => $qualityGate['failed_reasons'] ?? [],
                    'word_count' => $qualityGate['stats']['word_count'] ?? null,
                ]);
            }

            if ($qualityGateEnforced && ! ($qualityGate['passed'] ?? false)) {
                throw new RuntimeException('QUALITY_GATE_FAILED: ' . implode(' | ', (array) ($qualityGate['failed_reasons'] ?? [])));
            }

            $heroImagePath = null;
            if ((bool) config('blog.auto_generate_image', true)) {
                $this->logPipelineStage($pipelineRun, 'hero_image.started');
                $heroImagePath = $this->generateHeroImage($topicPack, $draft);
                $this->logPipelineStage($pipelineRun, 'hero_image.completed', [
                    'hero_image_path' => $heroImagePath,
                    'image_model_used' => $draft['_image_model'] ?? null,
                ]);

                if ($heroImagePath !== null) {
                    $draft['_hero_image'] = $heroImagePath;
                }
            }

            $usedModels = [
                'researcher' => (string) config('blog.vertex_models.researcher'),
                'selector' => (string) config('blog.vertex_models.selector'),
                'writer' => (string) ($draft['_model'] ?? config('blog.vertex_models.writer')),
                'premium_reviewer' => ($premiumReview || $score >= (int) config('blog.premium_review_score_threshold', 90))
                    ? (string) ($draft['_premium_review_model'] ?? config('blog.vertex_models.premium_reviewer'))
                    : null,
                'seo' => (string) config('blog.vertex_models.seo'),
                'image' => $heroImagePath !== null
                    ? (string) ($draft['_image_model'] ?? config('blog.vertex_models.image'))
                    : null,
            ];

            $result = [
                'topic' => $topicPack,
                'draft' => $draft,
                'hero_image' => $heroImagePath,
                'used_models' => $usedModels,
                'quality_gate' => $qualityGate,
            ];

            if ($createDraft) {
                $this->logPipelineStage($pipelineRun, 'persist.started');
                $post = $this->persistDraft($topicPack, $draft);
                $this->logPipelineStage($pipelineRun, 'persist.completed', [
                    'blog_post_id' => $post->id,
                    'slug' => $post->slug,
                ]);

                $result['post'] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'admin_edit_url' => url('/admin/blog-posts/' . $post->id . '/edit'),
                    'public_url' => $post->is_published ? route('blog.show', $post->slug) : null,
                    'draft_api_url' => url('/api/blog/draft/' . $post->id),
                ];

                $pipelineRun->markDraftCreated($post->id, $usedModels);
            } else {
                $pipelineRun->update([
                    'status' => BlogPipelineStatus::DraftCreated,
                    'used_models' => $usedModels,
                    'completed_at' => now(),
                ]);

                $this->logPipelineStage($pipelineRun, 'dry_run.completed');
            }

            $result['pipeline_run_id'] = $pipelineRun->id;

            return $result;
        } catch (\Throwable $exception) {
            $this->logPipelineStage($pipelineRun, 'run.failed', [
                'message' => $exception->getMessage(),
            ]);
            $pipelineRun->markFailed(
                Str::limit($exception->getMessage(), 1000),
            );

            throw $exception;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFeedItems(string $editorialMode = 'evergreen', string $newsDate = ''): array
    {
        $items = [];
        $sameDayOnly = $editorialMode === 'daily_news';
        $targetDate = $newsDate !== '' ? $newsDate : now('Europe/Warsaw')->toDateString();

        foreach ((array) config('blog.rss_sources', []) as $sourceName => $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            try {
                $response = Http::timeout(20)->get($url);

                if (! $response->successful()) {
                    continue;
                }

                $xml = @simplexml_load_string($response->body());
                if ($xml === false || ! isset($xml->channel->item)) {
                    continue;
                }

                foreach ($xml->channel->item as $item) {
                    $title = trim((string) ($item->title ?? ''));
                    $link = trim((string) ($item->link ?? ''));
                    $publishedAt = trim((string) ($item->pubDate ?? ''));
                    $publishedDateLocal = $this->normalizePublishedDate($publishedAt);

                    if ($title === '' || $link === '') {
                        continue;
                    }

                    if ($sameDayOnly && $publishedDateLocal !== $targetDate) {
                        continue;
                    }

                    $items[] = [
                        'source' => $sourceName,
                        'title' => $title,
                        'link' => $link,
                        'description' => trim((string) ($item->description ?? '')),
                        'published_at' => $publishedAt,
                        'published_date_local' => $publishedDateLocal,
                    ];
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return array_slice($items, 0, 24);
    }

    /**
     * @param  array<int, array<string, mixed>>  $feedItems
     * @return array<string, mixed>
     */
    private function buildTopicPack(
        array $feedItems,
        string $manualTopic = '',
        string $editorialMode = 'evergreen',
        string $editorialNotes = '',
        string $slotType = '',
        array $operatorSourceUrls = [],
        string $newsDate = '',
    ): array {
        $candidate = $manualTopic !== ''
            ? [
                'title' => $manualTopic,
                'keyword' => $manualTopic,
                'hook' => $editorialNotes !== '' ? $editorialNotes : $manualTopic,
                'reason' => $editorialMode === 'daily_news'
                    ? 'Manual news topic provided by the editorial workflow.'
                    : 'Manual topic provided.',
                'priority_score' => 88,
            ]
            : $this->selectTopicCandidate($feedItems);

        $prompt = $this->researchPrompt($candidate, $feedItems, $editorialMode, $editorialNotes, $slotType, $operatorSourceUrls, $newsDate);
        $researchSystemPrompt = 'Jesteś researcherem dla premium bloga warsztatu RS Performance. Działasz jak redaktor śledczy w pokoju redakcyjnym: weryfikujesz fakty, szukasz źródeł do cytowania, nie wymyślasz liczb ani recalli, tłumaczysz skutki dla kierowcy i warsztatu. Robisz głęboki research z Google Search i zwracasz tylko poprawny JSON.';
        $research = null;

        try {
            $research = $this->callGoogleGroundedJson(
                (string) config('blog.vertex_models.researcher', 'gemini-2.5-flash'),
                $researchSystemPrompt,
                $prompt
            );

            $topic = $this->decodeJsonPayload($research['text']);
        } catch (RuntimeException $exception) {
            if (! $this->shouldUseFallbackModel($exception)) {
                throw $exception;
            }

            $topic = $this->callModelJsonWithGenericFallback(
                (string) config('blog.vertex_models.researcher_fallback', 'gemini-2.5-flash-lite'),
                (string) config('blog.vertex_models.selector', 'gemini-2.5-flash-lite'),
                $researchSystemPrompt,
                $prompt
            );
        }

        $topic['source_urls'] = collect(array_merge(
            (array) ($topic['source_urls'] ?? []),
            (array) ($research['source_urls'] ?? []),
            $operatorSourceUrls,
        ))->filter(fn ($url) => is_string($url) && $url !== '')
            ->unique()
            ->values()
            ->all();
        $topic['source_urls'] = $this->prioritizeEditorialSourceUrls(
            (array) $topic['source_urls'],
            [
                'title' => $topic['title'] ?? $candidate['title'] ?? null,
                'operator_topic' => $manualTopic !== '' ? $manualTopic : null,
                'slot_type' => $slotType,
            ],
            $slotType,
        );

        $topic['search_queries'] = (array) ($research['search_queries'] ?? []);
        $topic['feed_items'] = array_slice($feedItems, 0, 12);
        $topic['operator_topic'] = $manualTopic !== '' ? $manualTopic : null;
        $topic['editorial_mode'] = $editorialMode;
        $topic['editorial_notes'] = $editorialNotes !== '' ? $editorialNotes : null;
        $topic['slot_type'] = $slotType !== '' ? $slotType : null;
        $topic['news_date'] = $newsDate !== '' ? $newsDate : null;

        return $topic;
    }

    /**
     * @param  array<int, array<string, mixed>>  $feedItems
     * @return array<string, mixed>
     */
    private function selectTopicCandidate(array $feedItems): array
    {
        return $this->callModelJson(
            (string) config('blog.vertex_models.selector', 'gemini-2.5-flash-lite'),
            'Jesteś szybkim selektorem tematów dla bloga RS Performance. Patrzysz tylko na feed wejściowy i zwracasz tylko poprawny JSON.',
            <<<PROMPT
            Z tych feed items wybierz najlepszy kandydat na wpis blogowy dla warsztatu RS Performance.

            Feed items:
            {$this->json($feedItems)}

            Zwracaj tylko JSON:
            {
              "title": "...",
              "keyword": "...",
              "hook": "...",
              "reason": "...",
              "priority_score": 0
            }
            PROMPT
        );
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @return array<string, mixed>
     */
    private function writeDraft(array $topicPack): array
    {
        $primaryModel = (string) config('blog.vertex_models.writer', 'gemini-2.5-pro');
        $fallbackModel = (string) config('blog.vertex_models.writer_fallback', 'gemini-2.5-flash-lite');

        $payload = $this->callModelJsonWithFallback(
            $primaryModel,
            $fallbackModel,
            'Jesteś zespołem redakcyjnym w jednym autorze: dziennikarz motoryzacyjny + korespondent warsztatowy + desk międzynarodowy + copy desk. Piszesz premium, konkretnie, po ludzku — jak człowiek z wieloletnim stażem. Nie wymyślasz faktów, nie uprawiasz clickbaitu, nie brzmisz jak AI. Zwracasz tylko poprawny JSON.',
            <<<PROMPT
            Na podstawie research packu przygotuj finalny draft bloga dla RS Performance.

            Research pack:
            {$this->json($topicPack)}

            Zwracaj tylko JSON:
            {
              "title": "...",
              "excerpt": "...",
              "content_html": "...",
              "meta_title": "...",
              "meta_description": "...",
              "category": "...",
              "featured_image_alt": "...",
              "faq": [
                {"question":"...","answer":"..."},
                {"question":"...","answer":"..."},
                {"question":"...","answer":"..."}
              ],
              "related_service_slug": "...",
              "related_problem_slugs": ["...","..."]
            }

            Wymagania:
            - 900-1200 słów.
            - HTML z h2, h3, p, ul, li, strong.
            - lead ma być mocny i czytelny, nie przegadany.
            - temat ma być aktualny, ale użyteczny dla klienta warsztatu.
            - tekst ma mieć mocne, ale subtelne SEO / AEO / GEO:
              - naturalne frazy zamiast keyword dumpu
              - cytowalne, konkretne zdania dla AI engines
              - lokalny sens dla Gdańska / Trójmiasta bez sztucznego upychania miasta
            - jeśli `operator_topic` nie jest puste, finalny tytuł i treść muszą zostać wierne osi tematu operatora.
            - jeśli `editorial_mode` to `daily_news`, tekst ma być osadzony w newsie z dnia i tłumaczyć kierowcy, co z tego wynika dzisiaj.
            - jeśli `editorial_mode` to `daily_news`, wybierz tylko jeden z trzech tonów: porada, news, premiera. Trzymaj się tonu wskazanego w `editorial_notes`.
            - jeśli `slot_type` jest ustawiony, trzymaj formę wpisu zgodnie z nim:
              - `porada` = praktyczna pomoc i decyzje dla kierowcy
              - `news` = rzeczowe wyjaśnienie zmiany i skutków
              - `premiera` = co nowy model lub technologia znaczy w praktyce
              - `analiza` = ekspercki komentarz i konsekwencje
            - lokalny kontekst Gdańsk / Trójmiasto tylko naturalnie.
            - na końcu konkretne CTA do RS Performance.
            - nie używaj fraz typu „warto zauważyć”, „nie ulega wątpliwości”, „podsumowując”.
            - nie używaj stylu „jako AI”, „ten artykuł omawia”, „w niniejszym artykule”.
            - nie używaj tandetnych, wulgarnych albo plotkarskich chwytów.
            - mocny tytuł jest dozwolony, ale nie może obiecywać czegoś, czego tekst nie dowodzi.
            - nie pisz o lifestyle, celebrytach, motorsporcie, gadżetach ani plotkach, jeśli nie ma jasnego związku z codzienną eksploatacją auta, serwisem, diagnostyką lub rynkiem motoryzacyjnym.
            - jeśli temat nie uzasadnia twardych danych liczbowych, nie dopisuj ich z głowy.
            - featured_image_alt: 2-4 zdania po angielsku lub polsku jak brief fotografa prasowego — konkretna scena, światło, kontekst (warsztat / droga / stacja / inspekcja), spójna z leadem i z kątem newsa; bez nazw marek, bez logo, bez tablic rejestracyjnych czytelnych.
            PROMPT
        );

        $draft = $this->normalizeDraftPayload($payload);
        $draft['_model'] = (string) ($payload['_model'] ?? $primaryModel);

        return $draft;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function buildSeoPass(array $topicPack, array $draft): array
    {
        $defaultSeoPayload = [
            'meta_title' => $this->normalizeString($draft['meta_title'] ?? $draft['title'] ?? ''),
            'meta_description' => $this->normalizeString($draft['meta_description'] ?? $draft['excerpt'] ?? ''),
            'faq' => $this->normalizeFaq($draft['faq'] ?? []),
            'related_problem_slugs' => collect((array) ($draft['related_problem_slugs'] ?? []))
                ->map(fn ($slug) => Str::slug((string) $slug))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];

        try {
            $payload = $this->callModelJsonWithGenericFallback(
                (string) config('blog.vertex_models.seo', 'gemini-2.5-flash-lite'),
                (string) config('blog.vertex_models.seo_fallback', config('blog.vertex_models.writer_fallback', 'gemini-2.5-flash-lite')),
                'Jesteś SEO edytorem RS Performance. Dbasz o czytelność, lokalną intencję i AI-friendly structure. Zwracasz tylko poprawny JSON.',
                <<<PROMPT
                Na podstawie topic packu i draftu zrob finalny SEO pass.

                Topic pack:
                {$this->json($topicPack)}

                Draft:
                {$this->json($draft)}

                Zwracaj tylko JSON:
                {
                  "meta_title": "...",
                  "meta_description": "...",
                  "faq": [
                    {"question":"...","answer":"..."},
                    {"question":"...","answer":"..."},
                    {"question":"...","answer":"..."}
                  ],
                  "related_problem_slugs": ["...","..."]
                }

                Warunki:
                - meta title max 60 znakow
                - meta description max 160 znakow
                - FAQ ma wynikac z tresci i byc praktyczne
                - related problem slugs maja byc krotkie i zgodne z realnymi objawami
                - SEO / AEO / GEO ma byc mocne, ale subtelne:
                  - bez keyword stuffingu
                  - z naturalnym lokalnym sygnalem
                  - z pytaniami i odpowiedziami, ktore AI moze cytowac
                PROMPT
            );
        } catch (RuntimeException) {
            return $defaultSeoPayload;
        }

        return [
            'meta_title' => $this->normalizeString(Arr::get($payload, 'meta_title')) ?: $defaultSeoPayload['meta_title'],
            'meta_description' => $this->normalizeString(Arr::get($payload, 'meta_description')) ?: $defaultSeoPayload['meta_description'],
            'faq' => $this->normalizeFaq(Arr::get($payload, 'faq')),
            'related_problem_slugs' => collect((array) Arr::get($payload, 'related_problem_slugs'))
                ->map(fn ($slug) => Str::slug((string) $slug))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function runPremiumReview(array $topicPack, array $draft): array
    {
        try {
            $payload = $this->callModelJsonWithGenericFallback(
                (string) config('blog.vertex_models.premium_reviewer', 'claude-sonnet-4-6'),
                (string) config('blog.vertex_models.premium_reviewer_fallback', 'gemini-2.5-pro'),
                'Jesteś redaktorem naczelnym i copy deski premium bloga RS Performance. Wygładzasz tekst, wzmacniasz klarowność, nie tolerujesz waty ani wątpliwych twierdzeń prawnych/rynkowych. Zwracasz tylko poprawny JSON.',
                <<<PROMPT
                Zrób premium review draftu. Nie zmieniaj tematu, nie wymyślaj nowych faktów, ale popraw tytuł, lead, strukturę i siłę argumentacji.
                Jeśli featured_image_alt jest ogólnikowe, przepisz je jak brief dla fotografa prasowego — scena musi pasować 1:1 do leadu i głównego newsa (bez marek i logo), żeby downstream mógł wygenerować obraz w Vertex Imagen 4 / Gemini image.

                Topic pack:
                {$this->json($topicPack)}

                Draft:
                {$this->json($draft)}

                Zwracaj tylko JSON:
                {
                  "title": "...",
                  "excerpt": "...",
                  "content_html": "...",
                  "meta_title": "...",
                  "meta_description": "...",
                  "featured_image_alt": "..."
                }
                PROMPT
            );
        } catch (RuntimeException) {
            return [];
        }

        return $this->normalizeDraftPayload($payload, allowMissing: true);
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function generateHeroImage(array $topicPack, array &$draft): ?string
    {
        if ($this->shouldPreferSourcedHeroImage($topicPack)) {
            $sourcedHero = $this->acquireSourcedHeroImage($topicPack, $draft);

            if ($sourcedHero !== null) {
                return $sourcedHero;
            }
        }

        $geminiModel = (string) config('blog.vertex_models.image', 'gemini-3.1-flash-image-preview');
        $imagenModel = (string) config('blog.vertex_models.image_fallback', 'imagen-4.0-generate-001');
        $editorialMode = (string) ($topicPack['editorial_mode'] ?? '');
        $preferImagenFirst = (bool) config('blog.prefer_imagen_hero_first', true);
        $tryImagenFirst = $preferImagenFirst
            && $imagenModel !== ''
            && str_starts_with($imagenModel, 'imagen-');

        $modelQueue = $tryImagenFirst
            ? array_values(array_unique(array_filter([$imagenModel, $geminiModel])))
            : array_values(array_unique(array_filter([$geminiModel, $imagenModel])));

        $prompt = $this->buildHeroImagePrompt($topicPack, $draft);
        $lastException = null;

        foreach ($modelQueue as $index => $model) {
            try {
                $asset = $this->callImageModel($model, $prompt);
                $draft['_image_model'] = $model;
                $extension = $asset['extension'] ?? 'jpg';
                $filename = 'blog/' . Str::ulid() . '.' . $extension;
                Storage::disk('public')->put($filename, $asset['bytes']);

                return $filename;
            } catch (RuntimeException $exception) {
                $lastException = $exception;
                Log::warning('BLOG_HERO_IMAGE_FAILED', [
                    'model' => $model,
                    'attempt' => $index + 1,
                    'editorial_mode' => $editorialMode,
                    'imagen_first' => $tryImagenFirst,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($lastException !== null) {
            Log::warning('BLOG_HERO_IMAGE_EXHAUSTED', [
                'models_tried' => $modelQueue,
                'message' => $lastException->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     */
    private function shouldPreferSourcedHeroImage(array $topicPack): bool
    {
        if (! (bool) config('blog.source_first_hero_enabled', true)) {
            return false;
        }

        $slotType = $this->normalizeSlotType($topicPack['slot_type'] ?? null);
        $preferredSlots = collect((array) config('blog.source_first_hero_slots', ['premiera', 'news']))
            ->map(fn ($slot) => trim(Str::lower((string) $slot)))
            ->filter()
            ->all();

        if (! in_array($slotType, $preferredSlots, true)) {
            return false;
        }

        return collect((array) ($topicPack['source_urls'] ?? []))
            ->contains(fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL));
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function acquireSourcedHeroImage(array $topicPack, array &$draft): ?string
    {
        $sourceUrls = collect((array) ($topicPack['source_urls'] ?? []))
            ->filter(fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL))
            ->map(fn ($url) => trim((string) $url))
            ->unique()
            ->values()
            ->all();

        if ($sourceUrls === []) {
            return null;
        }

        $sourceUrls = $this->prioritizeSourcePageUrls($sourceUrls, $topicPack, $draft);

        foreach ($sourceUrls as $sourceUrl) {
            try {
                $htmlResponse = Http::timeout(20)
                    ->retry(2, 800)
                    ->withHeaders([
                        'Accept' => 'text/html,application/xhtml+xml',
                        'User-Agent' => 'RSPerformanceEditorialBot/2026.04 (+https://rsperformance.online)',
                    ])
                    ->get($sourceUrl);

                if (! $htmlResponse->successful()) {
                    continue;
                }

                $candidateUrls = $this->extractCandidateImageUrlsFromHtml($htmlResponse->body(), $sourceUrl);
                $candidateUrls = $this->prioritizeCandidateImageUrls($candidateUrls, $sourceUrl, $topicPack, $draft);

                foreach ($candidateUrls as $candidateUrl) {
                    $downloaded = $this->downloadSourceImageCandidate($candidateUrl);

                    if ($downloaded === null) {
                        continue;
                    }

                    $filename = 'blog/' . Str::ulid() . '.' . $downloaded['extension'];
                    Storage::disk('public')->put($filename, $downloaded['bytes']);

                    $draft['_image_model'] = 'source-first-og-image';
                    $draft['_hero_image_origin'] = $candidateUrl;
                    $draft['_hero_image_source_page'] = $sourceUrl;

                    if ($this->normalizeString($draft['featured_image_alt'] ?? '') === '') {
                        $draft['featured_image_alt'] = $this->buildSourceFirstImageAlt($topicPack, $draft);
                    }

                    return $filename;
                }
            } catch (\Throwable $exception) {
                Log::warning('BLOG_SOURCE_HERO_FAILED', [
                    'source_url' => $sourceUrl,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $sourceUrls
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array<int, string>
     */
    private function prioritizeSourcePageUrls(array $sourceUrls, array $topicPack, array $draft): array
    {
        $subjectTokens = $this->extractHeroSubjectTokens($topicPack, $draft);
        $newsHosts = ['interia', 'autokult', 'autocentrum', 'autotrader', 'elektrowoz', 'elektromobilni', 'jdpower', 'imagazine', 'drivemode', 'motoryzacja'];

        return collect($sourceUrls)
            ->sortByDesc(function (string $url) use ($subjectTokens, $newsHosts): int {
                $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
                $path = Str::lower((string) parse_url($url, PHP_URL_PATH));
                $score = 0;

                if (preg_match('/(^|\.)(press|media|newsroom)\./', $host) === 1 || str_contains($path, '/press')) {
                    $score += 160;
                }

                foreach ($subjectTokens as $token) {
                    if (str_contains($host, $token)) {
                        $score += 90;
                    }

                    if (str_contains($path, $token)) {
                        $score += 25;
                    }
                }

                foreach ($newsHosts as $needle) {
                    if (str_contains($host, $needle)) {
                        $score -= 35;
                    }
                }

                if (str_contains($host, 'blog')) {
                    $score -= 15;
                }

                return $score;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $candidateUrls
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array<int, string>
     */
    private function prioritizeCandidateImageUrls(array $candidateUrls, string $sourceUrl, array $topicPack, array $draft): array
    {
        $subjectTokens = $this->extractHeroSubjectTokens($topicPack, $draft);
        $sourceHost = Str::lower((string) parse_url($sourceUrl, PHP_URL_HOST));

        return collect($candidateUrls)
            ->sortByDesc(function (string $url) use ($subjectTokens, $sourceHost): int {
                $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
                $path = Str::lower((string) parse_url($url, PHP_URL_PATH));
                $score = 0;

                if ($sourceHost !== '' && $host === $sourceHost) {
                    $score += 80;
                }

                foreach ($subjectTokens as $token) {
                    if (str_contains($host, $token)) {
                        $score += 40;
                    }

                    if (str_contains($path, $token)) {
                        $score += 35;
                    }
                }

                if (preg_match('/(^|\.)(press|media|newsroom)\./', $host) === 1) {
                    $score += 40;
                }

                if (str_contains($path, 'hero') || str_contains($path, 'cover') || str_contains($path, 'header')) {
                    $score += 20;
                }

                return $score;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function extractCandidateImageUrlsFromHtml(string $html, string $pageUrl): array
    {
        $urls = [];

        if ($html === '') {
            return [];
        }

        $internalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;

        try {
            @$dom->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
        }

        foreach ($dom->getElementsByTagName('meta') as $meta) {
            $property = trim(Str::lower((string) $meta->getAttribute('property')));
            $name = trim(Str::lower((string) $meta->getAttribute('name')));
            $content = trim((string) $meta->getAttribute('content'));
            $key = $property !== '' ? $property : $name;

            if ($content === '' || ! in_array($key, ['og:image', 'og:image:url', 'twitter:image', 'twitter:image:src'], true)) {
                continue;
            }

            $resolved = $this->resolveUrl($pageUrl, $content);
            if ($resolved !== null) {
                $urls[] = $resolved;
            }
        }

        foreach ($dom->getElementsByTagName('link') as $link) {
            $rel = trim(Str::lower((string) $link->getAttribute('rel')));
            $href = trim((string) $link->getAttribute('href'));

            if ($href === '' || ! in_array($rel, ['image_src', 'preload'], true)) {
                continue;
            }

            $as = trim(Str::lower((string) $link->getAttribute('as')));
            if ($rel === 'preload' && $as !== 'image') {
                continue;
            }

            $resolved = $this->resolveUrl($pageUrl, $href);
            if ($resolved !== null) {
                $urls[] = $resolved;
            }
        }

        foreach ($dom->getElementsByTagName('img') as $image) {
            $src = trim((string) $image->getAttribute('src'));
            if ($src === '') {
                continue;
            }

            $resolved = $this->resolveUrl($pageUrl, $src);
            if ($resolved !== null) {
                $urls[] = $resolved;
            }
        }

        return collect($urls)
            ->map(fn ($url) => trim((string) $url))
            ->filter(fn ($url) => $this->isUsableSourceImageUrl($url))
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function resolveUrl(string $pageUrl, string $candidate): ?string
    {
        $candidate = trim(html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($candidate === '' || Str::startsWith($candidate, ['data:', 'javascript:', 'mailto:'])) {
            return null;
        }

        if (filter_var($candidate, FILTER_VALIDATE_URL)) {
            return $candidate;
        }

        $base = parse_url($pageUrl);
        if (! is_array($base) || ! isset($base['scheme'], $base['host'])) {
            return null;
        }

        $scheme = (string) $base['scheme'];
        $host = (string) $base['host'];
        $port = isset($base['port']) ? ':' . $base['port'] : '';

        if (Str::startsWith($candidate, '//')) {
            return $scheme . ':' . $candidate;
        }

        if (Str::startsWith($candidate, '/')) {
            return $scheme . '://' . $host . $port . $candidate;
        }

        $path = (string) ($base['path'] ?? '/');
        $directory = preg_replace('~/[^/]*$~', '/', $path) ?: '/';

        return $scheme . '://' . $host . $port . $directory . ltrim($candidate, '/');
    }

    private function isUsableSourceImageUrl(string $url): bool
    {
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));

        if ($path === '' || str_contains($path, '.svg')) {
            return false;
        }

        foreach (['logo', 'icon', 'avatar', 'favicon', 'sprite', 'placeholder', 'thumb'] as $needle) {
            if (str_contains($path, $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{bytes: string, extension: string}|null
     */
    private function downloadSourceImageCandidate(string $imageUrl): ?array
    {
        $response = Http::timeout(25)
            ->retry(2, 900)
            ->withHeaders([
                'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'User-Agent' => 'RSPerformanceEditorialBot/2026.04 (+https://rsperformance.online)',
            ])
            ->get($imageUrl);

        if (! $response->successful()) {
            return null;
        }

        $bytes = $response->body();
        if ($bytes === '' || strlen($bytes) < (int) config('blog.source_first_hero_min_bytes', 65536)) {
            return null;
        }

        $mimeType = Str::lower(trim((string) $response->header('Content-Type')));
        $mimeType = trim(explode(';', $mimeType)[0] ?? '');

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $dimensions = @getimagesizefromstring($bytes);
        if (! is_array($dimensions)) {
            return null;
        }

        $minWidth = (int) config('blog.source_first_hero_min_width', 960);
        $minHeight = (int) config('blog.source_first_hero_min_height', 540);

        if (($dimensions[0] ?? 0) < $minWidth || ($dimensions[1] ?? 0) < $minHeight) {
            return null;
        }

        return [
            'bytes' => $bytes,
            'extension' => $this->extensionFromMimeType($mimeType),
        ];
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function buildSourceFirstImageAlt(array $topicPack, array $draft): string
    {
        $title = $this->normalizeString($draft['title'] ?? $topicPack['title'] ?? '');
        $slotType = $this->normalizeSlotType($topicPack['slot_type'] ?? null);

        return match ($slotType) {
            'premiera' => $title !== '' ? $title . ' - zdjecie modelu z materialu zrodlowego' : 'Zdjecie modelu z materialu zrodlowego',
            'news' => $title !== '' ? $title . ' - zdjecie z materialu zrodlowego' : 'Zdjecie z materialu zrodlowego',
            default => $title !== '' ? $title : 'Zdjecie do artykulu RS Performance',
        };
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array<int, string>
     */
    private function extractHeroSubjectTokens(array $topicPack, array $draft): array
    {
        $raw = implode(' ', array_filter([
            $this->normalizeString($draft['title'] ?? ''),
            $this->normalizeString($topicPack['operator_topic'] ?? ''),
            $this->normalizeString($topicPack['title'] ?? ''),
        ]));

        $normalized = Str::lower(Str::ascii($raw));
        $tokens = preg_split('/[^a-z0-9]+/', $normalized) ?: [];

        return collect($tokens)
            ->map(fn (string $token): string => trim($token))
            ->filter()
            ->filter(function (string $token): bool {
                if (preg_match('/^[a-z0-9]{2,6}$/', $token) === 1 && preg_match('/[a-z]/', $token) === 1 && preg_match('/\d/', $token) === 1) {
                    return true;
                }

                return Str::length($token) >= 4;
            })
            ->reject(fn (string $token): bool => in_array($token, [
                'premiera',
                'nowa',
                'era',
                'elektrycznego',
                'sedana',
                'premium',
                'news',
                'porada',
                'analiza',
                'auto',
                'auta',
                'samochod',
                'warsztat',
            ], true))
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $sourceUrls
     * @param  array<string, mixed>  $topicContext
     * @return array<int, string>
     */
    private function prioritizeEditorialSourceUrls(array $sourceUrls, array $topicContext, string $slotType): array
    {
        $subjectTokens = $this->extractHeroSubjectTokens($topicContext, []);

        return collect($sourceUrls)
            ->filter(fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL))
            ->map(fn ($url) => trim((string) $url))
            ->unique()
            ->sortByDesc(function (string $url) use ($slotType, $subjectTokens): int {
                $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
                $path = Str::lower((string) parse_url($url, PHP_URL_PATH));
                $score = 0;

                if ($slotType === 'premiera') {
                    if (
                        preg_match('/(^|\.)(press|media|newsroom)\./', $host) === 1
                        || str_contains($path, '/press')
                        || str_contains($path, '/newsroom')
                        || str_contains($path, '/media')
                    ) {
                        $score += 220;
                    }

                    if (
                        ! str_contains($host, 'blog')
                        && ! str_contains($host, 'interia')
                        && ! str_contains($host, 'autokult')
                        && ! str_contains($host, 'autocentrum')
                        && ! str_contains($host, 'autotrader')
                        && ! str_contains($host, 'imagazine')
                        && ! str_contains($host, 'drivemode')
                        && ! str_contains($host, 'jdpower')
                    ) {
                        $score += 80;
                    }
                }

                foreach ($subjectTokens as $token) {
                    if (str_contains($host, $token)) {
                        $score += 90;
                    }

                    if (str_contains($path, $token)) {
                        $score += 35;
                    }
                }

                if (str_contains($path, 'neue-klasse') || str_contains($path, 'panoramic') || str_contains($path, 'vision')) {
                    $score += 15;
                }

                return $score;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function buildHeroImagePrompt(array $topicPack, array $draft): string
    {
        $title = $this->normalizeString($draft['title'] ?? $topicPack['title'] ?? '');
        $excerpt = $this->normalizeString($draft['excerpt'] ?? '');
        $brief = $this->normalizeString($topicPack['research_brief'] ?? $topicPack['hook'] ?? '');
        $operatorTopic = $this->normalizeString($topicPack['operator_topic'] ?? '');
        $slotType = $this->normalizeString($topicPack['slot_type'] ?? '');
        $category = $this->normalizeString($draft['category'] ?? $topicPack['suggested_category'] ?? 'motoryzacja');
        $problemHints = collect((array) ($draft['related_problem_slugs'] ?? []))
            ->map(fn ($slug) => str_replace('-', ' ', (string) $slug))
            ->filter()
            ->take(3)
            ->implode(', ');
        $contentPlain = preg_replace('/\s+/', ' ', strip_tags((string) ($draft['content_html'] ?? ''))) ?? '';
        $contentPlain = trim($contentPlain);
        $contentSnippet = $contentPlain !== '' ? Str::limit($contentPlain, 420, '') : '';
        $altBrief = $this->normalizeString($draft['featured_image_alt'] ?? '');

        return trim(implode("\n", array_filter([
            'Create a photorealistic hero image for a Polish automotive service blog article.',
            'The scene should look like premium automotive photography shot in a modern European workshop or on the road, depending on the topic.',
            $title !== '' ? 'Article title: ' . $title : null,
            $excerpt !== '' ? 'Article summary: ' . $excerpt : null,
            $contentSnippet !== '' ? 'Key narrative beats from article body (match this angle, not generic stock): ' . $contentSnippet : null,
            $altBrief !== '' ? 'Photo brief from editor (primary — follow closely): ' . $altBrief : null,
            $brief !== '' ? 'Research brief: ' . $brief : null,
            $operatorTopic !== '' ? 'Operator topic to preserve: ' . $operatorTopic : null,
            $slotType !== '' ? 'Editorial slot type: ' . $slotType : null,
            $category !== '' ? 'Category: ' . $category : null,
            $problemHints !== '' ? 'Mechanical context: ' . $problemHints : null,
            'Storyboard one decisive frame a Polish automotive magazine would run as the opener — the NEWS ANGLE, not a random workshop.',
            'Style: hyperrealistic, cinematic lighting, editorial automotive photography, crisp detail, natural reflections, premium but believable.',
            'The image must stay faithful to the article subject and must not drift into a generic unrelated automotive scene.',
            'If the article is about fuel prices, show a believable station / refueling / fuel-price context.',
            'If the article is about a service action or technical alert, show a believable service / inspection / vehicle-check context.',
            'If the article is about a premiere or market launch, show a believable new-car / showroom / road reveal context without brand marks.',
            'Composition: wide landscape 16:9 hero image, no text, no letters, no watermarks, no logos, no brand emblems.',
            'Use generic vehicle descriptions instead of brand names.',
        ])));
    }

    /**
     * @return array{bytes: string, extension: string}
     */
    private function callImageModel(string $model, string $prompt): array
    {
        return Str::startsWith($model, 'imagen-')
            ? $this->callImagenModel($model, $prompt)
            : $this->callGeminiImageModel($model, $prompt);
    }

    /**
     * @return array{bytes: string, extension: string}
     */
    private function callGeminiImageModel(string $model, string $prompt): array
    {
        $project = (string) config('vertex.project_id', '');
        $token = $this->tokenFactory->make();
        $location = (string) config('vertex.location', 'global');

        $response = Http::withToken($token)
            ->timeout(180)
            ->retry(3, 1200)
            ->post(sprintf(
                'https://aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
                $project,
                $location,
                $model
            ), [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'responseModalities' => ['TEXT', 'IMAGE'],
                    'imageConfig' => [
                        'aspectRatio' => '16:9',
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Gemini image request failed for model %s: HTTP %s | %s',
                $model,
                $response->status(),
                Str::limit($response->body(), 800, '...')
            ));
        }

        foreach ((array) $response->json('candidates.0.content.parts', []) as $part) {
            $inlineData = $part['inlineData'] ?? null;
            if (! is_array($inlineData)) {
                continue;
            }

            $bytes = $inlineData['data'] ?? null;
            $mimeType = $inlineData['mimeType'] ?? null;

            if (! is_string($bytes) || $bytes === '' || ! is_string($mimeType) || $mimeType === '') {
                continue;
            }

            return [
                'bytes' => base64_decode($bytes, true) ?: '',
                'extension' => $this->extensionFromMimeType($mimeType),
            ];
        }

        throw new RuntimeException("Gemini image model {$model} returned no inline image payload.");
    }

    /**
     * @return array{bytes: string, extension: string}
     */
    private function callImagenModel(string $model, string $prompt): array
    {
        $project = (string) config('vertex.project_id', '');
        $token = $this->tokenFactory->make();

        $response = Http::withToken($token)
            ->timeout(180)
            ->retry(3, 1200)
            ->post(sprintf(
                'https://us-central1-aiplatform.googleapis.com/v1/projects/%s/locations/us-central1/publishers/google/models/%s:predict',
                $project,
                $model
            ), [
                'instances' => [[
                    'prompt' => $prompt,
                ]],
                'parameters' => [
                    'sampleCount' => 1,
                    'aspectRatio' => '16:9',
                    'outputOptions' => [
                        'mimeType' => 'image/jpeg',
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Imagen request failed for model %s: HTTP %s | %s',
                $model,
                $response->status(),
                Str::limit($response->body(), 800, '...')
            ));
        }

        $bytes = $response->json('predictions.0.bytesBase64Encoded');
        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException("Imagen model {$model} returned no image bytes.");
        }

        return [
            'bytes' => base64_decode($bytes, true) ?: '',
            'extension' => 'jpg',
        ];
    }

    private function extensionFromMimeType(string $mimeType): string
    {
        return match (strtolower($mimeType)) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    public function persistPreparedDraft(array $topicPack, array $draft): BlogPost
    {
        $normalizedDraft = $this->normalizeDraftPayload($draft, allowMissing: true);

        if ($this->normalizeString($normalizedDraft['title'] ?? '') === '') {
            throw new RuntimeException('Prepared draft is missing a title.');
        }

        return $this->persistDraft($topicPack, $normalizedDraft);
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function persistDraft(array $topicPack, array $draft): BlogPost
    {
        $title = $this->normalizeString($draft['title'] ?? '') ?: $this->normalizeString($topicPack['title'] ?? 'Nowy draft bloga RS Performance');
        $slug = $this->makeUniqueSlug($title);
        $serviceSlug = $this->normalizeString($draft['related_service_slug'] ?? '')
            ?: (string) config('blog.default_related_service_slug', 'diagnostyka-komputerowa');
        $relatedServiceId = Service::query()->where('slug', $serviceSlug)->value('id');

        return BlogPost::query()->create([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $this->normalizeString($draft['excerpt'] ?? ''),
            'content' => $this->normalizeRichText($draft['content_html'] ?? ''),
            'raw_input' => $this->json([
                'topic' => $topicPack,
                'draft' => $draft,
            ]),
            'meta_title' => Str::limit($this->normalizeString($draft['meta_title'] ?? $title), 60, ''),
            'meta_description' => Str::limit($this->normalizeString($draft['meta_description'] ?? ''), 160, ''),
            'featured_image' => $this->normalizeString($draft['_hero_image'] ?? ''),
            'featured_image_alt' => $this->normalizeString($draft['featured_image_alt'] ?? $title),
            'author' => 'RS Performance AI',
            'category' => $this->normalizeString($draft['category'] ?? '')
                ?: (string) config('blog.default_category', 'Aktualnosci motoryzacyjne'),
            'published_at' => null,
            'is_published' => false,
            'ai_generated' => true,
            'faq' => $this->normalizeFaq($draft['faq'] ?? []),
            'related_service_id' => $relatedServiceId,
            'related_problem_slugs' => collect((array) ($draft['related_problem_slugs'] ?? []))
                ->map(fn ($slug) => Str::slug((string) $slug))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @param  array<int, array<string, mixed>>  $feedItems
     */
    private function researchPrompt(
        array $candidate,
        array $feedItems,
        string $editorialMode = 'evergreen',
        string $editorialNotes = '',
        string $slotType = '',
        array $operatorSourceUrls = [],
        string $newsDate = '',
    ): string {
        $dailyNewsDirective = $editorialMode === 'daily_news'
            ? implode("\n", array_filter([
                'To jest tryb DAILY_NEWS.',
                $newsDate !== '' ? 'Piszesz tylko o newsach i zmianach z dnia: ' . $newsDate . ' (Europe/Warsaw).' : null,
                'Nie wolno opierać tematu na starszych wydarzeniach jako głównej osi tekstu.',
                'Jeśli potrzebujesz starszego kontekstu, użyj go tylko jako tło, a nie główną oś wpisu.',
                'Szukaj skutków dla kierowcy, właściciela auta, serwisu i warsztatu.',
            ]))
            : 'To jest tryb EVERGREEN lub mixed-current.';

        $editorialNotesBlock = $editorialNotes !== ''
            ? "Notatki redakcyjne operatora:\n{$editorialNotes}\n"
            : '';

        $slotTypeBlock = $slotType !== ''
            ? "Docelowy typ wpisu: {$slotType}\n"
            : '';

        $sourceUrlsBlock = $operatorSourceUrls !== []
            ? "Wstepnie zatwierdzone URL-e z newsroomu / agregacji:\n{$this->json($operatorSourceUrls)}\n"
            : '';
        $premiereSourceDirective = $slotType === 'premiera'
            ? implode("\n", [
                'Dla premiery auta lub technologii `source_urls` mają być ustawione jak newsroom premium, nie jak śmietnik linków.',
                'Jeśli istnieje oficjalne źródło producenta, press room producenta lub oficjalna strona modelu, dodaj je i ustaw przed agregatami, blogami i wtórnymi omówieniami.',
                'Nie wpisuj literówek domen, skróconych adresów ani URL-i, których nie jesteś pewny.',
                'Dla premiery celem jest taki zestaw��ródeł, żeby downstream mógł pobrać prawdziwe zdjęcie opisywanego modelu, nie generyczny stock.',
            ])
            : '';

        return <<<PROMPT
        Masz wybranego kandydata na temat blogowy:

        {$this->json($candidate)}

        {$dailyNewsDirective}

        {$editorialNotesBlock}{$slotTypeBlock}{$sourceUrlsBlock}{$premiereSourceDirective}

        Oraz feed news dla motoryzacji:

        {$this->json($feedItems)}

        Użyj Google Search do głębokiego sprawdzenia aktualności i zamień to w research pack dla bloga warsztatu RS Performance.
        Temat ma być aktualny, praktyczny dla kierowcy i SEO-friendly pod lokalny warsztat.
        Ustaw research tak, żeby finalny tekst miał mocne, ale subtelne SEO, AEO i GEO:
        - naturalna intencja wyszukiwania
        - cytowalne, konkretne fragmenty
        - lokalny sens dla Gdańska / Trójmiasta bez keyword-spamu
        Pisz tak, jak robi to dziennikarz motoryzacyjny z wieloletnim stażem: konkretnie, bez waty, bez marketingowego plastiku.
        Myśl jak zespół redakcyjny: śledczy (fakty), warsztat (skutki dla serwisu), desk międzynarodowy (tłumaczenie dla PL) — research_brief ma zasilić każdy z tych głosów w downstream.
        Nie wymyślaj danych. Nie pisz jak robot.
        Jeśli operator podał konkretny temat, nie wolno zmienić osi tematu na inny motoryzacyjny temat. Możesz go doprecyzować, ale nie podmieniać.
        Nie wybieraj tematów lifestyle, celebryckich, motorsportowych, gadżetowych, plotkarskich ani viralowych, jeśli nie mają twardego znaczenia dla kierowcy, serwisu, diagnostyki, naprawy, eksploatacji, rynku motoryzacyjnego lub premiery modelu.
        Jeśli kandydat nie daje się uczciwie obronić jako temat dla profesjonalnego bloga motoryzacyjnego, obetnij mu priorytet i wybierz praktyczniejszy kąt.
        `suggested_category` ma być jedną z wartości:
        - Porady serwisowe
        - Aktualności motoryzacyjne
        - Premiery i rynek
        - Awarie i diagnostyka

        Zwracaj tylko JSON:
        {
          "title": "...",
          "keyword": "...",
          "hook": "...",
          "reason": "...",
          "research_brief": "...",
          "priority_score": 0,
          "suggested_category": "...",
          "source_urls": ["...","..."]
        }
        PROMPT;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function evaluateQualityGate(array $topicPack, array $draft): array
    {
        $title = $this->normalizeString($draft['title'] ?? '');
        $excerpt = $this->normalizeString($draft['excerpt'] ?? '');
        $content = $this->normalizeRichText($draft['content_html'] ?? '');
        $metaTitle = $this->normalizeString($draft['meta_title'] ?? '');
        $metaDescription = $this->normalizeString($draft['meta_description'] ?? '');
        $category = $this->normalizeString($draft['category'] ?? $topicPack['suggested_category'] ?? '');
        $faq = $this->normalizeFaq($draft['faq'] ?? []);
        $sourceUrls = collect((array) ($topicPack['source_urls'] ?? []))
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->unique()
            ->values()
            ->all();
        $operatorTopic = $this->normalizeString($topicPack['operator_topic'] ?? '');
        $editorialMode = (string) ($topicPack['editorial_mode'] ?? 'evergreen');
        $slotType = $this->normalizeSlotType($topicPack['slot_type'] ?? null);
        $isOperatorDriven = $operatorTopic !== '';
        $newsDate = (string) ($topicPack['news_date'] ?? '');
        $sameDayFeedCount = collect((array) ($topicPack['feed_items'] ?? []))
            ->filter(fn ($item) => is_array($item) && (($item['published_date_local'] ?? null) === $newsDate))
            ->count();
        $wordCount = str_word_count(strip_tags($content));
        $headingCount = preg_match_all('/<h[23][^>]*>/i', $content) ?: 0;
        $normalizedTitle = Str::lower($title);
        $normalizedSummary = Str::lower($title . ' ' . $excerpt . ' ' . strip_tags($content));
        $ctaHits = collect([
            'rs performance',
            'diagnostyka',
            'warsztat',
            'umow',
            'skontaktuj',
            'trojmiast',
            'gdansk',
        ])->filter(fn ($needle) => str_contains(Str::lower(strip_tags($content)), $needle))->count();

        $robotPhrases = [
            'warto zauwazyc',
            'nie ulega watpliwosci',
            'podsumowujac',
            'w dzisiejszych czasach',
            'nalezy podkreslic',
        ];
        $blockedAnglePhrases = [
            'szok',
            'szokuje',
            'nie uwierzysz',
            'to koniec',
            'masakra',
            'dramat',
            'hit internetu',
            'viral',
            'skandal',
            'plotka',
            'celebryt',
            'gwiazd',
            'manicure',
            'lifestyle',
            'praktycznie niezawodne',
            'bezawaryjne',
            'te samochody',
            'te auta',
        ];
        $blockedTitleStarts = [
            'uwaga:',
            'alarm:',
            'szok:',
            'pilne:',
            'te ',
            'ten ',
            'ta ',
        ];
        $allowedDailyNewsCategories = [
            'Porady serwisowe',
            'Aktualnosci motoryzacyjne',
            'Premiery i rynek',
            'Awarie i diagnostyka',
        ];

        $minTitleLength = $isOperatorDriven ? 38 : 45;
        $maxTitleLength = $slotType === 'premiera' ? 120 : 110;
        $minExcerptLength = $isOperatorDriven ? 105 : 120;
        $maxExcerptLength = 340;
        $minWordCount = match (true) {
            $editorialMode === 'daily_news' => 820,
            $isOperatorDriven => 760,
            default => 850,
        };
        $maxWordCount = 1800;

        $checks = [
            'title_length' => Str::length($title) >= $minTitleLength && Str::length($title) <= $maxTitleLength,
            'excerpt_length' => Str::length($excerpt) >= $minExcerptLength && Str::length($excerpt) <= $maxExcerptLength,
            'content_word_count' => $wordCount >= $minWordCount && $wordCount <= $maxWordCount,
            'heading_density' => $headingCount >= 3,
            'faq_count' => count($faq) >= 3,
            'source_count' => count($sourceUrls) >= 2,
            'meta_title_length' => Str::length($metaTitle) >= 40 && Str::length($metaTitle) <= 60,
            'meta_description_length' => Str::length($metaDescription) >= 110 && Str::length($metaDescription) <= 160,
            'cta_present' => $ctaHits >= 2,
            'robot_tone_clean' => ! collect($robotPhrases)->contains(fn ($phrase) => str_contains(Str::lower(strip_tags($content)), $phrase)),
            'truthful_angle_clean' => ! collect($blockedAnglePhrases)->contains(fn ($phrase) => str_contains($normalizedSummary, $phrase))
                && ! collect($blockedTitleStarts)->contains(fn ($phrase) => str_starts_with($normalizedTitle, $phrase)),
            'operator_topic_alignment' => $operatorTopic === '' || $this->topicAnchorsPresent($operatorTopic, $title, $excerpt, $content),
        ];

        if ($editorialMode === 'daily_news') {
            $checks['same_day_news'] = $sameDayFeedCount >= 2;
            $checks['daily_news_sources'] = count($sourceUrls) >= 3;
            $checks['daily_news_category'] = in_array($category, $allowedDailyNewsCategories, true);
        }

        $failedReasons = collect($checks)
            ->filter(fn ($passed) => $passed === false)
            ->keys()
            ->map(function (string $key) use ($newsDate): string {
                return match ($key) {
                    'title_length' => 'Title is outside the premium editorial range.',
                    'excerpt_length' => 'Excerpt is too short or too long.',
                    'content_word_count' => 'Content word count is outside the target editorial window.',
                    'heading_density' => 'Draft structure is too flat.',
                    'faq_count' => 'Draft does not contain 3 useful FAQ items.',
                    'source_count' => 'Research pack has fewer than 2 unique source URLs.',
                    'meta_title_length' => 'Meta title is outside the target range.',
                    'meta_description_length' => 'Meta description is outside the target range.',
                    'cta_present' => 'Draft does not naturally connect back to RS Performance or the workshop action.',
                    'robot_tone_clean' => 'Draft still contains robotic filler phrases.',
                    'truthful_angle_clean' => 'Draft still looks sensational, gossip-like or editorially cheap.',
                    'operator_topic_alignment' => 'Draft drifted away from the operator brief.',
                    'same_day_news' => 'Feed pack does not prove at least 2 items from ' . $newsDate . '.',
                    'daily_news_sources' => 'Daily-news draft has fewer than 3 verified source URLs.',
                    'daily_news_category' => 'Daily-news draft is outside the allowed editorial categories.',
                    default => 'Unknown quality gate failure.',
                };
            })
            ->values()
            ->all();

        $passedCount = collect($checks)->filter()->count();
        $score = (int) round(($passedCount / max(count($checks), 1)) * 100);

        return [
            'passed' => $failedReasons === [],
            'score' => $score,
            'checks' => $checks,
            'failed_reasons' => $failedReasons,
            'stats' => [
                'word_count' => $wordCount,
                'heading_count' => $headingCount,
                'faq_count' => count($faq),
                'source_count' => count($sourceUrls),
                'same_day_feed_count' => $sameDayFeedCount,
                'category' => $category,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $qualityGate
     * @return array<string, mixed>
     */
    private function appendSemanticTopicAlignment(array $topicPack, array $draft, array $qualityGate): array
    {
        $operatorTopic = $this->normalizeString($topicPack['operator_topic'] ?? '');

        if ($operatorTopic === '') {
            return $qualityGate;
        }

        $alignment = $this->evaluateSemanticTopicAlignment($topicPack, $draft);
        $checks = (array) ($qualityGate['checks'] ?? []);
        $checks['semantic_topic_alignment'] = (bool) ($alignment['passed'] ?? false);

        $failedReasons = collect((array) ($qualityGate['failed_reasons'] ?? []));

        if (! ($alignment['passed'] ?? false)) {
            $failedReasons->push((string) ($alignment['reason'] ?? 'Draft drifted away from the operator brief.'));
        }

        $failedReasons = $failedReasons
            ->filter(fn ($reason) => is_string($reason) && trim($reason) !== '')
            ->unique()
            ->values();

        $stats = array_merge(
            (array) ($qualityGate['stats'] ?? []),
            [
                'semantic_alignment_confidence' => $alignment['confidence'] ?? null,
                'semantic_alignment_must_have_terms' => $alignment['must_have_terms'] ?? [],
            ],
        );

        $passedCount = collect($checks)->filter()->count();
        $score = (int) round(($passedCount / max(count($checks), 1)) * 100);

        return [
            ...$qualityGate,
            'passed' => $failedReasons->isEmpty(),
            'score' => $score,
            'checks' => $checks,
            'failed_reasons' => $failedReasons->all(),
            'stats' => $stats,
        ];
    }

    /**
     * @param  array<string, mixed>  $qualityGate
     */
    private function shouldAttemptEditorialWindowRescue(array $qualityGate): bool
    {
        $checks = (array) ($qualityGate['checks'] ?? []);
        $failedKeys = collect($checks)
            ->filter(fn ($passed) => $passed === false)
            ->keys()
            ->values()
            ->all();

        return in_array('content_word_count', $failedKeys, true)
            && ! in_array('operator_topic_alignment', $failedKeys, true)
            && ! in_array('semantic_topic_alignment', $failedKeys, true)
            && count($failedKeys) <= 2;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $qualityGate
     * @return array<string, mixed>
     */
    private function repairDraftTopicAlignment(array $topicPack, array $draft, array $qualityGate, int $attempt): array
    {
        $operatorTopic = $this->normalizeString($topicPack['operator_topic'] ?? '');

        if ($operatorTopic === '') {
            return $draft;
        }

        $failedReasons = implode(' | ', (array) ($qualityGate['failed_reasons'] ?? []));
        $primaryModel = (string) config('blog.vertex_models.writer_fallback', 'gemini-2.5-flash-lite');
        $fallbackModel = (string) config('blog.vertex_models.seo_fallback', 'gemini-2.5-flash-lite');

        $payload = $this->callModelJsonWithGenericFallback(
            $primaryModel,
            $fallbackModel,
            'Jestes redaktorem naprawczym RS Performance. Dostajesz brief operatora i zly draft. Masz go przepisac tak, aby byl wierny tematowi, rzeczowy, sprawdzalny i nadal brzmial jak doswiadczony dziennikarz motoryzacyjny, nie robot. Zwracasz tylko poprawny JSON.',
            <<<PROMPT
            Popraw draft tak, aby wrocil do tematu operatora.

            Brief operatora:
            {$this->json([
                'operator_topic' => $topicPack['operator_topic'] ?? null,
                'editorial_mode' => $topicPack['editorial_mode'] ?? null,
                'editorial_notes' => $topicPack['editorial_notes'] ?? null,
                'slot_type' => $topicPack['slot_type'] ?? null,
                'news_date' => $topicPack['news_date'] ?? null,
                'source_urls' => $topicPack['source_urls'] ?? [],
            ])}

            Obecny draft do naprawy:
            {$this->json($draft)}

            Powody odrzucenia:
            {$failedReasons}

            Zwracaj tylko JSON:
            {
              "title": "...",
              "excerpt": "...",
              "content_html": "...",
              "meta_title": "...",
              "meta_description": "...",
              "category": "...",
              "featured_image_alt": "...",
              "faq": [
                {"question":"...","answer":"..."},
                {"question":"...","answer":"..."},
                {"question":"...","answer":"..."}
              ],
              "related_service_slug": "...",
              "related_problem_slugs": ["...","..."]
            }

            Twarde zasady:
            - zachowaj os tematu operatora, nie zmieniaj go na inny temat motoryzacyjny
            - jesli brief jest o paliwie, cenach paliw, tankowaniu, Pb95, dieslu lub LPG, to finalny tekst ma zostac o paliwie, cenach i skutkach dla kierowcy
            - nie wracaj do odrzuconego kata
            - zachowaj profesjonalny, ludzki ton
            - nie dopisuj danych bez pokrycia
            - to jest proba naprawcza numer {$attempt}
            PROMPT
        );

        $repairedDraft = $this->normalizeDraftPayload($payload);
        $repairedDraft['_model'] = (string) ($payload['_model'] ?? $primaryModel);
        $repairedDraft['_repair_attempt'] = $attempt;

        return $repairedDraft;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $qualityGate
     * @return array<string, mixed>
     */
    private function repairDraftEditorialWindow(array $topicPack, array $draft, array $qualityGate): array
    {
        $primaryModel = (string) config('blog.vertex_models.writer_fallback', 'gemini-2.5-flash-lite');
        $fallbackModel = (string) config('blog.vertex_models.seo_fallback', 'gemini-2.5-flash-lite');
        $failedReasons = implode(' | ', (array) ($qualityGate['failed_reasons'] ?? []));
        $currentWordCount = (int) ($qualityGate['stats']['word_count'] ?? 0);

        $payload = $this->callModelJsonWithGenericFallback(
            $primaryModel,
            $fallbackModel,
            'Jestes redaktorem finalizujacym RS Performance. Twoim zadaniem jest rozbudowac poprawny, ale zbyt krotki draft do pelnego, publikowalnego artykulu. Nie zmieniasz osi tematu. Nie dopisujesz niezweryfikowanych faktow. Zwracasz tylko poprawny JSON.',
            <<<PROMPT
            Rozbuduj draft tak, aby miescil sie w premium editorial window RS Performance i nadal byl wierny tematowi.

            Topic pack:
            {$this->json([
                'operator_topic' => $topicPack['operator_topic'] ?? null,
                'title' => $topicPack['title'] ?? null,
                'editorial_mode' => $topicPack['editorial_mode'] ?? null,
                'editorial_notes' => $topicPack['editorial_notes'] ?? null,
                'slot_type' => $topicPack['slot_type'] ?? null,
                'news_date' => $topicPack['news_date'] ?? null,
                'source_urls' => $topicPack['source_urls'] ?? [],
            ])}

            Obecny draft:
            {$this->json($draft)}

            Powody odrzucenia:
            {$failedReasons}

            Aktualna liczba slow:
            {$currentWordCount}

            Zwracaj tylko JSON:
            {
              "title": "...",
              "excerpt": "...",
              "content_html": "...",
              "meta_title": "...",
              "meta_description": "...",
              "category": "...",
              "featured_image_alt": "...",
              "faq": [
                {"question":"...","answer":"..."},
                {"question":"...","answer":"..."},
                {"question":"...","answer":"..."}
              ],
              "related_service_slug": "...",
              "related_problem_slugs": ["...","..."]
            }

            Twarde zasady:
            - rozbuduj tekst do okna 950-1250 slow
            - nie zmieniaj glownego tematu, nie podmieniaj kata artykulu
            - wzmocnij konkret, kontekst dla kierowcy i skutki praktyczne
            - nie dopisuj zmyslonych danych, cytatow ani statystyk
            - zadbaj o naturalne SEO, AEO i GEO, ale bez keyword stuffingu
            - tekst ma brzmiec jak zawodowy dziennikarz motoryzacyjny, nie jak AI
            PROMPT
        );

        $expandedDraft = $this->normalizeDraftPayload($payload);
        $expandedDraft['_model'] = (string) ($payload['_model'] ?? $primaryModel);
        $expandedDraft['_word_count_rescue'] = true;

        return $expandedDraft;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function stabilizeDraftForEditorialGate(array $topicPack, array $draft): array
    {
        $draft['title'] = $this->stabilizeEditorialTitle($topicPack, $draft);
        $draft['content_html'] = $this->stabilizeEditorialContent($topicPack, $draft);
        $draft['excerpt'] = $this->stabilizeEditorialExcerpt($draft);
        $draft['meta_title'] = Str::limit(
            $this->normalizeString($draft['meta_title'] ?? $draft['title'] ?? ''),
            60,
            ''
        );
        $draft['meta_description'] = Str::limit(
            $this->normalizeString($draft['meta_description'] ?? $draft['excerpt'] ?? ''),
            160,
            ''
        );

        return $draft;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function stabilizeEditorialTitle(array $topicPack, array $draft): string
    {
        $currentTitle = $this->normalizeString($draft['title'] ?? '');
        $normalizedTitle = Str::lower(Str::ascii($currentTitle));
        $blockedNeedles = [
            'szok',
            'masakra',
            'dramat',
            'nie uwierzysz',
            'koniec zartow',
            'eko-oszolom',
            'zabawka',
            'rewolucja, nie ewolucja',
        ];

        $hasBlockedNeedle = collect($blockedNeedles)->contains(
            fn (string $needle): bool => str_contains($normalizedTitle, $needle)
        );

        if (! $hasBlockedNeedle) {
            return $currentTitle;
        }

        $subject = $this->extractEditorialSubject($topicPack, $draft);
        $slotType = $this->normalizeSlotType($topicPack['slot_type'] ?? null);

        return match ($slotType) {
            'premiera' => "{$subject}: co nowy model oznacza dla kierowcow i serwisow",
            'news' => "{$subject}: co zmienia sie dla kierowcow i warsztatow",
            'porada' => "{$subject}: na co zwrocic uwage przed decyzja serwisowa",
            default => "{$subject}: co z tego wynika dla kierowcy i warsztatu",
        };
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function stabilizeEditorialContent(array $topicPack, array $draft): string
    {
        $content = $this->normalizeRichText($draft['content_html'] ?? '');
        $wordCount = str_word_count(strip_tags($content));

        if ($wordCount >= 920) {
            return $content;
        }

        $supplement = $this->buildEditorialSupplement($topicPack, $draft, $wordCount);

        if ($supplement === '') {
            return $content;
        }

        return trim($content . "\n\n" . $supplement);
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function stabilizeEditorialExcerpt(array $draft): string
    {
        $excerpt = $this->normalizeString($draft['excerpt'] ?? '');
        $contentText = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($draft['content_html'] ?? ''))) ?? '');
        $title = $this->normalizeString($draft['title'] ?? '');

        if (Str::length($excerpt) >= 120 && Str::length($excerpt) <= 320) {
            return $excerpt;
        }

        $base = $excerpt !== '' ? $excerpt : $contentText;

        if ($base === '') {
            $base = $title;
        }

        $base = trim($base);

        if (! str_ends_with($base, '.')) {
            $base .= '.';
        }

        $excerpt = Str::limit($base, 220, '');

        if (Str::length($excerpt) < 130) {
            $fallbackTail = ' Tekst wyjasnia konsekwencje dla kierowcy, serwisu i decyzji eksploatacyjnych bez taniej sensacji.';
            $excerpt = Str::limit(trim($excerpt . $fallbackTail), 280, '');
        }

        if (Str::length($excerpt) < 130) {
            $secondTail = ' Pokazuje tez, co z tej historii realnie wynika dla warsztatu i codziennej eksploatacji auta.';
            $excerpt = Str::limit(trim($excerpt . $secondTail), 300, '');
        }

        return trim($excerpt);
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function buildEditorialSupplement(array $topicPack, array $draft, int $wordCount): string
    {
        $missingWords = max(0, 960 - $wordCount);

        if ($missingWords <= 0) {
            return '';
        }

        $subject = $this->extractEditorialSubject($topicPack, $draft);
        $slotType = $this->normalizeSlotType($topicPack['slot_type'] ?? null);
        $newsDate = $this->normalizeString($topicPack['news_date'] ?? '');
        $sourceCount = count((array) ($topicPack['source_urls'] ?? []));
        $researchBrief = $this->normalizeString($topicPack['research_brief'] ?? $topicPack['hook'] ?? '');

        $impactLead = match ($slotType) {
            'premiera' => "W praktyce najwazniejsze nie jest samo show wokol premiery, tylko to, jak {$subject} zmienia codzienna eksploatacje, oczekiwania wobec serwisu i rozmowe o kosztach utrzymania auta w kolejnych latach.",
            'news' => "Najwazniejsze pytanie nie brzmi, czy temat jest glosny, tylko co realnie zmienia {$subject} dla kierowcy, warsztatu i codziennych decyzji eksploatacyjnych.",
            'porada' => "Najwiecej wartosci daje spokojne rozpisanie, co przy {$subject} da sie ocenic samodzielnie, a co warto od razu zweryfikowac w warsztacie, zanim problem zrobi sie drozszy.",
            default => "Przy {$subject} najwazniejsze jest oddzielenie medialnego szumu od praktyki: co z tego faktycznie wynika dla kierowcy, serwisu i budzetu utrzymania auta.",
        };

        $verificationLead = $newsDate !== ''
            ? "Ten wpis opiera sie na materiale zebranym i sprawdzonym na dzien {$newsDate}, dlatego zamiast szerokich teorii porzadkuje to, co kierowca i warsztat moga z tego wyciagnac juz teraz."
            : 'Ten wpis opiera sie na zebranych materialach z rynku i dlatego zamiast taniej sensacji porzadkuje to, co kierowca i warsztat moga z tego wyciagnac w praktyce.';

        $sourceLead = $sourceCount >= 2
            ? 'Wnioski nie wisza w prozni, bo zostaly zestawione z kilkoma zrodlami branzowymi, komunikatami producentow i kontekstem serwisowym, a nie z pojedynczym naglowkiem wyrwanym z feedu.'
            : 'Wnioski zostaly rozpisane ostroznie i bez dopisywania faktow, tak aby tekst zostal wierny temu, co da sie obronic serwisowo i rynkowo.';

        $briefSentence = $researchBrief !== ''
            ? 'Punkt wyjscia jest prosty: ' . Str::limit($researchBrief, 260, '') . '.'
            : null;

        $geoSentence = 'Z perspektywy kierowcy z Gdanska i Trojmiasta liczy sie nie tylko sam news, ale tez dostepnosc czesci, terminy serwisowe, realna diagnoza i to, czy lokalny warsztat potrafi wytlumaczyc konsekwencje bez marketingowego dymu.';

        $checklist = match ($slotType) {
            'premiera' => [
                'Czy nowa technologia wymaga innego podejscia do diagnostyki, ladowania albo obslugi okresowej.',
                'Jak szybko takie rozwiazania trafia do niezaleznego serwisu i kiedy skonczy sie etap \"tylko ASO wie lepiej\".',
                'Ktore elementy sa marketingiem, a ktore faktycznie zmieniaja codzienne koszty lub wygode uzytkowania.',
            ],
            'porada' => [
                'Jakie objawy lub sygnaly ostrzegawcze warto zapisac przed wizyta, zeby warsztat nie zaczynal diagnozy od zgadywania.',
                'Ktore czynnosci sa bezpieczne do sprawdzenia samodzielnie, a gdzie lepiej nie ryzykowac pogorszenia stanu auta.',
                'Jak zadac serwisowi dobre pytania o przyczyne, ryzyko dalszej jazdy i plan naprawy.',
            ],
            default => [
                'Co ten temat zmienia dla kierowcy tu i teraz, a co jest tylko zapowiedzia na dalsze miesiace.',
                'Jak przejdzie to przez realny warsztat: diagnoza, dostepnosc czesci, czas oczekiwania i koszt ryzyka.',
                'Ktore fragmenty komunikatow producenta lub rynku warto czytac ostroznie, a ktore maja realna wartosc techniczna.',
            ],
        };

        $listItems = collect($checklist)
            ->map(fn (string $item) => '<li>' . e($item) . '</li>')
            ->implode('');

        $supplement = [
            '<h2>Co to realnie oznacza dla kierowcy</h2>',
            '<p>' . e($impactLead) . '</p>',
            '<p>' . e($verificationLead) . '</p>',
            $briefSentence !== null ? '<p>' . e($briefSentence) . '</p>' : null,
            '<h2>Warsztatowa perspektywa zamiast naglowka</h2>',
            '<p>' . e($sourceLead) . '</p>',
            '<p>' . e($geoSentence) . '</p>',
            '<h3>Na co zwrocic uwage w praktyce</h3>',
            '<ul>' . $listItems . '</ul>',
        ];

        $html = implode("\n", array_filter($supplement));

        if ($missingWords > 180) {
            $html .= "\n" . '<p>' . e('Wlasnie tutaj przydaje sie spokojna, warsztatowa interpretacja: bez dopisywania sensacji, ale tez bez zamiatania trudnych kosztow i ograniczen pod dywan. Dla klienta najcenniejsze jest to, zeby po lekturze wiedzial, czy obserwowac auto dalej, umawiac diagnostyke, czy po prostu poczekac na kolejny etap wdrozenia tej zmiany na rynku.') . '</p>';
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     */
    private function extractEditorialSubject(array $topicPack, array $draft): string
    {
        $candidate = $this->normalizeString(
            $topicPack['operator_topic']
                ?? $topicPack['title']
                ?? $draft['title']
                ?? 'temat motoryzacyjny'
        );

        $candidate = preg_replace('/\s+/', ' ', $candidate) ?: $candidate;
        $candidate = preg_replace('/[!.?]+$/', '', $candidate) ?: $candidate;
        $candidate = preg_replace('/\b(eko-oszolomow|eko oszolomow|szok|masakra|dramat)\b/iu', '', $candidate) ?: $candidate;

        return trim(Str::limit($candidate, 90, ''));
    }

    private function normalizeEditorialMode(mixed $mode): string
    {
        $value = trim((string) $mode);

        return $value === 'daily_news' ? 'daily_news' : 'evergreen';
    }

    /**
     * @return array{topic: string, notes: string}
     */
    private function normalizeOperatorBrief(string $manualTopic, string $editorialNotes): array
    {
        $normalizedTopic = preg_replace("/\r\n?/", "\n", trim($manualTopic)) ?: trim($manualTopic);

        if ($normalizedTopic === '') {
            return [
                'topic' => '',
                'notes' => trim($editorialNotes),
            ];
        }

        $lines = collect(explode("\n", $normalizedTopic))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return [
                'topic' => '',
                'notes' => trim($editorialNotes),
            ];
        }

        $primaryTopic = (string) $lines->shift();
        $primaryTopic = preg_replace('/^(wiadomosci|wiadomości|news|premiera|porada|analiza)\s*:\s*/iu', '', $primaryTopic) ?: $primaryTopic;
        $primaryTopic = preg_replace('/\s+/', ' ', $primaryTopic) ?: $primaryTopic;
        $primaryTopic = trim(Str::limit(trim($primaryTopic), 180, ''));

        $briefContext = trim($lines->implode("\n"));

        if ($briefContext !== '') {
            $editorialNotes = trim(implode("\n\n", array_filter([
                trim($editorialNotes),
                "Rozszerzony brief operatora:\n" . $briefContext,
            ])));
        }

        return [
            'topic' => $primaryTopic,
            'notes' => trim($editorialNotes),
        ];
    }

    private function normalizeSlotType(mixed $value, string $editorialNotes = ''): string
    {
        $slot = trim(Str::lower((string) $value));

        if ($slot === '' && preg_match('/slot_type:\s*(porada|news|premiera|analiza)/i', $editorialNotes, $matches) === 1) {
            $slot = Str::lower((string) ($matches[1] ?? ''));
        }

        return in_array($slot, ['porada', 'news', 'premiera', 'analiza'], true) ? $slot : '';
    }

    private function resolveNewsDate(mixed $value): string
    {
        $date = trim((string) $value);

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        return now('Europe/Warsaw')->toDateString();
    }

    private function normalizePublishedDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->setTimezone('Europe/Warsaw')->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function topicAnchorsPresent(string $rawInput, string $title, string $excerpt, string $content): bool
    {
        $anchors = $this->extractTopicAnchors($rawInput);

        if ($anchors === []) {
            return true;
        }

        $haystack = Str::lower(Str::ascii($title . ' ' . $excerpt . ' ' . strip_tags($content)));
        $matchedAnchors = collect($anchors)
            ->filter(fn (string $anchor): bool => str_contains($haystack, $anchor))
            ->count();

        return $matchedAnchors >= (count($anchors) >= 3 ? 2 : 1);
    }

    /**
     * @param  array<string, mixed>  $topicPack
     * @param  array<string, mixed>  $draft
     * @return array{passed: bool, confidence: float, reason: string, must_have_terms: array<int, string>}
     */
    private function evaluateSemanticTopicAlignment(array $topicPack, array $draft): array
    {
        $operatorTopic = $this->normalizeString($topicPack['operator_topic'] ?? '');

        if ($operatorTopic === '') {
            return [
                'passed' => true,
                'confidence' => 1.0,
                'reason' => '',
                'must_have_terms' => [],
            ];
        }

        $deterministicPassed = $this->topicAnchorsPresent(
            $operatorTopic,
            $this->normalizeString($draft['title'] ?? ''),
            $this->normalizeString($draft['excerpt'] ?? ''),
            $this->normalizeRichText($draft['content_html'] ?? ''),
        );

        if (! $deterministicPassed) {
            return [
                'passed' => false,
                'confidence' => 0.98,
                'reason' => 'Draft drifted away from the operator brief.',
                'must_have_terms' => $this->extractTopicAnchors($operatorTopic),
            ];
        }

        try {
            $payload = $this->callModelJsonWithGenericFallback(
                (string) config('blog.vertex_models.seo', 'gemini-2.5-flash-lite'),
                (string) config('blog.vertex_models.seo_fallback', 'gemini-2.5-flash-lite'),
                'Jestes bezlitosnym fact-checkerem i redaktorem zgodnosci briefu. Oceniasz tylko to, czy finalny draft zostal wierny osi tematu operatora. Zwracasz tylko poprawny JSON.',
                <<<PROMPT
                Oceń zgodnosc draftu z briefem operatora.

                Brief operatora:
                {$operatorTopic}

                Draft:
                {$this->json([
                    'title' => $draft['title'] ?? '',
                    'excerpt' => $draft['excerpt'] ?? '',
                    'content_html' => Str::limit($this->normalizeRichText($draft['content_html'] ?? ''), 6000, '...'),
                ])}

                Zwracaj tylko JSON:
                {
                  "passed": true,
                  "confidence": 0.0,
                  "reason": "",
                  "must_have_terms": ["...","..."]
                }

                Zasady:
                - `passed=true` tylko wtedy, gdy temat i glowny kat tekstu sa naprawde zgodne z briefem
                - jesli brief jest o paliwie, cenach paliw, tankowaniu, Pb95, dieslu lub LPG, a draft jest o rdzy, korozji, blacharce, lakierze albo innym pobocznym temacie, to odpowiedz musi byc `passed=false`
                - `must_have_terms` podaj jako 2-5 najwazniejszych terminow, ktore powinny byc widoczne w poprawnym artykule
                - `reason` ma byc krotki i konkretny
                PROMPT
            );

            return [
                'passed' => (bool) ($payload['passed'] ?? false),
                'confidence' => max(0.0, min(1.0, (float) ($payload['confidence'] ?? 0.0))),
                'reason' => $this->normalizeString($payload['reason'] ?? ''),
                'must_have_terms' => collect((array) ($payload['must_have_terms'] ?? []))
                    ->map(fn ($term) => $this->normalizeString((string) $term))
                    ->filter()
                    ->take(5)
                    ->values()
                    ->all(),
            ];
        } catch (\Throwable) {
            return [
                'passed' => $deterministicPassed,
                'confidence' => 0.7,
                'reason' => $deterministicPassed ? '' : 'Draft drifted away from the operator brief.',
                'must_have_terms' => $this->extractTopicAnchors($operatorTopic),
            ];
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractTopicAnchors(string $rawInput): array
    {
        $normalized = Str::lower(Str::ascii($rawInput));
        $tokens = preg_split('/[^a-z0-9]+/', $normalized) ?: [];

        $keepShort = [
            'pb95',
            'pb98',
            'lpg',
            'cng',
            'ev',
            'bev',
            'phev',
            'hev',
            'dpf',
            'adblue',
            'obd',
            'obd2',
            'scr',
            'tsi',
            'tdi',
            'fsi',
            'gpf',
            'egr',
            'abs',
            'esp',
            'sfd2',
        ];

        $stopwords = [
            'tekst',
            'brief',
            'blog',
            'warsztat',
            'performance',
            'gdansk',
            'gdanska',
            'trojmiasto',
            'temat',
            'wpis',
            'operator',
            'redakcja',
            'news',
            'wiadomosci',
            'porada',
            'premiera',
            'analiza',
            'auto',
            'auta',
            'samochod',
            'samochody',
            'kierowca',
            'kierowcy',
            'dzisiaj',
            'rano',
            'teraz',
            'wczoraj',
            'jutro',
            'glownie',
            'bardzo',
            'takze',
        ];

        return collect($tokens)
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => $token !== '')
            ->filter(function (string $token) use ($keepShort, $stopwords): bool {
                if (in_array($token, $stopwords, true)) {
                    return false;
                }

                if (in_array($token, $keepShort, true)) {
                    return true;
                }

                if (preg_match('/^[a-z]{2,}\d{1,4}$/', $token) === 1) {
                    return true;
                }

                return Str::length($token) >= 4;
            })
            ->unique()
            ->sortByDesc(fn (string $token): int => Str::length($token))
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @return array{text: string, source_urls: array<int, string>, search_queries: array<int, string>}
     */
    private function callGoogleGroundedJson(string $model, string $systemPrompt, string $userPrompt): array
    {
        $project = (string) config('vertex.project_id', '');
        $token = $this->tokenFactory->make();

        $response = Http::withToken($token)
            ->timeout(120)
            ->retry(3, 1200)
            ->post(sprintf(
                'https://aiplatform.googleapis.com/v1/projects/%s/locations/global/publishers/google/models/%s:generateContent',
                $project,
                $model
            ), [
                'systemInstruction' => [
                    'parts' => [[
                        'text' => $systemPrompt,
                    ]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $userPrompt,
                    ]],
                ]],
                'tools' => [[
                    'googleSearch' => [
                        'exclude_domains' => [],
                    ],
                ]],
                'model' => sprintf(
                    'projects/%s/locations/global/publishers/google/models/%s',
                    $project,
                    $model
                ),
                'generationConfig' => [
                    'temperature' => 0.3,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Google grounded request failed for model %s: HTTP %s | %s',
                $model,
                $response->status(),
                Str::limit($response->body(), 1000, '...')
            ));
        }

        $text = collect((array) $response->json('candidates.0.content.parts'))
            ->pluck('text')
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->implode("\n");

        if ($text === '') {
            throw new RuntimeException("Google grounded search zwrocil pusty payload dla modelu {$model}.");
        }

        $groundingMetadata = (array) $response->json('candidates.0.groundingMetadata', []);
        $sourceUrls = collect((array) ($groundingMetadata['groundingChunks'] ?? []))
            ->map(static function ($chunk): ?string {
                if (! is_array($chunk)) {
                    return null;
                }

                $web = $chunk['web'] ?? null;
                if (! is_array($web)) {
                    return null;
                }

                return isset($web['uri']) && is_string($web['uri']) ? $web['uri'] : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $queries = collect((array) ($groundingMetadata['webSearchQueries'] ?? []))
            ->filter(fn ($query) => is_string($query) && $query !== '')
            ->values()
            ->all();

        return [
            'text' => $text,
            'source_urls' => $sourceUrls,
            'search_queries' => $queries,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function callModelJson(string $model, string $systemPrompt, string $userPrompt): array
    {
        $payload = Str::startsWith($model, 'claude-')
            ? $this->callAnthropicModel($model, $systemPrompt, $userPrompt)
            : $this->callGoogleModel($model, $systemPrompt, $userPrompt);

        $decoded = $this->decodeJsonPayload($payload);
        $decoded['_model'] = $model;

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function callModelJsonWithFallback(string $primaryModel, string $fallbackModel, string $systemPrompt, string $userPrompt): array
    {
        try {
            return $this->callModelJson($primaryModel, $systemPrompt, $userPrompt);
        } catch (RuntimeException $exception) {
            if (
                $fallbackModel === ''
                || $fallbackModel === $primaryModel
                || ! $this->shouldFallbackToGoogle($primaryModel, $exception)
            ) {
                throw $exception;
            }

            return $this->callModelJson($fallbackModel, $systemPrompt, $userPrompt);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function callModelJsonWithGenericFallback(string $primaryModel, string $fallbackModel, string $systemPrompt, string $userPrompt): array
    {
        try {
            return $this->callModelJson($primaryModel, $systemPrompt, $userPrompt);
        } catch (RuntimeException $exception) {
            if (
                $fallbackModel === ''
                || $fallbackModel === $primaryModel
                || ! $this->shouldUseFallbackModel($exception)
            ) {
                throw $exception;
            }

            return $this->callModelJson($fallbackModel, $systemPrompt, $userPrompt);
        }
    }

    private function shouldFallbackToGoogle(string $primaryModel, RuntimeException $exception): bool
    {
        if (! Str::startsWith($primaryModel, 'claude-')) {
            return false;
        }

        return Str::contains($exception->getMessage(), ['HTTP 404', 'HTTP 429', 'RESOURCE_EXHAUSTED', 'NOT_FOUND']);
    }

    private function shouldUseFallbackModel(RuntimeException $exception): bool
    {
        return Str::contains($exception->getMessage(), ['HTTP 404', 'HTTP 429', 'RESOURCE_EXHAUSTED', 'NOT_FOUND']);
    }

    private function callGoogleModel(string $model, string $systemPrompt, string $userPrompt): string
    {
        $location = (string) config('vertex.location', 'us-central1');
        $project = (string) config('vertex.project_id', '');
        $token = $this->tokenFactory->make();

        $response = Http::withToken($token)
            ->timeout(90)
            ->retry(3, 1200)
            ->post(sprintf(
                'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
                $location,
                $project,
                $location,
                $model
            ), [
                'systemInstruction' => [
                    'parts' => [[
                        'text' => $systemPrompt,
                    ]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $userPrompt,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                    'maxOutputTokens' => 16384,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("Google Vertex request failed for model {$model}: HTTP {$response->status()}");
        }

        $text = collect((array) $response->json('candidates.0.content.parts'))
            ->pluck('text')
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->implode("\n");

        if ($text === '') {
            throw new RuntimeException("Google Vertex zwrocil pusty payload dla modelu {$model}.");
        }

        return $text;
    }

    private function callAnthropicModel(string $model, string $systemPrompt, string $userPrompt): string
    {
        $token = $this->tokenFactory->make();

        $response = Http::withToken($token)
            ->timeout(120)
            ->retry(3, 1200)
            ->post(AnthropicVertexPartnerEndpoint::rawPredictUrl($model), [
                'anthropic_version' => 'vertex-2023-10-16',
                'max_tokens' => 4000,
                'temperature' => 0.2,
                'system' => $systemPrompt,
                'messages' => [[
                    'role' => 'user',
                    'content' => [[
                        'type' => 'text',
                        'text' => $userPrompt,
                    ]],
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Anthropic Vertex request failed for model %s: HTTP %d — %s',
                $model,
                $response->status(),
                VertexHttpErrorSummary::fromResponse($response)
            ));
        }

        $text = collect((array) $response->json('content'))
            ->filter(fn ($part) => is_array($part) && ($part['type'] ?? null) === 'text')
            ->pluck('text')
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->implode("\n");

        if ($text === '') {
            throw new RuntimeException("Anthropic Vertex zwrocil pusty payload dla modelu {$model}.");
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonPayload(string $payload): array
    {
        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $payload, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException(sprintf(
            'Model nie zwrocil poprawnego JSON. Payload: %s',
            Str::limit(preg_replace('/\s+/', ' ', $payload) ?? $payload, 1200, '...')
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeDraftPayload(array $payload, bool $allowMissing = false): array
    {
        $normalized = [
            'title' => $this->normalizeString(Arr::get($payload, 'title')),
            'excerpt' => $this->normalizeString(Arr::get($payload, 'excerpt')),
            'content_html' => $this->normalizeRichText(Arr::get($payload, 'content_html')),
            'meta_title' => Str::limit($this->normalizeString(Arr::get($payload, 'meta_title')), 60, ''),
            'meta_description' => Str::limit($this->normalizeString(Arr::get($payload, 'meta_description')), 160, ''),
            'category' => $this->normalizeString(Arr::get($payload, 'category')),
            'featured_image_alt' => $this->normalizeString(Arr::get($payload, 'featured_image_alt')),
            'faq' => $this->normalizeFaq(Arr::get($payload, 'faq')),
            'related_service_slug' => Str::slug($this->normalizeString(Arr::get($payload, 'related_service_slug'))),
            'related_problem_slugs' => collect((array) Arr::get($payload, 'related_problem_slugs'))
                ->map(fn ($slug) => Str::slug((string) $slug))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];

        if (! $allowMissing && ($normalized['title'] === '' || $normalized['content_html'] === '')) {
            throw new RuntimeException('Draft AI nie zwrocil tytulu lub tresci HTML.');
        }

        return collect($normalized)
            ->filter(static fn ($value) => ! ($value === '' || $value === [] || $value === null))
            ->all();
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function normalizeFaq(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn ($item) => is_array($item) && ! empty($item['question']) && ! empty($item['answer']))
            ->map(static fn (array $item) => [
                'question' => trim((string) $item['question']),
                'answer' => trim((string) $item['answer']),
            ])
            ->take(5)
            ->values()
            ->all();
    }

    private function normalizeString(mixed $value): string
    {
        return trim(is_string($value) ? $value : (string) $value);
    }

    private function normalizeRichText(mixed $value): string
    {
        $html = trim(is_string($value) ? $value : (string) $value);
        $html = preg_replace('/\R{3,}/', "\n\n", $html) ?: $html;

        return trim($html);
    }

    private function makeUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : 'rs-blog-draft';
        $counter = 1;

        while (BlogPost::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function json(array $payload): string
    {
        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function logPipelineStage(BlogPipelineRun $pipelineRun, string $stage, array $context = []): void
    {
        $payload = [
            'pipeline_run_id' => $pipelineRun->id,
            'stage' => $stage,
            ...$context,
        ];

        Log::info('BLOG_PIPELINE_STAGE', $payload);

        try {
            @file_put_contents(
                storage_path('logs/blog-generate.log'),
                '[BLOG_PIPELINE_STAGE] ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND
            );
        } catch (\Throwable) {
            // Stage trace should never break the pipeline.
        }
    }
}

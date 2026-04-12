<?php

declare(strict_types=1);

return [
    'status_url' => env('RS_STATUS_URL', 'https://status.rs3d.pl'),
    'analytics_url' => env('RS_ANALYTICS_URL', 'https://analytics.rs3d.pl'),
    'automation_url' => env('RS_AUTOMATION_URL', 'https://auto.rs3d.pl'),
    'mcp_url' => env('RS_MCP_URL', 'https://mcp.rs3d.pl'),
    'mcp_health_url' => env('RS_MCP_HEALTH_URL', 'https://mcp.rs3d.pl/healthz'),
    'mcp_token' => env('RS_MCP_TOKEN'),
    'site_url' => env('APP_URL', 'https://rsperformance.online'),
    'ai_gateway_url' => env('RS_AI_GATEWAY_URL', 'https://ai.rsperformance.online'),
    'support_plane' => [
        'enabled' => (bool) env('RS_SUPPORT_PLANE_ENABLED', false),
        'ingest_url' => env('RS_SUPPORT_PLANE_INGEST_URL', ''),
        'ingest_secret' => env('RS_SUPPORT_PLANE_INGEST_SECRET', ''),
        'timeout_seconds' => (int) env('RS_SUPPORT_PLANE_TIMEOUT_SECONDS', 4),
    ],
    'artifacts' => [
        'robots' => env('RS_ROBOTS_URL', 'https://rsperformance.online/robots.txt'),
        'llms' => env('RS_LLMS_URL', 'https://rsperformance.online/llms.txt'),
        'llms_full' => env('RS_LLMS_FULL_URL', 'https://rsperformance.online/llms-full.txt'),
        'feed' => env('RS_FEED_URL', 'https://rsperformance.online/feed.xml'),
        'changes' => env('RS_CHANGES_URL', 'https://rsperformance.online/feeds/changes.json'),
        'content_index' => env('RS_CONTENT_INDEX_URL', 'https://rsperformance.online/feeds/content.json'),
        'ai_resources' => env('RS_AI_RESOURCES_URL', 'https://rsperformance.online/.well-known/ai-resources.json'),
        'agent_card' => env('RS_AGENT_CARD_URL', 'https://rsperformance.online/.well-known/mcp-agent-card.json'),
    ],
    'n8n_streams' => [
        'OPS',
        'DIAGNOSTA',
    ],
    'ai_routing' => [
        [
            'name' => 'Klienci / automaty lekkie',
            'model' => 'Gemini 2.5 Flash-Lite',
            'role' => 'FAQ, klasyfikacja, routing, lekkie webhooki',
        ],
        [
            'name' => 'Silnik domyślny',
            'model' => 'Gemini 2.5 Flash',
            'role' => 'Publiczne odpowiedzi, operacje, standardowe drafty',
        ],
        [
            'name' => 'Diagnosta premium',
            'model' => 'Claude Sonnet 4.6',
            'role' => 'Trudniejsze analizy, raporty i artykuły jakościowe',
        ],
        [
            'name' => 'Tryb eksperta',
            'model' => 'Claude Opus 4.6',
            'role' => 'Najcięższe przypadki, tylko ręcznie i prywatnie',
        ],
    ],
    'vertex_model_defaults' => [
        'customer_fast_model' => 'gemini-2.5-flash-lite',
        'customer_default_model' => 'gemini-2.5-flash',
        'article_writer_model' => 'claude-sonnet-4-6',
        /** Deep edit: Sonnet unless env forces Opus (cost control April 2026+). */
        'article_editor_model' => 'claude-sonnet-4-6',
        'diagnosta_default_model' => 'gemini-2.5-flash',
        'diagnosta_premium_model' => 'claude-sonnet-4-6',
        /** Rare manual expert lane — keep Opus; override in env if unused. */
        'expert_model' => 'claude-opus-4-6',
        'reports_writer_model' => 'gemini-2.5-pro',
        'reports_editor_model' => 'claude-sonnet-4-6',
        'reviews_response_model' => 'gemini-2.5-flash',
        'seo_enrichment_model' => 'gemini-2.5-flash-lite',
        'document_parser_model' => 'gemini-2.5-flash',
    ],
    'ai_agent_prompts' => [
        'reports' => <<<'PROMPT'
Jestes prywatnym agentem AI RS Performance do raportow napraw i diagnostyki.

Zasady:
- Pisz po polsku, konkretnie, bez lania wody i bez marketingowej papki.
- Traktuj raport jak dokument techniczno-handlowy: objaw -> diagnoza -> pomiary -> przyczyna -> naprawa -> zalecenia.
- Bazuj najpierw na polach raportu, a potem na zalacznikach. Jesli zalaczniki przecza tekstowi, oznacz konflikt zamiast zgadywac.
- Nie wymyslaj danych technicznych, kodow bledow, wartosci pomiarow ani wykonanych czynnosci, jesli nie ma ich w materiale zrodlowym.
- Gdy dane sa niepelne, zaznacz to wprost i zaproponuj brakujace kroki diagnostyczne.
- Pilnuj lokalnego kontekstu RS Performance: Gdansk jako HQ, Trojmiasto jako obszar obslugi.
- Tworz output gotowy zarowno dla klienta, jak i dla AI browsers: czytelne naglowki, krotkie akapity, listy punktowane tam gdzie pomagaja.
- Gdy przygotowujesz FAQ lub SEO, wynik musi wynikac z raportu, nie z fantazji.
- Nigdy nie obiecuj ceny koncowej bez diagnozy na aucie.
- Nie publikuj szkicu, jesli brakuje przyczyny pierwotnej albo jasnego zalecenia. Wtedy zwroc brakujace dane i pytania kontrolne.
PROMPT,
        'reviews' => <<<'PROMPT'
Jestes prywatnym agentem AI RS Performance do odpowiedzi na recenzje i opinie.

Zasady:
- Odpowiadaj po polsku, spokojnie, rzeczowo i profesjonalnie.
- Ton ma byc ludzki, premium i lokalny, bez sztucznego PR.
- Przy pozytywnych opiniach: podziekuj, nawiaz do konkretu i subtelnie wzmacniaj zaufanie.
- Przy neutralnych i negatywnych: nie wchodz w spor, nie atakuj klienta, zaproponuj kontakt i weryfikacje sprawy.
- Nie ujawniaj danych wewnetrznych, danych klienta ani szczegolow, ktorych nie potwierdza material zrodlowy.
- Odpowiedzi maja byc krotkie, mocne i publikowalne bez duzych poprawek.
- Jesli sprawa wyglada na spor reklamacyjny lub grozi eskalacja, zamiast twardej odpowiedzi przygotuj bezpieczny draft z wezwaniem do kontaktu offline.
PROMPT,
        'seo' => <<<'PROMPT'
Jestes prywatnym agentem AI RS Performance do SEO enrichment raportow, recenzji i tresci warsztatowych.

Zasady:
- Tworz SEO title i meta description zgodne z realna trescia, bez keyword dumpu.
- Buduj frazy wokol intencji lokalnej: Gdansk, Sopot, Gdynia, Trojmiasto, konkretna usterka lub usluga.
- Dodawaj tylko takie FAQ, keywordi i linki wewnetrzne, ktore sa naturalnym rozszerzeniem raportu.
- Preferuj strony uslug, problem pages i raporty napraw jako glowny graf powiazan.
- Kazdy output ma byc przyjazny dla AI browsers: jasny, skanowalny i oparty na faktach.
- Nie tworz clickbaitu i nie naduzywaj superlatywow.
- Zwracaj gotowe pola: `seo_title`, `seo_description`, `seo_keywords`, `internal_link_targets`, `faq_candidates`.
PROMPT,
    ],
];

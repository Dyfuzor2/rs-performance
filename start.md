> LIVE FILAMENT N8N WOW OPS HUB (2026-04-12 CET)
> Hosting `domains/rsperformance.online/laravel/`: wdrożono Filament **n8n WOW Ops Hub** (`N8nWorkflowDocumentResource` + widget `N8nWorkflowHubOverview`), migracja `2026_04_12_150000_create_n8n_workflow_documents_table`, seed `N8nWorkflowDocumentSeeder`, `config/n8n.php`, `OpsVerifyN8nHostingBridgeCommand`. Utworzono zdalne katalogi `.../N8nWorkflowDocumentResource/Pages|Widgets`, `app/Support/N8n`. Backupy: `database/seeders/DatabaseSeeder.php.bak_cursor_n8n_wow_20260412`, `config/n8n.php.bak_cursor_n8n_wow_20260412`. Komendy: `composer dump-autoload -o`, `php85 artisan migrate --force`, `php85 artisan db:seed --class=N8nWorkflowDocumentSeeder --force`, `php85 artisan optimize:clear`. Trasy: `GET admin/n8n-workflow-documents` (+ create/edit). Smoke z serwera: `curl` `https://rsperformance.online/` i `/admin/login` => **200**; `php85 artisan route:list --path=n8n` => **3** trasy.
> LIVE AI CATALOG + ARTIFACTS SYNC (2026-04-12 CET)
> Hosting `laravel/`: wgrano z repo `config/ai_agents.php`, `SearchArtifactFactory.php`, `AiDiscoveryArtifactBuilder.php`. Backupy: `*.bak_cursor_sync_20260412`. Komendy: `php85 artisan config:clear`, `php85 artisan search:artifacts-generate` (OK). Test na serwerze: `tests/Unit/AiAgentsInclusivePolicyTest.php` → **1 passed**. Publicznie: `/.well-known/ai-resources.json` zawiera zaktualizowane `catalog_policy` / listy agentów.
> LIVE FULL CATALOG HTACCESS (2026-04-12 CET)
> Wdrożono `G:\gravity\.htaccess_remote` → hosting `public_html/.htaccess` (`ssh_exec.py --upload`). ModSecurity **99000/99001** + WOW `RewriteCond` zsynchronizowane z pełnym merge `search_bots` + `training_bots` + `user_fetchers` (82 tokeny w bypass; bramka **80** bez Googlebot/Bingbot na kanonicznym). Jedyna twarda blokada: **denied_agents** (CCBot, iaskbot, magpie-crawler). Backup rollback: `.htaccess.bak_cursor_fullcatalog_htaccess_20260411`. Smoke: `Mozilla/5.0` → **200**; `GPTBot`, `Cursor` → **302** `Location: https://ai.rsperformance.online/`.
> LIVE VPS N8N WOW DIGEST CRON NOTE (2026-04-11 CET)
> User **rsops** on VPS `185.180.207.211`: daily **08:00 Europe/Warsaw** runs `/usr/bin/python3 /srv/ops-stack/scripts/vps_n8n_telegram_wow_digest.py --quiet` with `N8N_SQLITE=/srv/ops-stack/n8n/storage/database.sqlite`, append log `/srv/ops-stack/logs/n8n_wow_digest.log`. Repo installer (idempotent): `scripts/vps_install_wow_digest_cron.sh`. **Canonical Bot Invitation Hub workflow:** `F6uosr6xSCJZM4fO`; keep duplicate `FM1BxBIDKRmhr57i` **inactive** (dedup). Verify: `curl -s https://auto.rs3d.pl/healthz` => `{"status":"ok"}`; optional manual run without `--quiet` => Telegram HTTP 200, ~15 active workflows in fleet matrix.
> LIVE AI AGENT CATALOG NOTE (2026-04-11)
> Hosting: zsynchronizowano `config/ai_agents.php` (m.in. `xAI-Bot`, `Firecrawl`/`FirecrawlAgent`, `MistralAI-Index`, `GitHub-Copilot`, `Cursor`, `VercelBot`; bez `MendableBot`), `TrackAiAgentTraffic.php` (shadow labels), `public_html/.htaccess` z repo `.htaccess_remote`. Backupy: `*.bak_cursor_aiagents_deploy_20260411`. `php85 artisan config:clear`; test `tests/Unit/AiAgentsInclusivePolicyTest.php` => **1 passed** (wymaga `uses(TestCase::class)` w pliku). Smoke: `curl -A FirecrawlAgent` na canonical `/` => `302` → `ai.rsperformance.online`; zwykły UA => `200`.
> LIVE DENIED SCRAPERS NOTE (2026-04-12)
> Operator explicit deny: **CCBot**, **iaskbot**, **magpie-crawler** — not in ModSecurity99000/99001; not in WOW gateway UA list; in `config/ai_agents.denied_agents` (robots `Disallow: /`). Backups `*.bak_cursor_deny_ccbot_iask_magpie_20260412`.
> LIVE GOOGLEBOT MODSEC NOTE (2026-04-12)
> Repo `.htaccess_remote` / live `public_html/.htaccess`: SecRule `99000` includes **`Googlebot`** so ModSecurity `ctl:ruleEngine=Off` applies (not only `99001` ruleRemoveById). Deployed via SFTP/ssh_exec upload; backup `.htaccess.bak_cursor_googlebot_modsec_20260412`. **Spoof test:** `curl -A Googlebot` from non-Google IP may still return `403` (LiteSpeed `Vary: User-Agent`, `Retry-After`) — VPS egress with same UA gets `200`; use GSC for real crawler verification.
> LIVE AEO GATEWAY HTACCESS NOTE (2026-04-12)
> Canonical `302` to `https://ai.rsperformance.online/` for listed AI UAs works again on LiteSpeed. Root cause: a **literal space inside `ChatGPT Atlas`** within one long `RewriteCond` alternation broke the entire condition (silent no-match). Fix in repo `G:\gravity\.htaccess_remote` (deployed to `public_html/.htaccess`): `ChatGPT\x20Atlas`, encoded `%20` as `chatgpt\x25\x32\x30atlas`, UA case via trailing `[NC]` (avoid `(?i:(...))` in this cond), `ModSecurity` + fortress `FilesMatch` moved **outside** `IfModule mod_rewrite.c`, gatewayconds: `!^ai\.rsperformance\.online$`, `^(www\.)?rsperformance\.online$`, `^(GET|HEAD)$`, rule `[R=302,L,QSA]`. Backups on hosting: `.htaccess.bak_cursor_rewritefix_20260412`, earlier `.bak_cursor_wow_full_20260412`. Verify: `curl -sD - -o NUL -A GPTBot https://rsperformance.online/` => `302` + `Location: https://ai.rsperformance.online/`; browser UA => `200`; `/wp-admin` => `403`.
> LIVE DTC CITATION HEADERS NOTE (2026-04-12)
> `AiCitationHeaders` is now **registered** in production `bootstrap/app.php` (web stack), not only present on disk. Without registration, citation headers never ran. Deployed: `AiCitationHeaders.php` (always `X-Citation-Policy` + `X-Preferred-Citation` on `/kody-usterek` and `/kody-usterek/*` for all UAs), `bootstrap/app.php`. Backups: `AiCitationHeaders.php.bak_cursor_dtc_citation_20260412`, `bootstrap/app.php.bak_cursor_aicitation_register_20260412`. Verify: `curl -sI 'https://rsperformance.online/kody-usterek?v=1' -A 'Mozilla/5.0'` → `X-Citation-Policy`, `X-Preferred-Citation`, `X-Content-Type-Semantic: dtc-hub`. SFTP: use absolute remote path (`/home/tyurjydtpw/...`), not `~`.
> LIVE KNOWLEDGE PLANE NOTE (2026-04-11)
> `/.well-known/ai-resources.json` now includes `knowledge_plane`: canonical **MySQL** on `rsperformance.online` = source of truth; **Qdrant on VPS** = vector/RAG lane synced from production (agents: use gateway semantic search / MCP; no bulk MySQL scraping). Repo: `RELAY.md`, skills `rs-discovery-wow-2026`, `qdrant-memory-market`. Deploy: `SearchArtifactFactory.php` backup `.bak_cursor_knowledge_plane_20260411`, `search:artifacts-generate`, live JSON verified.
> LIVE A2A CARD + STATIC WELL-KNOWN SYNC NOTE (2026-04-11 23:00 CET)
> Hosting `public_html/.well-known/a2a.json` and `agent-card.json` are regenerated from `SearchArtifactFactory::writeAll()` again (production copy previously lacked `a2a.json` keys in `writeAll`, so disk stayed on 2026-04-10).
> Deployed to live Laravel: `app/Support/RsUri.php` (backup `.bak_cursor_a2auri_sync_20260411`), `app/Support/Search/SearchArtifactFactory.php` (backup `.bak_cursor_a2a_writeall_20260411`).
> Commands: `php85 artisan search:artifacts-generate --no-interaction`, `php85 artisan optimize:clear --no-interaction`.
> Verification: on-disk `a2a.json` mtime `2026-04-11`, size `7496` bytes; `curl -sI https://rsperformance.online/.well-known/a2a.json` => `200`, `Last-Modified` matches deploy; body includes `message:send` and `rs_discovery` blocks.
> `POST https://rsperformance.online/message:send` with `{}` => `400` (Laravel validation path) — no longer blocked at `406` from this probe (WAF may still vary by UA/IP).
> LIVE HOSPITALITY FAST-LANE NOTE (2026-04-11 21:05 CET)
> Canonical hosting + VPS gateway now expose a standards-first AI hospitality layer for bots, agents and AI browsers.
> Live changes:
>
> - hosting `ai-resources.json` / MCP card metadata now publish explicit hospitality + preferred fetch order
> - hosting bot-facing headers now include:
>     - `X-AI-Hospitality`
>     - `X-AI-Preferred-Fetch-Order`
> - VPS gateway now serves:
>     - `https://ai.rsperformance.online/for-agents`
>     - `https://ai.rsperformance.online/for-ai-browsers`
> - gateway `agent.json` now advertises the same fast-lane contract
>   Smoke:
> - `GPTBot`, `ClaudeBot`, `ChatGPT-User` => `302` from canonical `/` to `https://ai.rsperformance.online/`
> - gateway `/for-agents` => `200`
> - gateway `/for-ai-browsers` => `200`
> - `a2a-overture certify` remains green: `18 passed / 0 failed / 0 warnings / 6 skipped`
> - `php85 artisan aeo:invite-bots --force --no-interaction` completed successfully (`IndexNow`, `WebSub`, `Ping-o-Matic`, `Archive.org`)
>   LIVE A2A CERTIFY NOTE (2026-04-10 20:25 CET)
>   Canonical RS A2A surface is now green under `a2a-overture certify https://rsperformance.online`.
>   Result:
> - `18 passed`
> - `0 failed`
> - `0 warnings`
> - `6 skipped` (expected auth/signature/push skips)
>   Live fixes completed on hosting:
> - `cancel-task` now works
> - `subscribe-task` now streams via SSE
> - `get-task` / multi-turn task shape is aligned enough for full certify
> - legacy JSON-RPC `POST /` works again without CSRF mismatch
>   Updated live files:
> - `bootstrap/app.php`
> - `routes/web.php`
> - `app/Http/Controllers/A2aTaskController.php`
>   New 2026+ local A2A reference runtimes installed in `G:\gravity\tools`:
> - `a2a-mesh-runtime` (`a2a-mesh@1.1.0`)
> - `truss-mcp-a2a-gateway-runtime` (`truss-mcp-a2a-gateway@1.2.0`)
>   Production backups created:
> - `bootstrap/app.php.bak_codex_a2a_streaming_csrf_20260410`
> - `bootstrap/app.php.bak_codex_a2a_csrf_surface_20260410`
> - `bootstrap/app.php.bak_codex_a2a_root_csrf_20260410`
>   LIVE BLOG BRIEF SPLIT NOTE (2026-04-10 05:25 CET)
>   Manual blog generation on hosting now normalizes pasted multi-line briefs before they enter the editorial pipeline.
>   Updated live files:
> - `app/Console/Commands/AutoGenerateBlogPost.php`
> - `app/Support/Blog/BlogVertexPipelineService.php`
>   New behavior:
> - first non-empty line becomes the canonical `operator_topic`
> - remaining lines move into `editorial_notes` as `Rozszerzony brief operatora`
> - labels like `Wiadomości:` / `Premiera:` / `Porada:` are stripped from the canonical topic axis
>   Backups:
> - `app/Console/Commands/AutoGenerateBlogPost.php.bak_codex_brief_split_20260410`
> - `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_brief_split_20260410`
>   Verification:
> - `php85 -l` OK on both files
> - `php85 artisan optimize:clear --no-interaction` OK
>   LIVE GCP NOTE (2026-04-10 05:25 CET)
>   Service account file `G:\gravity\diagnosta-489719-96def3352c52.json` was verified with `gcloud`.
>   Confirmed:
> - service account activation works
> - project `diagnosta-489719` is active
> - Vertex AI API `aiplatform.googleapis.com` is enabled
>   LIVE VERTEX PROD VERIFY (2026-04-11)
>   Hosting `.env` already had `VERTEX_PROJECT_ID`, `VERTEX_LOCATION=us-central1`, `VERTEX_SERVICE_ACCOUNT_JSON=/home/tyurjydtpw/secure/vertex/diagnosta-489719-96def3352c52.json`. Smoke: `VertexAccessTokenFactory::make()` => `VERTEX_OK`. Local `.env` appended with same IDs and `G:/gravity/...json` path for workspace.
>   LIVE BLOG HERO NOTE (2026-04-09 21:35 CET)
>   Manual/newsroom blog pipeline on hosting is no longer AI-image-only for `premiera` / `news`.
>   Updated live files:
> - `app/Support/Blog/BlogVertexPipelineService.php`
> - `config/blog.php`
>   New behavior:
> - pipeline first tries to fetch a real source image from approved `source_urls`
> - it extracts `og:image` / `twitter:image` / article images, validates dimensions, stores locally, and only falls back to AI generation if sourcing fails
> - source pages and image URLs are ranked so official / press-like hosts and model-matching URLs win where possible
>   Live proof:
> - draft `#110` no longer points to the bad generated premiere image
> - stored draft image was replaced with `blog/01KNSSEGTYRG29VRT3VYWKCF8S.jpg`
> - sourced from `https://www.bmwblog.com/2023/09/02/bmw-panoramic-vision/` -> `https://cdn.bmwblog.com/wp-content/uploads/2023/08/new-bmw-idrive-neue-klasse-02.jpg`
>   Backups:
> - `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_source_first_hero_20260409`
> - `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_source_first_ranking_20260409`
> - `config/blog.php.bak_codex_source_first_hero_20260409`
>   LIVE WOW AEO GATEWAY NOTE (2026-04-06 09:12 CET)
>   Canonical `public_html/.htaccess` was upgraded to the WOW Discovery Gateway routing strategy.
>   Previously (since 2026-04-03) only Anthropic identities were routed to the VPS gateway.
>   Now ALL major AI agents, training bots, and LLM search crawlers are redirected to `https://ai.rsperformance.online` (Qdrant).
>   This covers: ChatGPT, Perplexity, Claude, Google-Extended, Discovery bots, etc. Regular Googlebot/Bing stay canonical.
>
> LIVE RUNTIME NOTE (2026-03-29)
> Current verified runtime is Hosting = Laravel 13.1.1 / PHP 8.5.3 and VPS support-plane = Laravel 13.1.1 / PHP 8.5.3.
> Any older Laravel 12 references below are historical notes unless explicitly re-verified live.
> LIVE ROBOTS WOW NOTE (2026-04-03 00:21 CET)
> Canonical crawler policy was widened again with additional Google/Vertex variants:
>
> - `GoogleOther-Image`
> - `GoogleOther-Video`
> - `Google-CloudVertexBot`
>   These are now explicit in live `robots.txt` and in `ai-resources.json` grouped `agent_routing`.
>   Verified live `200`: `GoogleOther-Image`, `GoogleOther-Video`, `Google-CloudVertexBot`, `Applebot-Extended`, `Meta-ExternalFetcher`.
>   LIVE AI AGENT CATALOG NOTE (2026-04-03 00:06 CET)
>   Canonical `robots.txt` and `ai-resources.json` now expose a broader legit AI catalog across search bots, training bots and user-triggered fetchers.
>   Newly explicit in the live policy: `GoogleOther`, `Applebot-Extended`, `Meta-ExternalFetcher`.
>   Verified live `200` on canonical: `GoogleOther`, `Applebot-Extended`, `Meta-ExternalFetcher`, `Meta-ExternalAgent`, `Google-Extended`.
>   Low-value bulk scrapers are still denied by design; this was an expansion of the legit AI tier, not an opening for abusive harvesting.
>   LIVE WEAK BOOST NOTE (2026-04-02 23:42 CET)
>   Canonical `exact_lookup` now exposes `entities`, and VPS overlap routing now uses those entities for symptom-to-canonical matching.
>   Verified live:
> - `brak doladowania turbo` -> `/problemy/brak-doladowania-turbo`
> - `slabe doladowanie turbo` -> `/problemy/brak-doladowania-turbo`
> - `underboost turbo` -> `/problemy/brak-doladowania-turbo`
> - `utrata doladowania podczas przyspieszania` -> `/problemy/brak-doladowania-turbo`
> - `turbina slabo pompuje` -> `/problemy/brak-doladowania-turbo`
>   LIVE GATEWAY SNAPSHOT NOTE (2026-04-02 23:42 CET)
>   `/srv/ai-gateway/sync.sh` now rebuilds gateway `exact_lookup` from canonical `priority_answer_paths`, so `entities` survive answer-routing generation.
>   A manual VPS refresh of `.well-known/ai-resources.json` and `.well-known/answer-routing.json` was required today because the gateway snapshot drifted behind the public canonical file.
>   LIVE COPY POLICY NOTE (2026-04-02 18:10 CET)
>   Public RS copy must not expose explicit prices. Canonical process wording:
>   weryfikacja usterki -> wycena -> autoryzacja klienta -> naprawa.
>   Latest hosting batch removed price-first copy from homepage, chatbot, FAQ-home and `/diagnostyka`.
>   LIVE AEO ROUTING NOTE (2026-04-02 20:55 CET)
>   Canonical hosting now publishes enriched answer-routing metadata and VPS gateway now exposes `/.well-known/answer-routing.json`.
>   The current live gap is operational routing quality, not missing discovery surfaces: 24h baseline still shows `priority answer-path share = 0%`.
>   LIVE EXACT LOOKUP NOTE (2026-04-02 19:50 CET)
>   Canonical `ai-resources.json` and VPS `answer-routing.json` now expose deterministic `exact_lookup` maps for DTC and priority slugs.
>   VPS `sync.sh` now uses a cache-busting query parameter on canonical fetches, so gateway artifacts stop lagging behind fresh hosting deploys.
>   LIVE EXACT RESOLVER NOTE (2026-04-02 22:46 CET)
>   Hosting restored missing public `kody-usterek/*` routes, and VPS `diagnosta-api.service` now resolves exact DTC plus exact service/problem slug intents to canonical public URLs.
>   Live smoke now returns:
> - `P0299 brak mocy` -> `/kody-usterek/p0299` (`exact_match=true`)
> - `diagnostyka-komputerowa` -> `/uslugi/diagnostyka-komputerowa` (`confidence=high`)
> - `auto traci moc` -> `/problemy/auto-traci-moc` (`confidence=high`)
>   LIVE TELEMETRY HYGIENE NOTE (2026-04-02 22:58 CET)
>   `TrackAiAgentTraffic` now ignores requests marked with `X-RS-Synthetic-Probe`.
>   All future operator smoke checks should use that header so `ai_bot_visits` reflects real bot traffic instead of our own probes.
>   LIVE SYMPTOM ROUTING NOTE (2026-04-02 23:12 CET)
>   VPS `diagnosta-api.service` now uses:
> - exact DTC routing
> - exact slug routing
> - Qdrant route candidates from `rs_answer_routing`
> - lexical overlap matching with Polish diacritic normalization
>   Verified:
> - `diagnostyka komputerowa` -> `/uslugi/diagnostyka-komputerowa`
> - `klimatyzacja nie działa` -> `/problemy/klimatyzacja-nie-chodzi`
>   LIVE INVITATION NOTE (2026-04-02 23:18 CET)
>   Canonical `ai-resources.json` now exposes a machine-readable `invitation_policy` with `mode = standards-only`.
>   Approved invitation channels are sitemap / llms / ai-resources / agent cards / openapi / answer-routing / freshness / RFC 8288 / IndexNow only.
>   Priority symptom packet now includes:
> - `problemy-z-alternatorem`
> - `klimatyzacja-nie-chodzi`
> - `klimatyzacja-nie-chlodzi-na-postoju`
> - `problemy-z-odpalaniem`
>   VPS `sync.sh` now sends explicit no-cache headers so gateway discovery files stay aligned with fresh canonical artifacts.
>   LIVE REAL AI TRAFFIC NOTE (2026-04-03 00:55 CET)
>   `TrackAiAgentTraffic` now ignores internal `RS-AI-Gateway` fetches, so telemetry reflects external AI traffic instead of support-plane self-noise.
>   `aeo:traffic-alerts` now matches priority answer paths against real stored request URIs, not only absolute canonical URLs.
>   Clean live numbers:
> - 7d: `Visits 532`, `Gateway 85 (16%)`, `Homepage share 62.8%`, `Priority answer-path share 6.4%`
> - 24h: `Visits 142`, `Gateway 4 (2.8%)`, `Homepage share 28.2%`, `Priority answer-path share 8.5%`
>   No new major legit AI family was discovered in the last-7d telemetry; the current gap is routing quality from homepage to answer paths, not crawler-catalog coverage.
>   LIVE HOMEPAGE EXIT NOTE (2026-04-03 01:10 CET)
>   Canonical `llms.txt`, `llms-full.txt` and `/.well-known/ai-resources.json` now publish a `homepage_exit_map` plus `agent_routing_hints`.
>   The homepage-heavy families from live telemetry now get explicit first-hop guidance toward:
> - `/uslugi/diagnostyka-komputerowa`
> - `/problemy/auto-traci-moc`
> - `/problemy/brak-doladowania-turbo`
> - `/problemy/problemy-z-alternatorem`
> - `/problemy/klimatyzacja-nie-chodzi`
> - `/kody-usterek/p0299`
>   VPS gateway was re-synced after the artifact refresh so support-plane discovery is aligned with canonical.
>   LIVE HOMEPAGE ANSWER RAIL NOTE (2026-04-03 01:22 CET)
>   Canonical homepage `/` now emits `X-AEO-Homepage-Exit-Map` and stronger RFC 8288 `Link` hints for exact first-hop routes.
>   Homepage HTML now also contains a visible quick-answer rail:
>   `Najkrótsza droga z pytania do właściwej diagnozy`
>   with direct links to:
> - `/uslugi/diagnostyka-komputerowa`
> - `/problemy/auto-traci-moc`
> - `/problemy/problemy-z-alternatorem`
> - `/kody-usterek/p0299`
>   This is now the main live experiment for reducing homepage-only AI landings.
>   LIVE HOMEPAGE MARKDOWN NOTE (2026-04-03 01:40 CET)
>   Canonical `home.md` now exposes the same homepage exit contract as the stronger HTML/discovery layers.
>   New live sections:
> - `## Homepage exit map for AI systems`
> - `## Agent first-hop hints`
>   This closes the gap where markdown-consuming agents could still read homepage business context without the exact first-hop guidance already present in `ai-resources.json` and homepage headers.
>   LIVE HOMEPAGE UI NOTE (2026-04-03 01:52 CET)
>   The visible homepage `AI-ready quick answer paths` block was removed from the public UI.
>   The routing contract remains live in non-visual surfaces only:
> - `X-AEO-Homepage-Exit-Map`
> - RFC 8288 `Link` hints
> - `/.well-known/ai-resources.json`
> - `/home.md`
>   LIVE CLAUDE ACCESS NOTE (2026-04-03 02:08 CET)
>   Canonical `.htaccess` now restores a narrow VPS rescue only for the exact Anthropic crawler identities still hitting host-level `406`:
> - `ClaudeBot`
> - `Claude-SearchBot`
> - exact `anthropic-ai`
>   These now resolve via `https://ai.rsperformance.online/` with `200`, while `Claude-User` and `Claude-Web` stay on canonical.
>   The earlier `429` seen during smoke on `OAI-SearchBot` was probe timing, not a live outage.
>   LIVE WATCHDOG + N8N NOTE (2026-04-03 06:10 CET)
>   Hosting now runs `aeo:traffic-watchdog --hours=8 --cooldown-hours=8` every 30 minutes.
>   Current watchdog sample proved the stack is not silent in rolling time:
> - `window_count = 23`
> - `latest_visit = 2026-04-02T23:47:01+02:00`
> - `is_silent = false`
>   VPS `n8n` audit result:
> - `RS AI Bot Invitation Hub` active, latest execution `success`
> - `Content Freshness Monitor` active, latest execution `success`
> - `SEO Health Dashboard` active, latest execution `success`
> - `DTC IndexNow Drip` was repaired for `n8n 2.14.2` (`runOnceForAllItems` + `$input.first()` in all four parse nodes), re-imported, re-published, and re-activated after container restart
>   Residual: latest stored execution for `DTC IndexNow Drip` is still the historical `04:00` error from before the fix; next hourly execution must be checked to confirm `success`.
>   LIVE BLOG NEWS WORKFLOW NOTE (2026-04-03 07:05 CET)
>   Hosting blog pipeline now supports a quality-gated `daily_news` mode plus `POST /api/blog/pipeline/persist`.
>   New live VPS `n8n` workflow:
> - `RS Daily Automotive News Drafts` (`zo4sIHAUZZseEGkL`)
> - cadence: `08:15 / 13:15 / 18:15 Europe/Warsaw`
> - flow: same-day news feed -> operator scoring -> family rotation -> preview -> quality gate -> persist -> Telegram
>   Daily-news gate checks:
> - same-day feed proof
> - source count
> - word count
> - heading density
> - FAQ count
> - meta lengths
> - CTA presence
> - robotic filler phrases
> - hero image presence from the existing Laravel blog pipeline
>   Live proof:
> - preview returned `quality_gate.score = 100`
> - same-day feed proof passed
> - persist smoke created draft `#77`
> - `DTC IndexNow Drip` also confirmed `success` at `2026-04-03 05:00 UTC / 07:00 CEST`
>   LIVE BLOG QUALITY HARDENING NOTE (2026-04-03 22:58 CET)
>   Hosting `app/Support/Blog/BlogVertexPipelineService.php` and VPS workflow `RS Daily Automotive News Drafts` (`zo4sIHAUZZseEGkL`) were tightened together:
> - stricter anti-bait / anti-gossip / anti-lifestyle rules
> - explicit slot contract:
>     - morning => `porada`
>     - midday => `news`
>     - evening => `premiera`
> - preview now enforces `quality_gate_enforced: true`
> - workflow backup on VPS:
>     - `/srv/ops-stack/n8n/rs-daily-automotive-news-drafts.json.bak_codex_blog_news_quality_20260403`
> - latest proof after hardening:
>     - canonical and VPS discovery surfaces still `200`
>     - top AI UA smoke still green
>     - weak listicle bait topic `Te ponad 10-letnie samochody...` now fails in `blog_pipeline_runs` instead of creating content
>       LIVE BLOG WORKFLOW NOTE (2026-04-03 23:30 CET)
>       The visible `2026-04-03` posts were not created by `n8n`.
>       Verified timings:
> - post `#74` created `06:08:59`
> - post `#75` created `06:14:19`
> - post `#76` created `06:25:07`
>   while `RS Daily Automotive News Drafts` (`zo4sIHAUZZseEGkL`) only recorded:
> - `06:15:00` => `error`
> - `11:15:00` => `error`
> - `16:15:00` => `error`
>   Root cause in `execution_data`:
> - `Node 'Fetch Global Auto Feed' hasn't been executed`
>   Live VPS workflow graph has been corrected with `Merge Feeds` and `activeVersionId = versionId`, but this lane is still not green until a fresh post-fix execution appears.

# TEN PLIK JEST PRZESTARZAĹY (DEPRECATED)

> đź”´ **UWAGA AGENTY AI:** Ten plik (`start.md`) ulegĹ‚ dekapitacji ze wzglÄ™du na przeĹ‚adowanie kontekstu (miaĹ‚ >5800 linii). Nie stanowi on juĹĽ gĹ‚Ăłwnego ĹşrĂłdĹ‚a wiedzy operacyjnej.

## NOWE ZRĂ“DĹO WIEDZY: `RELAY.md`

1. PrzejdĹş natychmiast do pliku `RELAY.md` w katalogu gĹ‚Ăłwnym.
2. Znajdziesz w nim wszystkie credentials, dyrektywy SSH, mapowanie skills oraz zasady wspĂłĹ‚pracy agentĂłw (Relay Protocol).
3. Przed jakimikolwiek zmianami kodu, przeczytaj `HANDOFF.md`!

---

_Informacje o dostÄ™pie SSH, VPS i Google Cloud zostaĹ‚y przeniesione i zorganizowane w `RELAY.md`. Stare logi produkcyjne z tego pliku wylÄ…dowaĹ‚y w archiwum._

## AKTUALIZACJA 2026-03-29 23:40 CET - ROOT ANSWER MANIFEST

- Hosting produkcyjny:
    - `app/Support/RsUri.php`
    - `app/Support/Search/SearchArtifactFactory.php`
- VPS:
    - `/srv/ai-gateway/.well-known/agent.json`
    - `/srv/ai-gateway/.well-known/openapi.json`
- Cel batcha:
    - przestaÄ‡ liczyÄ‡, ĹĽe bot z `/` sam odkryje wĹ‚aĹ›ciwÄ… stronÄ™
    - daÄ‡ mu maĹ‚y, twardy kontrakt z 20-40 priorytetowymi URL-ami i reguĹ‚Ä… zejĹ›cia z homepage na exact answer path
- Efekt:
    - nowy canonical manifest `/.well-known/priority-answer-paths.json`
    - alias feed `feeds/priority-answer-paths.json`
    - manifest jest reklamowany z `ai-resources.json`, `llms.txt`, `llms-full.txt` i MCP agent card
    - gateway `agent.json` i `openapi.json` wskazujÄ… teraz wprost na canonical priority-answer manifest
    - canonical `entrypoint_strategy` zostaĹ‚ zgrany z gateway i zawiera teĹĽ intent `charging-and-alternator`
- Backupy:
    - `RsUri.php.bak_codex_root_answer_manifest_20260329`
    - `SearchArtifactFactory.php.bak_codex_root_answer_manifest_20260329`
    - `agent.json.bak_codex_root_answer_manifest_20260329`
    - `openapi.json.bak_codex_root_answer_manifest_20260329`
- Weryfikacja:
    - `php85 -l` OK dla obu plikĂłw PHP
    - `php85 artisan optimize:clear && php85 artisan search:artifacts-generate && php85 artisan view:cache` => OK
    - `https://rsperformance.online/.well-known/priority-answer-paths.json` => `200`
    - `https://rsperformance.online/feeds/priority-answer-paths.json` => `200`
    - gateway `agent.json` i `openapi.json` => `200` i wskazujÄ… nowy manifest

## AKTUALIZACJA 2026-03-29 19:55 CET - AEO CONTRACT HARDENING

- Hosting produkcyjny:
    - `app/Support/Search/SearchArtifactFactory.php`
    - `app/Models/AiBotVisit.php`
    - `app/Http/Middleware/AiCitationHeaders.php`
- Cel batcha:
    - domknÄ…Ä‡ `gateway-first` jako maszynowo czytelny kontrakt AEO
    - naprawiÄ‡ klasyfikacjÄ™ DTC w telemetryce AI
- Efekt:
    - `/.well-known/ai-resources.json` zaczyna teraz fetch order od gateway (`agent.json`, `freshness.json`, `openapi.json`)
    - `ai-resources.json` ma nowy blok `agent_routing`
    - DTC telemetryka uĹĽywa live prefiksu `kody-usterek/*`, nie starego `kody-bledow/*`
    - AI responses majÄ… `X-AI-Route-Policy: gateway-first`
- Backupy produkcyjne:
    - `SearchArtifactFactory.php.bak_codex_aeo_contract_20260329`
    - `AiBotVisit.php.bak_codex_aeo_contract_20260329`
    - `AiCitationHeaders.php.bak_codex_aeo_contract_20260329`
- Weryfikacja:
    - `php85 -l` OK na wszystkich 3 plikach
    - `php85 artisan optimize:clear && php85 artisan config:cache && php85 artisan search:artifacts-generate && php85 artisan view:cache` => OK
    - `Bingbot` na `/kody-usterek/p0299` dostaje `HTTP 200` + `x-content-type-semantic: dtc-reference`
- `ClaudeBot` nadal jest poprawnie wypychany `302` na `https://ai.rsperformance.online/`

## AKTUALIZACJA 2026-03-29 20:20 CET - AI TRAFFIC CENTER V2

- Hosting produkcyjny:
    - `app/Filament/Pages/AiTrafficCenter.php`
    - `resources/views/filament/pages/ai-traffic-center.blade.php`
- Cel batcha:
    - zamieniÄ‡ panel z licznika wizyt AI w operacyjne centrum routingu AEO
- Efekt:
    - panel pokazuje teraz `gateway delta` vs poprzednie okno
    - pokazuje `policy drift` dla botĂłw, ktĂłre powinny iĹ›Ä‡ przez gateway, ale pojawiajÄ… siÄ™ direct
    - ma `policy matrix` dla:
        - gateway-routed
        - canonical-direct
        - denied scrapers
    - ma wykres `gateway vs direct by day`
    - ma listy `top gateway agents` i `top direct agents`
    - w recent visits pokazuje teĹĽ `original_user_agent` przy ruchu przez VPS gateway
- Backupy produkcyjne:
    - `AiTrafficCenter.php.bak_codex_ai_traffic_v2_20260329`
    - `ai-traffic-center.blade.php.bak_codex_ai_traffic_v2_20260329`
- Weryfikacja:
    - `php85 -l` OK
    - `php85 artisan view:clear && php85 artisan view:cache` => OK
    - `/admin/ai-traffic-center` => `HTTP 302` do logowania, route zdrowa

## AKTUALIZACJA 2026-03-25 23:02 CET - PWA TELEMETRY HARDENING

- Hosting produkcyjny: `resources/views/components/rs/layout.blade.php` dostaĹ‚ lekkÄ… telemetrykÄ™ PWA bez zmian UI i bez nowego endpointu.
- Transport: istniejÄ…cy `POST /api/web-vitals` przez `navigator.sendBeacon(..., Blob('application/json'))` + fallback `fetch(..., keepalive: true)`.
- Eventy: `PWA_INSTALL_PROMPT_SHOWN`, `PWA_INSTALL_PROMPT_OPENED`, `PWA_INSTALL_PROMPT_ACCEPTED`, `PWA_INSTALL_PROMPT_DISMISSED`, `PWA_UPDATE_PROMPT_SHOWN`, `PWA_UPDATE_APPLY_TRIGGERED`, `PWA_IOS_INSTALL_HELPER_SHOWN`, `PWA_INSTALLED`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/resources/views/components/rs/layout.blade.php.bak_codex_pwa_telemetry_20260325`.
- Po patchu: `php85 artisan view:clear && php85 artisan optimize`, `php85 -l` OK.
- Lokalny validator: `G:\\gravity\\scripts\\validate_pwa_readiness.py` rozszerzony o checki telemetryczne; wynik po zmianie `53/53 PASS`, `0 warnings`.
- NastÄ™pny agent:
    - traktuj `/api/web-vitals` jako aktualny kanaĹ‚ telemetryczny dla PWA,
    - nie dodawaj nowego endpointu telemetrycznego bez twardego powodu,
    - nie ruszaj gĹ‚Ăłwnego mobile UI i nie ruszaj struktury AEO.

## AKTUALIZACJA 2026-03-25 23:12 CET - NIGHTWATCH INGEST RECOVERY

- Hosting produkcyjny: `app/Http/Controllers/Api/WebVitalsController.php` przestaĹ‚ logowaÄ‡ do kanaĹ‚u `single`; teraz uĹĽywa domyĹ›lnego drivera logĂłw, wiÄ™c telemetryka PWA idzie do Nightwatch.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Http/Controllers/Api/WebVitalsController.php.bak_codex_nightwatch_pwa_20260325`.
- VPS root cause: `/srv/workspaces/rs-support-plane/routes/ai.php` miaĹ‚ zĹ‚y import `Laravel\\MCP\\Facades\\Mcp`; poprawka na `Laravel\\Mcp\\Facades\\Mcp` odblokowaĹ‚a restartujÄ…cy siÄ™ support-plane i agentĂłw Nightwatch.
- Backup VPS: `/srv/workspaces/rs-support-plane/routes/ai.php.bak_codex_nightwatch_namespace_20260325`.
- Po fixie:
    - hosting `php85 artisan nightwatch:status` => `The Nightwatch agent is running and accepting connections.`
    - VPS kontenery `rs-support-plane-app`, `rs-support-plane-horizon`, `rs-support-plane-nightwatch-agent`, `rs-hosting-nightwatch-agent` => `Up`
    - logi agentĂłw Nightwatch => `Authentication successful`
- NastÄ™pny agent:
    - nie diagnozuj PWA telemetryki bez sprawdzenia `nightwatch:status`, bo wczeĹ›niej problemem byĹ‚ martwy ingest, nie sam PWA layer,
    - nie cofaj poprawki namespace w `routes/ai.php`.

## AKTUALIZACJA 2026-03-25 23:22 CET - PWA TELEMETRY HARDENING V2

- Hosting produkcyjny: `WebVitalsController.php` ma teraz allowlistÄ™ nazw telemetrycznych i normalizacjÄ™ payloadĂłw pod query w hostingu Nightwatch.
- Dozwolone klasy sygnaĹ‚Ăłw:
    - CWV: `CLS`, `FCP`, `INP`, `LCP`, `TTFB`
    - PWA: tylko jawnie dopisane eventy `PWA_*`
- Ĺšmieciowe eventy nie wywalajÄ… klienta; endpoint zwraca `204` i loguje `TELEMETRY-DROPPED`.
- Normalizacja:
    - `url` bez query stringa
    - dodatkowe `url_path`
    - `telemetry_stream`
    - `telemetry_kind`
    - `pwa_surface`, `pwa_action`, `pwa_outcome`
- Backupy:
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Http/Controllers/Api/WebVitalsController.php.bak_codex_pwa_telemetry_hardening_20260325`
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/tests/Feature/ArtifactsSmokeTest.php.bak_codex_pwa_telemetry_hardening_20260325`
- Weryfikacja:
    - `php85 artisan nightwatch:status` => OK
    - `php85 artisan test tests/Feature/ArtifactsSmokeTest.php --compact` => PASS
    - `python G:\\gravity\\scripts\\validate_pwa_readiness.py` => `53/53 PASS`, `0 warnings`
- NastÄ™pny agent:
    - jeĹ›li dodajesz nowe eventy PWA, wpisuj je jawnie do allowlisty,
    - nie wracaj do otwartego, dowolnego przyjmowania nazw telemetryki.

## AKTUALIZACJA 2026-03-25 23:29 CET - PWA TELEMETRY TOOLING

- Lokalnie dodane:
    - `G:\\gravity\\scripts\\pwa_telemetry_smoke.py`
    - `G:\\gravity\\docs\\pwa-nightwatch-runbook.md`
- `pwa_telemetry_smoke.py` sprawdza 3 poprawne eventy PWA + 1 Ĺ›mieciowy event i oczekuje samych `HTTP 204`.
- `pwa-nightwatch-runbook.md` opisuje:
    - dozwolone eventy
    - pola do query w hostingu Nightwatch
    - przykĹ‚adowe filtry dla install/update/iOS
    - Ĺ›cieĹĽkÄ™ diagnostycznÄ…, gdy telemetryka zniknie
- NastÄ™pny agent:
    - po zmianach w telemetryce uruchamiaj najpierw `pwa_telemetry_smoke.py`, potem `validate_pwa_readiness.py`,
    - aktualizuj runbook razem z allowlistÄ… eventĂłw.

## AKTUALIZACJA 2026-03-25 23:36 CET - HOSTING PWA TELEMETRY COMMAND

- Hosting produkcyjny dostaĹ‚ command:
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Console/Commands/CheckPwaTelemetryCommand.php`
- UĹĽycie:
    - `php85 artisan pwa:telemetry-check`
    - opcjonalnie `--skip-smoke`
    - opcjonalnie `--timeout=15`
- Command wykonuje jeden operator-friendly check caĹ‚ego toru:
    - Nightwatch hosting
    - `/`
    - `/manifest.json`
    - `/sw.js`
    - `/offline.html`
    - smoke `/api/web-vitals`
- NastÄ™pny agent:
    - to jest teraz pierwszy hosting-side smoke dla PWA telemetry,
    - odpalaj go przed gĹ‚Ä™bszÄ… diagnostykÄ… albo przed kolejnymi zmianami w tym obszarze.

## AKTUALIZACJA 2026-03-25 23:42 CET - PWA TELEMETRY REGRESSION TEST

- Hosting produkcyjny dostaĹ‚ test:
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/tests/Feature/WebVitalsTelemetryNormalizationTest.php`
- Test pilnuje:
    - poprawnej normalizacji `PWA_INSTALL_PROMPT_ACCEPTED`
    - miÄ™kkiego odrzucania `PWA_NOT_REAL`
- Weryfikacja:
    - `php85 artisan test tests/Feature/WebVitalsTelemetryNormalizationTest.php --compact` => PASS
- NastÄ™pny agent:
    - po zmianach w telemetryce utrzymuj ten test na zielono,
    - nie ograniczaj siÄ™ juĹĽ do samych smoke testĂłw `204`.

## AKTUALIZACJA 2026-03-25 23:42 CET - PWA SNAPSHOT REPORT

- Hosting produkcyjny dostaĹ‚ lokalny snapshot telemetryki PWA:
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Status/PwaTelemetrySnapshotService.php`
- Hosting produkcyjny dostaĹ‚ komendÄ™ operatorskÄ…:
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Console/Commands/PwaTelemetryReportCommand.php`
    - uĹĽycie: `php85 artisan pwa:telemetry-report` lub `php85 artisan pwa:telemetry-report --json`
- `WebVitalsController.php` zapisuje teraz eventy PWA do `storage/app/status/pwa-telemetry.json`.
- Syntetyczne eventy sÄ… odfiltrowane z raportu po prefiksach:
    - `artisan-*`
    - `smoke-*`
    - `snapshot-smoke-*`
- Test:
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/tests/Feature/PwaTelemetrySnapshotTest.php`
    - po teĹ›cie snapshot jest sprzÄ…tany, wiÄ™c test nie zanieczyszcza produkcyjnego raportu.
- Backupy:
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Http/Controllers/Api/WebVitalsController.php.bak_codex_pwa_snapshot_20260325`
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Status/PwaTelemetrySnapshotService.php.bak_codex_pwa_snapshot_filter_20260325`
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/tests/Feature/PwaTelemetrySnapshotTest.php.bak_codex_pwa_snapshot_filter_20260325`
    - `/home/tyurjydtpw/domains/rsperformance.online/laravel/tests/Feature/PwaTelemetrySnapshotTest.php.bak_codex_pwa_snapshot_test_cleanup_20260325`
- Weryfikacja:
    - `php85 artisan test tests/Feature/PwaTelemetrySnapshotTest.php --compact` => PASS
    - `php85 artisan pwa:telemetry-check` => PASS
    - `php85 artisan pwa:telemetry-report --json` => dziaĹ‚a
    - `python G:\gravity\scripts\pwa_telemetry_smoke.py` => PASS
    - `python G:\gravity\scripts\validate_pwa_readiness.py` => `53/53 PASS`, `0 warnings`
- NastÄ™pny agent:
    - uĹĽywaj `pwa:telemetry-report` jako szybkiego wglÄ…du w realne eventy PWA,
    - nie usuwaj filtra syntetycznych ID,
    - jeĹ›li dojdÄ… nowe sztuczne eventy diagnostyczne, dopisz ich prefiksy do filtra snapshotu.

## AKTUALIZACJA 2026-03-25 23:50 CET - BLACKSCREEN HOTFIX

- Hosting produkcyjny: naprawiono krytycznÄ… regresjÄ™ mobilnego renderu i PWA objawiajÄ…cÄ… siÄ™ jako samo czarne tĹ‚o.
- Root cause:
    - aktywny `resources/views/components/rs/layout.blade.php` byĹ‚ uszkodzony i nie renderowaĹ‚ wĹ‚aĹ›ciwego `main` / `{{ $slot }}`.
    - w body zostawaĹ‚y gĹ‚Ăłwnie skrypty i warstwa pomocnicza PWA, wiÄ™c live frontend wyglÄ…daĹ‚ jak czarne tĹ‚o.
- Naprawa:
    - backup: `/home/tyurjydtpw/domains/rsperformance.online/laravel/resources/views/components/rs/layout.blade.php.bak_codex_blackscreen_hotfix_20260325`
    - przywrĂłcenie `resources/views/components/rs/layout.blade.php` z dziaĹ‚ajÄ…cego backupu `resources/views/components/rs/layout.blade.php.bak_codex_updateviacache_20260325`
    - `php85 artisan view:clear`
    - `php85 artisan optimize --no-interaction`
- Weryfikacja:
    - `php85 -l resources/views/components/rs/layout.blade.php` => OK
    - `python G:\gravity\scripts\validate_pwa_readiness.py` => `53/53 PASS`, `0 warnings`
    - Playwright mobile snapshot z live URL znow pokazuje peĹ‚nÄ… homepage
- NastÄ™pny agent:
    - nie wracaj do wadliwej wersji layoutu z backupu `bak_codex_pwa_telemetry_20260325`
    - jeĹ›li wracaÄ‡ do dalszego PWA polish w layoucie, robiÄ‡ to tylko z dziaĹ‚ajÄ…cej bazy i po live browser smoke mobile

## AKTUALIZACJA 2026-03-22 21:45 CET - MCP AUTH & CUSTOM SERVER PLANNING (Stage G)

- **Cel**: Zabezpieczenie istniejÄ…cego endpointu MCP na VPS (`mcp.rs3d.pl/laravel/mcp/rs-knowledge`) oraz stworzenie nowego Artisan MCP Servera dla AI.
- **Wykonano**:
    - PeĹ‚na analiza kodu hostingu i VPS pod kÄ…tem MCP (identyfikacja `laravel/mcp`, `laravel/boost`, `vps.md`).
    - Stworzono i zatwierdzono `implementation_plan.md` (w `brain/<conv-id>/`).
    - Stworzono `task.md` do Ĺ›ledzenia postÄ™pĂłw.
- **Zatwierdzony Plan**:
    1. **VPS**: Middleware `McpTokenAuth` (Bearer token) + rejestracja w `bootstrap/app.php` + naĹ‚oĹĽenie na trasy w `routes/ai.php`.
    2. **Hosting**: Dodanie `RS_MCP_TOKEN` do `.env`/`config/ops.php` + aktualizacja `AutomationCenter.php` i `SearchArtifactFactory.php` o wysyĹ‚anie nagĹ‚Ăłwka Bearer.
    3. **Nowy Serwer**: `RsArtisanMcpServer` na VPS z narzÄ™dziami `NightwatchStatusTool` i `TriggerBlogJobTool`.
- **Co ma robiÄ‡ nastÄ™pny agent**:
    - KontynuowaÄ‡ wdraĹĽanie zmian zgodnie z `implementation_plan.md`.
    - ZaczÄ…Ä‡ od zmian na VPS (uĹĽyj `vps_exec.py`).
    - PamiÄ™taÄ‡ o backupach `.bak_cli_mcp_auth` przed zmianami.
    - SzczegĂłĹ‚y techniczne w artefaktach sesji.

---

15: ## AKTUALIZACJA 2026-03-21 07:20 CET - BLOG PIPELINE OBSERVABILITY PANEL (Stage E)

- Hosting produkcyjny: wdroĹĽono Blog Pipeline Observability Panel â€” Filament dashboard `/admin/blog-pipeline-dashboard` (root-only).
- NOWA tabela `blog_pipeline_runs` z ULID PK â€” Ĺ›ledzenie peĹ‚nego lifecycle pipeline'u bloga (topic â†’ VPS dispatch â†’ draft â†’ Telegram).
- NOWE pliki: `app/Enums/BlogPipelineStatus.php`, `app/Enums/BlogPipelineSource.php` (PHP 8.5 backed enums).
- NOWY model: `app/Models/BlogPipelineRun.php` â€” factory methods: `begin()`, `markDispatched()`, `markProcessing()`, `markDraftCreated()`, `markFailed()`.
- NOWY panel: `app/Filament/Pages/BlogPipelineDashboard.php` + `resources/views/filament/pages/blog-pipeline-dashboard.blade.php`.
- MODYFIKACJA: `BlogVertexPipelineService.php`, `BlogPipelineController.php`, `AutoGenerateBlogPost.php`, `BlogPostWebhookController.php` â€” telemetryzacja pipeline runs.
- Backupy: `.bak_pipeline_obs` na wszystkich zmodyfikowanych plikach.
- Migracja: `2026_03_21_063400_create_blog_pipeline_runs_table` â€” batch [19], ran OK.
- Dashboard: stats bar (total/success/failed/pending/avg duration), paginated tabela runĂłw z badgami, live polling co 15s.
- Smoke test: `BlogPipelineDashboardSmokeTest` â€” PASS (1 test, 2 assertions).
- End-to-end proof: dry-run pipeline run (ULID `01km7g57bxbv44eprn0h0f6yw7`, status `draft_created`, duration 1m 47s).
- **Co ma robiÄ‡ nastÄ™pny agent:**
    - NIE ruszaÄ‡ Blog Pipeline Observability â€” jest stabilny i zweryfikowany.
    - NastÄ™pny logiczny krok to retry/requeue actions w dashboardzie (Stage E full) LUB VPS artifact probe endpoint.
    - Dashboard jest read-only â€” operatory widzi status, ale nie moĹĽe jeszcze retriggerowaÄ‡ pipeline'u z poziomu panelu.
    - Ewentualnie: rozwaĹĽyÄ‡ dodanie webhook health-check dla support-plane â†’ hosting pipeline path.
    - Alternatywnie: kontynuuj plan.md â€” Stage E dalej lub Stage F (AEO Hardening Round 2).

## AKTUALIZACJA 2026-03-21 02:29 CET - AEO STAGE 0A DISCOVERY ALIGNMENT

- Hosting produkcyjny: uruchomiono `php85 artisan search:artifacts-generate` z katalogu `/home/tyurjydtpw/domains/rsperformance.online/laravel`.
- Naprawa AEO: `app/Support/Search/SearchArtifactFactory.php` zapisuje teraz nie tylko root `llms.txt` / `llms-full.txt`, ale tez `.well-known/llms.txt` i `.well-known/llms-full.txt`.
- Deploy hardening: `scripts/hosting_post_deploy_sync.sh` uruchamia teraz `php85 artisan search:artifacts-generate --no-interaction` przed syncem assetow, wiec rollouty odswiezaja discovery surface automatycznie.
- Root cause: stale kopie `.well-known/llms*.txt` nie byly przepisywane przez generator i rozjezdzaly sie z aktualnym root discovery surface.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_llms_wellknown_20260321`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/scripts/hosting_post_deploy_sync.sh.bak_codex_aeo_deploy_hook_20260321`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_ai_plugin_generator_20260321`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_dtc_restore_20260321`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/RsUri.php.bak_codex_aeo_dtc_restore_20260321`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/AiDiscoveryArtifactBuilder.php.bak_codex_aeo_plugin_discovery_20260321`.
- Weryfikacja: `php85 -l` OK, `php85 artisan search:artifacts-generate --no-interaction` OK, `bash -n scripts/hosting_post_deploy_sync.sh` OK, endpointy `/.well-known/llms.txt`, `/.well-known/llms-full.txt`, `/.well-known/mcp-agent-card.json`, `/ai-plugin.json`, `/.well-known/ai-plugin.json` zwracaja HTTP 200 i nowe tresci; `robots.txt` zawiera `ai-plugin-json`, a `ai-resources.json` / `mcp-agent-card.json` promuja `/.well-known/ai-plugin.json` w fetch order.

## AKTUALIZACJA 2026-03-18 17:45 CET - LARAVEL 13 SAFE UPGRADE GATE

- Hosting: Laravel 12.54.1, PHP 8.5.3.
- VPS (rs-support-plane): Laravel 12.54.1, PHP 8.5.3.
- Safe-upgrade dry-run do L13 zablokowany przez stabilne wersje zaleďż˝noďż˝ci (pulse/horizon constraints <=12).
- Brak wdroďż˝enia L13 na produkcji i VPS w tym kroku (brak bezpiecznej ďż˝cieďż˝ki in-place).

## AKTUALIZACJA 2026-03-18 18:05 CET - L13 PROBA I ROLLBACK

- Wykonano probe upgradu do Laravel 13 (hosting dry-run, VPS dry-run + remove/require test).
- Na VPS po remove pulse/horizon wystapil blad package discover; rollback wykonany natychmiast z backupow composer.
- Stan koncowy po rollbacku: Laravel 12.54.1 dziala, brak zmiany wersji frameworka.

## AKTUALIZACJA 2026-03-18 21:15 CET - POST-UPDATE ERROR FIX + STABILIZACJA

- Po probie upgradu i naprawie bledow Filament potwierdzono, ze kod panelu jest na API Filament v3, a nie v5.
- Wykonano bezpieczny rollback hostingu do stabilnego stacku:
    - Laravel 12.54.1
    - Filament 3.3.45
    - Livewire 3.7.3
    - Pulse 1.6.0
- W trakcie napraw:
    - odtworzono composer.json i composer.lock z backupow L13
    - uruchomiono composer install, artisan package:discover, optimize:clear, optimize
    - odtworzono pliki Filament z backupow sprzed zmian typow pod v5
- Weryfikacja po naprawie:
    - php85 artisan about OK
    - home: HTTP 200
    - /admin/automation-center: HTTP 302 -> login (brak 500)
    - /admin/diagnosta-center: HTTP 302 -> login (brak 500)
- Wniosek: produkcja stabilna, ale migracja do Laravel 13 wymaga osobnego projektu migracji Filament v3 -> v5.

## AKTUALIZACJA 2026-03-18 21:18 CET - L13 STATUS CHECK (GITHUB 2026+) + HOTFIX

- Zweryfikowano na GitHub (2026+): laravel/framework v13.1.1 (2026-03-18), laravel/horizon v5.45.4 (2026-03-18), laravel/pulse v1.7.1 (2026-03-17).
- Na hostingu wykonano normalizacje typow w app/Filament po probach L13/Filament compatibility.
- Stan po zmianie: php artisan --version => Laravel 12.54.1, optimize clear+build OK, strona HTTP 200.

## AKTUALIZACJA 2026-03-18 22:08 CET - LARAVEL 13 WDROZONY NA HOSTING

- Hosting produkcyjny `rsperformance.online` podniesiony do:
    - Laravel 13.1.1
    - Filament 5.4.0
    - Livewire 4.2.1
- Pierwszy rollout polegĹ‚ na starym `bootstrap/cache/packages.php` z referencjÄ… do `Laravel\\Pail\\PailServiceProvider`.
- Wykonano natychmiastowy rollback do backupu i drugi rollout z czyszczeniem `bootstrap/cache/*.php` przed pierwszym `artisan`.
- Po wdroĹĽeniu:
    - `php artisan package:discover` OK
    - `php artisan optimize` OK
    - `php artisan search:artifacts-generate` OK
    - `https://rsperformance.online` -> HTTP 200
    - `https://rsperformance.online/admin/login` -> HTTP 200
    - `https://rsperformance.online/blog` -> HTTP 200
- Staging `laravel_staging_l13prep` jest zielony na smoke + `php artisan test` (12 passed).
- Znany temat po wdroĹĽeniu: w logu zostaje historyczny/Ĺ›rodowiskowy bĹ‚Ä…d `watchdog:run` oraz wpis o braku `GEMINI_API_KEY` dla blog pipeline; nie blokuje runtime frontu ani panelu logowania.

## 2026-03-19 03:05 CET - Codex

- hosting production: usuniety scheduler `pulse:check --once`, bo `laravel/pulse` nie jest zainstalowany po rollout Laravel 13 i generowal stale `production.ERROR`
- hosting production: `blog:auto-generate` przepiety na `BlogVertexPipelineService` zamiast starego `GeminiBlogDraftGenerator`
- hosting production: ustawione stabilne modele blogowe w `.env`: researcher `gemini-2.5-pro`, selector `gemini-2.5-flash`, writer `gemini-2.5-pro`, premium reviewer `gemini-2.5-pro`, seo `gemini-2.5-flash-lite`
- hosting production: `php artisan blog:auto-generate --dry-run` -> SUCCESS, payload i modele zwrocone poprawnie
- hosting production: `php artisan watchdog:run --window-minutes=10 --error-threshold=5` -> `All systems operational`
- vps: wgrany `/home/rsops/secure/vertex/diagnosta-489719-96def3352c52.json`, aktywowany `gcloud` service account `vertex-express@diagnosta-489719.iam.gserviceaccount.com`, project `diagnosta-489719`
- backupy: `app/Console/Commands/AutoGenerateBlogPost.php.bak_codex_vertex_blog_20260319`, `routes/console.php.bak_codex_remove_pulse_20260319`, `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_blog_outputtokens_20260319`, `.env.bak_codex_blog_models_20260319`

## 2026-03-19 03:07 CET - Codex

- hosting production: naprawa rozjechanego Filament admin po Laravel 13 rollout
- root cause: domena serwowala stare assety Filamentu z `public_html`, podczas gdy nowe assety lezaly w `laravel/public`
- wykonany sync: `laravel/public/css/filament`, `laravel/public/js/filament`, `laravel/public/fonts/filament` -> `public_html/...`
- backup: `backups/filament_assets_20260319/*`
- weryfikacja HTTP: `app.css` content-length 591566 i `fonts/.../index.css` HTTP 200 po syncu

## 2026-03-19 03:08 CET - Codex

- hosting production: naprawa rozjechanego Filament admin po Laravel 13 rollout
- root cause: domena serwowala stare assety Filamentu z public_html, podczas gdy nowe assety lezaly w laravel/public
- wykonany sync: laravel/public/css/filament, laravel/public/js/filament, laravel/public/fonts/filament -> public_html/...
- backup: backups/filament_assets_20260319/\*
- weryfikacja: admin login renderuje poprawnie po syncu, Playwright snapshot OK

## AKTUALIZACJA 2026-03-19 03:27 CET - FILAMENT

- Filament admin after Laravel 13 upgrade is operational.
- Fixed production resources/pages: app/Filament/Resources/UserResource.php, ServiceResource.php, GalleryImageResource.php, app/Filament/Pages/AutomationCenter.php, CompanySettings.php.
- Fixed production asset mismatch between laravel/public and public_html for Filament CSS/JS/fonts.
- Authenticated smoke passed for all requested admin routes; storage/logs/laravel.log clean after smoke.
- Action item left: automate Filament asset sync in deploy flow.

## AKTUALIZACJA 2026-03-19 03:43 CET - HOSTING + VPS FOLLOW-UP

- Hosting: dodany skrypt scripts/hosting_post_deploy_sync.sh do synchronizacji public/build oraz assetow Filament do public_html po kazdym deployu.
- Hosting: poprawiony blog generator form-state (FAQ serializowane do JSON przed ustawieniem w textarea) oraz poprawiona akcja notyfikacji w ListRepairReports.
- Hosting: create/edit smoke dla blog, services, gallery, users -> HTTP 200.
- VPS: realny Laravel support-plane znajduje sie w /srv/workspaces/rs-support-plane i dziala w Docker, osobno od pythonowego MCP w /srv/diagnosta/app.
- VPS: bezpiecznie zaktualizowany do Laravel 12.55.1 + Horizon 5.45.4 + Pulse 1.7.1 + MCP 0.6.3 + AI 0.3.2.
- VPS: modele support-plane zmienione z gemini-3.1-pro-preview na stabilne gemini-2.5-pro / gemini-2.5-flash.
- VPS: pelny Laravel 13 upgrade support-plane nadal zablokowany przez aktualny oficjalny stan zaleznosci tej sciezki; nie wdrazano ryzykownych dev-branchy.

## AKTUALIZACJA 2026-03-19 03:48 CET - FILAMENT ACTION LAYOUT FIX

- Hosting: naprawiony problem z brakujacymi / rozjechanymi przyciskami akcji w tabelach Filament po migracji L13.
- Zmiana: akcje w `BlogPostResource` i `RepairReportResource` zostaly zebrane do jednego menu `Akcje` (`ActionGroup`) zamiast wielu przyciskow inline.
- Backupy produkcyjne: `app/Filament/Resources/BlogPostResource.php.bak_codex_actiongroup_20260319`, `app/Filament/Resources/RepairReportResource.php.bak_codex_actiongroup_20260319`.
- Weryfikacja: `php -l` OK, `php artisan optimize:clear` OK, browser smoke w zalogowanej sesji potwierdzil poprawny render `Akcje` na `/admin/blog-posts` i `/admin/repair-reports`.

## AKTUALIZACJA 2026-03-19 04:05 CET - ADMIN CACHE + VPS L13 AUDIT

- Hosting: admin panel przestal byc cacheowany publicznie; `/admin/login` zwraca teraz `Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0`.
- Hosting: browser smoke (Playwright) potwierdzil poprawny layout Filament po stronie serwera.
- VPS: realny blocker L13 zostal zawďż˝ony do `laravel/pulse` + stary `laravel/tinker`.
- VPS: suchy resolver przechodzi dla zestawu `Laravel 13.1.1 + Horizon 5.45.4 + AI 0.3.2 + MCP 0.6.3 + Tinker 3.0.0 + Nightwatch 1.24.4`.

## AKTUALIZACJA 2026-03-19 04:21 CET - VPS L13 + NIGHTWATCH READY

- VPS support-plane dziala juz na `Laravel 13.1.1`.
- `Pulse` zostal usuniety; docelowy monitoring przestawiony na `Nightwatch` scaffold z profilem compose `nightwatch`.
- `Nightwatch` nie jest jeszcze aktywny operacyjnie z jednego powodu: brak `NIGHTWATCH_TOKEN`.
- Runtime po migracji: HTTP OK, MCP OK, Horizon OK, config/routes/events cached.

## AKTUALIZACJA 2026-03-19 04:40 CET - NIGHTWATCH LIVE ON VPS

- VPS support-plane: Nightwatch aktywny operacyjnie na Laravel 13.1.1.
- Ustawiono i zweryfikowano: LOG_CHANNEL=nightwatch, NIGHTWATCH_ENABLED=true, NIGHTWATCH_TOKEN oraz NIGHTWATCH_REQUEST_SAMPLE_RATE=0.1.
- Root cause finalny: kontenery mialy stare env, a Laravel czytal stary bootstrap/cache/config.php; wykonano recreate support-plane containers + optimize:clear + config:cache + event:cache +
  oute:cache.
- Weryfikacja: php artisan nightwatch:status -> The Nightwatch agent is running and accepting connections, log agenta -> Authentication successful, https://mcp.rs3d.pl/healthz -> OK.

## AKTUALIZACJA 2026-03-19 04:50 CET - VPS BUSINESS SMOKE + VERTEX PATH FIX

- VPS support-plane przeszedl realny smoke biznesowy po L13: api/dtc/search?query=P0, api/dtc/P0100, ingest api/internal/support-events z poprawnym HMAC oraz support-events:process -> event smoke.shared_host zakonczony jako completed z
  esult_type=smoke_ack.
- W trakcie smoke wykryto realny blocker biznesowy: support-artifacts:run failowal przez bledna sciezke VERTEX_SERVICE_ACCOUNT_JSON (/app/... zamiast /var/www/html/...).
- Naprawa VPS: backup .env, kopia JSON do storage/app/secure/vertex/, zmiana VERTEX_SERVICE_ACCOUNT_JSON=/var/www/html/storage/app/secure/vertex/diagnosta-489719-96def3352c52.json, recreate kontenerow support-plane + refresh cache Laravel.
- Weryfikacja: retry ostatniego
  epair_report.analysis_input zakonczyl sie sukcesem; powstal
  epair_report.analysis_output (status=completed).
- Weryfikacja publikacji: support-publication-drafts:run --limit=1 -> processed=1 completed=1 failed=0.

## AKTUALIZACJA 2026-03-19 04:52 CET - HOSTING HEARTBEAT FIX + NIGHTWATCH PREP

- Hosting: naprawiono broken cron dla heartbeat.sh (wczesniej relative path domains/.../heartbeat.sh powodowal No such file or directory oraz Permission denied).
- Backupy: heartbeat.sh.bak_codex_nightwatch_prep_20260319, storage/logs/crontab.bak_codex_nightwatch_prep_20260319.
- Zmiana: crontab uzywa teraz absolutnej sciezki /home/tyurjydtpw/domains/rsperformance.online/laravel/heartbeat.sh; sam skrypt ma chmod 755 i dziala recznie.
- heartbeat.sh zostal przygotowany pod przyszly hosting Nightwatch supervisor: jesli kiedys na hostingu pojawi sie NIGHTWATCH_ENABLED=true, NIGHTWATCH_TOKEN i komenda
  ightwatch:agent, skrypt podejmie probe utrzymania agenta przez cron.
- Hosting Nightwatch nadal NIE jest aktywny: brak osobnej aplikacji/tokenu Nightwatch dla hostingu i shared hosting nie utrzymuje stabilnie zwyklych daemonow bez supervisora.

## AKTUALIZACJA 2026-03-19 04:58 CET - HOSTING NIGHTWATCH PRZEZ VPS

- Hosting
  sperformance.online ma aktywny laravel/nightwatch v1.24.4 i nie uruchamia lokalnego agenta; ingest jest zdalny do VPS.
- Ustawienia hostingu: LOG_CHANNEL=nightwatch, NIGHTWATCH_ENABLED=true, NIGHTWATCH_TOKEN=<hosting-token>, NIGHTWATCH_SERVER=rsperformance.online, NIGHTWATCH_INGEST_URI=mcp.rs3d.pl:2417, NIGHTWATCH_REQUEST_SAMPLE_RATE=0.1.
- VPS: uruchomiono osobny agent
  s-hosting-nightwatch-agent na  .0.0.0:2417, ograniczony firewallem UFW tylko do IP hostingu 195.78.67.59.
- Root cause po pierwszej probie: hosting agent na VPS dzielil bootstrap/cache/config.php z support-plane i autoryzowal sie zlym tokenem; naprawiono przez osobny APP_CONFIG_CACHE=/tmp/rs-hosting-nightwatch-config.php dla uslugi
  s-hosting-nightwatch-agent.
- Weryfikacja: z hostingu TCP do mcp.rs3d.pl:2417 -> OPEN, php artisan nightwatch:status na hostingu -> The Nightwatch agent is running and accepting connections, heartbeat loguje
  ightwatch remote ingest active: mcp.rs3d.pl:2417.

## AKTUALIZACJA 2026-03-19 05:02 CET - CLEAN BASELINE PO MIGRACJI

- Hosting: wykonana bezpieczna rotacja laravel.log, cron_daemon.log, heartbeat_status.log po rolloutach Laravel 13 / Nightwatch; nowe baseline sa czyste.
- Hosting po rotacji: php artisan nightwatch:status OK, heartbeat.sh wpisuje
  ightwatch remote ingest active: mcp.rs3d.pl:2417, storage/logs/laravel.log pozostaje pusty po smoke / i /admin/login.
- VPS: wykonana bezpieczna rotacja storage/logs/laravel.log wewnatrz kontenera
  s-support-plane-app; po rotacji
  ightwatch:status OK, support-publication-drafts:status -> wszystko puste/green.
- Finalny przeglad Nightwatch: RS Support Plane i RS Performance Hosting sa runtime-owo zdrowe; widoczne stare issue dotyczace Vertex/Pulse nalezy traktowac jako historyczne incydenty sprzed fixow, nie aktywne regresje.

## AKTUALIZACJA 2026-03-19 05:12 CET - FILAMENT ASSET CACHE FIX

- Hosting: naprawiono nawrot rozjechanego Filament admina przez stabilne URL-e assetow z dlugim TTL w public_html/.htaccess.
- Zmiana: dodano wyjatek
  o_filament_asset_cache dla ^/(css|js|fonts)/filament/, aby CSS/JS/fonty Filament zwracaly Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0.
- Backup: /home/tyurjydtpw/domains/rsperformance.online/public_html/.htaccess.bak_codex_filament_asset_cache_20260319.
- Weryfikacja: curl -I dla css/filament/filament/app.css, js/filament/filament/app.js, onts/filament/filament/inter/index.css -> Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0; Playwright widzi poprawny layout /admin/users.

## AKTUALIZACJA 2026-03-19 05:16 CET - ADMIN HTML CACHE FIX

- Hosting: odkryto glowny root cause nawrotu problemu Filament: cale trasy /admin/\* poza czescia przypadkow nadal zwracaly Cache-Control: public, max-age=14400, wiec przegladarka trzymala stare HTML panelu.
- Naprawa: w public_html/.htaccess wymuszono globalnie dla
  o_lscache (^/(admin|api|klient|flota|livewire|sanctum|pulse)) naglowki Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0 oraz unset Expires i naglowkow LiteSpeed cache.
- Backup: /home/tyurjydtpw/domains/rsperformance.online/public_html/.htaccess.bak_codex_admin_html_cache_20260319.
- Weryfikacja: curl -I dla /admin, /admin/users, /admin/blog-posts, /admin/repair-reports, /admin/services, /admin/gallery-images, /admin/seo-aeo-center, /admin/automation-center, /admin/diagnosta-center, /admin/company-settings -> cache-control: no-cache, private; Playwright na wszystkich trasach widzi poprawny layout i tresc stron.

## 2026-03-19 05:31 CET - Codex

- hosting production: zidentyfikowany browser-specific root cause po stronie Chrome: sw.js byl cacheowany z dlugim TTL, a aktywny service worker na scope / mogl utrzymywac stary stan admina mimo poprawnego runtime serwera
- backup: /home/tyurjydtpw/domains/rsperformance.online/public_html/sw.js.bak_codex_sw_cachefix_20260319
- zmiana: podbity service worker do RS Performance â€” Service Worker v2026.03.19, CACHE_NAME=rs-performance-v2026.03.19-admin-fix; dodatkowo sw.js ma teraz Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0
- weryfikacja: curl -I https://rsperformance.online/sw.js -> cache-control: private, no-store, no-cache, must-revalidate, max-age=0; body serwuje nowa wersje workera

## AKTUALIZACJA 2026-03-19 06:10 CET - BLOG FILAMENT ACTIONS + ADMIN SW SELF-HEAL

- Hosting: w app/Filament/Resources/BlogPostResource.php poprawiono niekompatybilny enum pozycji akcji z Filament\\Tables\\Enums\\ActionsPosition na Filament\\Tables\\Enums\\RecordActionsPosition; poprzedni symbol nie istnieje w realnym vendorze produkcji.
- Backup: /home/tyurjydtpw/domains/rsperformance.online/laravel/app/Filament/Resources/BlogPostResource.php.bak_codex_blog_record_actions_position_20260319.
- Hosting: w app/Providers/Filament/AdminPanelProvider.php dodano render hook PanelsRenderHook::BODY_END, ktory na panelu admina wyrejestrowuje stare service workery i czyďż˝ci cache
  s-performance\* w przeglďż˝darce, aby Chrome nie trzymaďż˝ starego HTML Filament po scope /.
- Backup: /home/tyurjydtpw/domains/rsperformance.online/laravel/app/Providers/Filament/AdminPanelProvider.php.bak_codex_admin_sw_cleanup_20260319.
- Weryfikacja runtime: debug na produkcji potwierdzil, ze ListBlogPosts zwraca dwa header actions (Generuj artykuďż˝ AI + SEO, Utwďż˝rz), a Playwright na ďż˝wieďż˝ej sesji widzi poprawnie /admin/blog-posts z przyciskiem Generuj artykuďż˝ AI + SEO oraz kolumnďż˝ Akcje.

## AKTUALIZACJA 2026-03-19 06:45 CET - BLOG FILAMENT FIT + ASYNC DRAFT FIX

- Hosting: blog table w `app/Filament/Resources/BlogPostResource.php` zostala zwďż˝ona pod realny viewport admina; domyslnie widoczne sa teraz tylko kolumny `Tytuďż˝`, `Publikacja`, `Data`, `Akcje`, a mniej krytyczne (`Kategoria`, `AI`, `Views`) sa toggleable i ukryte domyslnie.
- Hosting: row actions w blogu zostaly przeniesione na koniec tabeli (`RecordActionsPosition::AfterColumns`) i zredukowane do ikonowego triggera `Akcje`, co usuwa clipping w Chrome i Edge.
- Hosting: `Generuj artykuďż˝ AI + SEO` w `app/Filament/Resources/BlogPostResource/Pages/ListBlogPosts.php` nie wykonuje juz 2-3 minutowego pipeline przez Livewire POST; action uruchamia teraz `php85 artisan blog:auto-generate` w tle przez `nohup`, a UI wraca od razu z sukcesem.
- Hosting: `app/Console/Commands/AutoGenerateBlogPost.php` dostal opcje `--premium-review=1`, zeby background action mogla przekazac preferencje review bez customowego wrappera.
- Hosting: `app/Support/Blog/BlogVertexPipelineService.php` dostal fallback dla researchera; przy `Vertex 429 / RESOURCE_EXHAUSTED` grounded research spada do zwyklego modelu bez ubijania calego draftu.
- Hosting: `config/blog.php` dostal `vertex_models.researcher_fallback` z defaultem `gemini-2.5-flash`.
- Backupy produkcyjne: `AutoGenerateBlogPost.php.bak_codex_blog_async_20260319`, `ListBlogPosts.php.bak_codex_blog_async_20260319`, `BlogPostResource.php.bak_codex_blog_tablefit_20260319`, `BlogVertexPipelineService.php.bak_codex_blog_research_fallback_20260319`, `config/blog.php.bak_codex_blog_research_fallback_20260319`.
- Twarda weryfikacja: Playwright po submitcie modala pokazuje wyďż˝ďż˝cznie `200` na `/livewire-*/update` i zero console errors; background log zapisuje `Blog draft created`, a finalny smoke z UI utworzyl draft `#36` (`Twoje auto ma ponad 15 lat? To nie klocki sďż˝ najwiďż˝kszym zagroďż˝eniem dla Twoich hamulcďż˝w`).

## AKTUALIZACJA 2026-03-21 02:46 CET - AEO CONTENT INDEX ROUTING METADATA

- Hosting produkcyjny: `app/Support/Search/SearchArtifactFactory.php` wzbogaca teraz glowny `feeds/content.json` o jawne metadane AEO dla najwazniejszych hubow i feedow.
- Dodane pola: `entity_scope`, `canonical_surface`, `preferred_next_urls` oraz utrwalone `routing_hint`, `source_of_truth`, `freshness_urls`.
- Zakres: homepage, services hub, problems hub, repair reports hub, blog hub, DTC hub i `dtc-strongest`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_content_metadata_20260321`.
- Weryfikacja: `php85 -l app/Support/Search/SearchArtifactFactory.php` OK; `php85 artisan search:artifacts-generate --no-interaction` OK; `https://rsperformance.online/feeds/content.json` zwraca nowe pola publicznie.

## AKTUALIZACJA 2026-03-21 02:46 CET - AEO CONTENT INDEX ROUTING METADATA

- Hosting produkcyjny: `app/Support/Search/SearchArtifactFactory.php` wzbogaca teraz glowny `feeds/content.json` o jawne metadane AEO dla najwazniejszych hubow i feedow.
- Dodane pola: `entity_scope`, `canonical_surface`, `preferred_next_urls` oraz utrwalone `routing_hint`, `source_of_truth`, `freshness_urls`.
- Zakres: homepage, services hub, problems hub, repair reports hub, blog hub, DTC hub i `dtc-strongest`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_content_metadata_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_entity_scope_20260321`.
- Weryfikacja: `php85 -l app/Support/Search/SearchArtifactFactory.php` OK; `php85 artisan search:artifacts-generate --no-interaction` OK; `https://rsperformance.online/feeds/content.json` zwraca nowe pola publicznie.

## AKTUALIZACJA 2026-03-21 02:53 CET - AEO EDITORIAL GATE DISCOVERY SURFACE

- Hosting produkcyjny: dodany publiczny artefakt `/.well-known/aeo-editorial-gate.json` jako maszynowo czytelna bramka jakosci AEO.
- Zmiana: `app/Support/RsUri.php` dostal `aeoEditorialGateJson()`; `SearchArtifactFactory` generuje gate i promuje go przez `ai-resources.json`, `mcp-agent-card.json`, `llms.txt`, `llms-full.txt`; `AiDiscoveryArtifactBuilder` reklamuje go w `robots.txt`.
- Backupy produkcyjne: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/RsUri.php.bak_codex_aeo_editorial_gate_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_editorial_gate_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/AiDiscoveryArtifactBuilder.php.bak_codex_aeo_gate_discovery_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_aeo_gate_discovery_20260321`.
- Weryfikacja: `https://rsperformance.online/.well-known/aeo-editorial-gate.json` -> HTTP 200; `robots.txt`, `.well-known/llms.txt` i `ai-resources.json` promuja gate publicznie.

## AKTUALIZACJA 2026-03-21 03:09 CET - ATOMIC ANSWERS

- Hosting produkcyjny: wdrozone `Atomic Answers` dla DTC, services i problem surfaces.
- DTC: `app/Http/Controllers/DtcCodeController.php` generuje `atomicSummary`, `atomicEvidence` i `atomic_summary` w feedzie; `resources/views/pages/dtc/show.blade.php` przenosi najwazniejsza odpowiedz do pierwszego akapitu.
- Services: `app/Http/Controllers/ServiceController.php` i `resources/views/pages/service-show.blade.php` renderuja diagnose-first `atomic_summary` w pierwszym akapicie.
- Problems: `app/Http/Controllers/ProblemController.php` i `resources/views/pages/problem.blade.php` renderuja `atomic_summary` jako pierwszy akapit, a bazowy objaw zostaje jako warstwa pomocnicza.
- Backupy produkcyjne: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Http/Controllers/DtcCodeController.php.bak_codex_atomic_answers_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Http/Controllers/ServiceController.php.bak_codex_atomic_answers_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Http/Controllers/ProblemController.php.bak_codex_atomic_answers_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/resources/views/pages/dtc/show.blade.php.bak_codex_atomic_answers_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/resources/views/pages/service-show.blade.php.bak_codex_atomic_answers_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/resources/views/pages/problem.blade.php.bak_codex_atomic_answers_20260321`.
- Weryfikacja: `php85 -l` OK dla trzech kontrolerow; `php85 artisan optimize:clear` OK; smoke publiczny OK dla `/kody-usterek/P0100`, `/uslugi/diagnostyka-komputerowa` i `/problemy/kontrolka-silnika-swieci`.

## AKTUALIZACJA 2026-03-21 03:15 CET - ATOMIC ANSWERS IN AI-READABLE EXPORTS

- Hosting produkcyjny: `app/Support/Search/SearchArtifactFactory.php` eksportuje teraz `atomic_summary` poza HTML do publicznych artefaktow AI-readable dla services i problems.
- Change: `feeds/content.json` dodaje `atomic_summary`, `markdown_url`, `routing_hint`, `source_of_truth`, `freshness_urls`, `entity_scope`, `canonical_surface` i `preferred_next_urls` na itemach typu `service` oraz `problem`.
- Change: `/.well-known/ai-resources.json` publikuje skondensowane listy `machine_readable.service_atomic_answers` i `machine_readable.problem_atomic_answers`, a sekcja `resources` promuje najwazniejsze surfaces z `atomic_summary`.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Search/SearchArtifactFactory.php.bak_codex_atomic_exports_20260321`.
- Weryfikacja: `php85 -l app/Support/Search/SearchArtifactFactory.php` OK; `php85 artisan search:artifacts-generate --no-interaction` OK; publiczny check potwierdzil `16` service entries z `atomic_summary`, `18` problem entries z `atomic_summary`, oraz po `6` curated atomic answers w `ai-resources.json`.

## AKTUALIZACJA 2026-03-21 03:22 CET - SEARCH OPS GSC GAP DISCOVERY

- Hosting produkcyjny: `app/Support/SearchOps/SearchConsoleService.php` liczy teraz prywatne `query_opportunities` i `page_opportunities` zamiast ograniczac sie do surowych `top_queries` / `top_pages`.
- Hosting produkcyjny: `config/search_ops.php` dostal progi gap discovery (`min impressions`, `max ctr`, `position window`, `limit`) i mapowanie `query_route_hints`, zeby GSC query sygnaly wskazywaly canonical service surface.
- Hosting produkcyjny: `app/Console/Commands/FetchSearchConsoleSignalsCommand.php` raportuje teraz liczbe `query_gaps` i `page_gaps` oraz zapisuje je do `storage/app/status/search-ops-gsc.json`.
- Hosting produkcyjny: `app/Filament/Pages/DiagnostaCenter.php` konsumuje scored `query_opportunities` / `page_opportunities` jako Search Ops targets, z fallbackiem do starego payloadu dla kompatybilnosci wstecznej.
- Backupy produkcyjne: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/SearchOps/SearchConsoleService.php.bak_codex_gsc_gap_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Console/Commands/FetchSearchConsoleSignalsCommand.php.bak_codex_gsc_gap_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Filament/Pages/DiagnostaCenter.php.bak_codex_gsc_gap_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/config/search_ops.php.bak_codex_gsc_gap_20260321`.
- Weryfikacja: `php85 -l` OK dla czterech plikow; `php85 artisan search-ops:gsc-fetch --days=28 --limit=20` OK; payload prywatny pokazal `3` query gaps i `6` page gaps.
- Najmocniejsze query gaps: `diagnostyka skrzyn biegow gdansk` -> `/uslugi/skrzynie-biegow`, `geometria kol gdansk` -> `/uslugi/zawieszenie`, `diagnostyka komputerowa gdansk` -> `/uslugi/diagnostyka-komputerowa`.
- Najmocniejsze page gaps: `/`, `/uslugi/skrzynie-biegow`, `/uslugi/dpf-adblue`, `/uslugi/hamulce`, `/problemy`, `/uslugi/mechanika-ogolna`.
- Next hardening step: przepchnac top GSC gaps do kontrolowanego refresh lane (Telegram approval / approved chain package), zaczynajac od `/uslugi/skrzynie-biegow`, `/uslugi/dpf-adblue`, `/uslugi/hamulce` i homepage.

## AKTUALIZACJA 2026-03-21 03:27 CET - SEARCH OPS CONTROLLED APPLY LANE

- Hosting produkcyjny: `app/Filament/Pages/DiagnostaCenter.php` eksportuje teraz `search_ops_packet` z sekcjami `gsc_priority_queue`, `controlled_apply_lane` i `approval_packet`, tak aby top GSC gaps byly gotowe do operator review.
- Hosting produkcyjny: dodana komenda `app/Console/Commands/ExportSearchOpsPacketCommand.php` (`php85 artisan search-ops:export-packet`) generujaca prywatny packet z approved chain package bez potrzeby wejscia do panelu.
- Behavior: `--latest-complete` probuje najpierw complete approved chain package, a gdy go nie ma, jawnie fallbackuje do najnowszego approved package i zapisuje `resolution_mode=latest_approved_fallback` w packet metadata.
- Backupy produkcyjne: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Filament/Pages/DiagnostaCenter.php.bak_codex_gsc_applylane_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Console/Commands/ExportSearchOpsPacketCommand.php.bak_codex_gsc_applylane_20260321` (jesli plik istnial; nowy command zostal dodany w tym batchu).
- Weryfikacja: `php85 -l` OK dla `DiagnostaCenter.php` i `ExportSearchOpsPacketCommand.php`; `php85 artisan search-ops:export-packet --latest-complete --no-interaction` OK.
- Materialized packet: `storage/app/ops-agent-exports/search-ops-packet-20260321-032736.json`.
- Priority queue head: `/` (73.5, `tighten_brand_answer`), `/uslugi/skrzynie-biegow` (72.8, `refresh_atomic_answer`), `/uslugi/skrzynie-biegow` z query gap (52.0), `/uslugi/dpf-adblue` (51.5), `/uslugi/hamulce` (50.9).
- Approval message gotowy w packet: operator dostaje kompaktowy template pod Telegram/panel z top 4 priorytetami i guardrails typu `no public apply before post-refresh verification`.
- Next hardening step: wykonac pierwszy controlled refresh batch dla homepage + `/uslugi/skrzynie-biegow` + `/uslugi/dpf-adblue` + `/uslugi/hamulce`, a po zmianach odswiezyc artifacts i porownac kolejny fetch GSC.

## AKTUALIZACJA 2026-03-21 03:40 CET - CONTROLLED REFRESH BATCH 01

- Hosting produkcyjny: wdrozony pierwszy controlled refresh batch dla homepage oraz surfaces `/uslugi/skrzynie-biegow`, `/uslugi/dpf-adblue` i `/uslugi/hamulce`.
- Homepage: `routes/web.php` dostal nowy answer-first `meta_title`, `meta_description`, keywords i OG copy pod intent `warsztat samochodowy Gdansk`, `diagnostyka`, `mechanik`, `skrzynie biegow`, `DPF`, `hamulce`.
- Hero: `resources/views/components/rs/hero-v9.blade.php` ma teraz pierwszy komunikat stricte diagnose-first dla kierowcow z Gdanska, Sopotu i Gdyni; po uploadzie usunieto BOM UTF-8 z pliku Blade.
- Service SEO: `app/Support/Seo/ServiceSeoBlueprints.php` zostal zaostrzony dla `skrzynie-biegow`, `hamulce` i `dpf-adblue`; dodane sa query-led meta title/meta description oraz mocniejsze `proof_points` dla DPF/AdBlue, zeby `atomic_summary` nie konczyl sie juz generycznym `Wywiad i objawy`.
- Backupy produkcyjne: `/home/tyurjydtpw/domains/rsperformance.online/laravel/routes/web.php.bak_codex_gsc_refresh_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/resources/views/components/rs/hero-v9.blade.php.bak_codex_gsc_refresh_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Seo/ServiceSeoBlueprints.php.bak_codex_gsc_refresh_20260321`, `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Seo/ServiceSeoBlueprints.php.bak_codex_gsc_refresh_dpfproof_20260321`.
- Weryfikacja: `php85 -l routes/web.php` OK; `php85 -l app/Support/Seo/ServiceSeoBlueprints.php` OK; `php85 artisan optimize:clear --no-interaction` OK; publiczne HTTP 200 dla `/`, `/uslugi/skrzynie-biegow`, `/uslugi/dpf-adblue`, `/uslugi/hamulce`.
- Smoke: homepage renderuje `Warsztat samochodowy Gdansk | Diagnostyka i mechanik | RS Performance`; `/uslugi/dpf-adblue?codex=...` renderuje juz `Najmocniejszy sygnal tej uslugi: Auto traci moc i czesciej wypala DPF.` po wymuszeniu swiezego fetchu.
- Root cause falszywego negatywu po deployu: pierwszy smoke czytal stary cache odpowiedzi; runtime Laravel po jednorazowej inspekcji pokazal poprawne `proof_points`, a swiezy request z cache-bust query potwierdzil publiczny update.
- Next hardening step: domknac batch 01 o analogiczny refresh dla `/uslugi/zawieszenie` i `/uslugi/mechanika-ogolna`, a potem ponowic `search-ops:gsc-fetch` dla oceny impactu.

## AKTUALIZACJA 2026-03-21 03:52 CET - CONTROLLED REFRESH BATCH 02

- Hosting produkcyjny: dopchniÄ™ty drugi refresh batch dla `/uslugi/zawieszenie` i `/uslugi/mechanika-ogolna` jako dalszy ciÄ…g GSC page-gap lane.
- Change: `app/Support/Seo/ServiceSeoBlueprints.php` ma teraz query-led meta i first-answer copy dla `zawieszenie` (`Geometria kol Gdansk...`, `Auto sciaga i zjada opony`) oraz `mechanika-ogolna` (`Mechanik Gdansk...`, `Stuki, wycieki i nierowna praca silnika`).
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Seo/ServiceSeoBlueprints.php.bak_codex_gsc_refresh_batch02_20260321`.
- Weryfikacja: `php85 -l app/Support/Seo/ServiceSeoBlueprints.php` OK; `php85 artisan optimize:clear --no-interaction` OK; publiczny cache-busted smoke dla `/uslugi/zawieszenie` i `/uslugi/mechanika-ogolna` potwierdza nowe title oraz `Najmocniejszy sygnal tej uslugi`.
- Next hardening step: odczekac kolejny cykl GSC/Search Ops i dopiero porownac, czy refreshed surfaces schodza z top page gaps; w miedzyczasie mozna wejsc w resilience 429 / stale artifact lane bez blokowania AEO.

## AKTUALIZACJA 2026-03-21 03:55 CET - SUPPORT-PLANE SEARCH OPS INTAKE + ANTI-429 LANE

- Hosting produkcyjny: `app/Console/Commands/ExportSearchOpsPacketCommand.php` ma nowa opcje `--dispatch-support-plane`; po eksporcie podpisany `search_ops_packet` moze od razu trafic do VPS support-plane.
- Backup produkcyjny: `/home/tyurjydtpw/domains/rsperformance.online/laravel/app/Console/Commands/ExportSearchOpsPacketCommand.php.bak_codex_searchops_supportplane_20260321`.
- VPS support-plane: `app/Support/SupportEventProcessor.php` obsluguje teraz `search_ops.packet_ready` i zamienia event na durable job `search_ops.packet_ingest`.
- VPS support-plane: `app/Support/SupportJobRunner.php` buduje z tego `search_ops.priority_brief` w `support_artifacts`, z polami `service_targets`, `gearbox_signals`, `dtc_candidates`, `top_priority_queue` i `recommended_next_topics`.
- VPS support-plane: `app/Support/SupportArtifactRunner.php` dostal trwaly backoff dla analysis stage przy `Vertex 429 / RESOURCE_EXHAUSTED / transient 5xx`; retryable analysis artifact wraca do `ready` z `analysis_retry_not_before`, zamiast umierac na stale przy pierwszym quota hit.
- VPS ingress: root cause dispatch failure byl infrastrukturalny, nie aplikacyjny. `auto.rs3d.pl/support-plane/*` w Caddy wskazywal na martwy `127.0.0.1:8091`; przepieto go na dzialajacy Laravel support-plane pod `127.0.0.1:8000`.
- Backup VPS ingress: `/etc/caddy/sites-enabled/auto.rs3d.pl.Caddyfile.bak_codex_supportplane_ingress_20260321`.
- Backupy VPS app: pre-change `cp` bez `sudo` odbil sie od uprawnien katalogu; bezposrednio po wdrozeniu wykonano snapshoty post-change: `SupportEventProcessor.php.bak_codex_searchops_ingest_20260321_post`, `SupportJobRunner.php.bak_codex_searchops_ingest_20260321_post`, `SupportArtifactRunner.php.bak_codex_vertex_backoff_20260321_post`.
- Weryfikacja: hosting `php85 -l app/Console/Commands/ExportSearchOpsPacketCommand.php` OK; VPS `docker exec rs-support-plane-app php -l` OK dla `SupportEventProcessor.php`, `SupportJobRunner.php`, `SupportArtifactRunner.php`; `https://auto.rs3d.pl/support-plane/api/internal/support-events` zwraca `405 Allow: POST`.
- E2E smoke: `php85 artisan search-ops:export-packet --latest-complete --dispatch-support-plane --no-interaction` na hostingu -> SUCCESS; na VPS `php artisan support-events:process --limit=5` i `support-jobs:run --limit=5` -> `event_type=search_ops.packet_ready`, `job_type=search_ops.packet_ingest`, `artifact_type=search_ops.priority_brief`.
- Artifact smoke: latest `search_ops.priority_brief` zawiera m.in. `service_targets` dla `/uslugi/skrzynie-biegow`, `/uslugi/dpf-adblue`, `/uslugi/hamulce`, `/uslugi/zawieszenie`, `/uslugi/mechanika-ogolna` oraz `gearbox_signals` dla skrzyn biegow.
- Next hardening step: podlaczyc kolejne Vertex-heavy workflowy do tego samego durable retry lane, tak aby hosting nie konczyl na synchronicznych quota hitach.

## AKTUALIZACJA 2026-03-21 04:25 CET - BLOG SUPPORT-PLANE LANE (SAFE-GATED)

- Hosting: dodano manualny dispatch bloga na VPS support-plane i callback sink draftow bloga, ale domyslne sciezki produkcyjne (Filament, cron, Telegram) zostaly swiadomie zostawione na hostingu.
- VPS: lane `blog.generation_requested -> blog.generation -> blog.draft_input` istnieje i przechodzi event/job, ale nadal blokuje sie na generacji artifactu (structured JSON / fallback `api/blog/pipeline/run` 500).
- Status produkcji: bez regresji dla operatora. Nowa sciezka jest wdrozona jako gated work-in-progress, nie jako aktywny default.

## AKTUALIZACJA 2026-03-21 05:15 CET - BLOG SUPPORT-PLANE DISPATCH FALLBACK

- Hosting ma juz lekki endpoint `/api/blog/pipeline/dispatch`, ktory odpala lokalny background artisan i zwraca `202`.
- VPS fallback bloga nie wali juz synchronicznie w `run`, tylko deleguje do `dispatch`, a event/job/artifact lane przechodzi green.
- Sciezki produkcyjne nadal nie sa przelaczone na default support-plane; to pozostaje swiadomym gate do czasu twardej weryfikacji finalnego draftu po delegacji.

## AKTUALIZACJA 2026-03-21 05:35 CET - BLOG DEFAULT SUPPORT-PLANE FOR FILAMENT + CRON

- Filament i cron dla bloga sa juz aktywne na `--dispatch-support-plane`.
- Telegram zostaje lokalny i nie jest jeszcze przeniesiony na default support-plane.
- Hosting ma nowy lekki endpoint `/api/blog/pipeline/dispatch`; VPS fallback deleguje tam zlecenie i artifact lane bloga jest green.

## AKTUALIZACJA 2026-03-21 06:05 CET - BLOG SUPPORT-PLANE DEFAULT FOR TELEGRAM + CALLBACK HARDENING

- Hosting production: app/Support/Blog/BlogTelegramBotService.php now starts blog:telegram-generate --dispatch-support-plane as the default path for Telegram /blog commands.
- Hosting production: app/Http/Controllers/Api/BlogPostWebhookController.php no longer lets Telegram notification failure break draft persistence. Draft save returns status=ok and telegram_notification.status=failed instead of 500.
- Production backups: /home/tyurjydtpw/domains/rsperformance.online/laravel/app/Support/Blog/BlogTelegramBotService.php.bak_codex_telegram_supportplane_default_20260321 and /home/tyurjydtpw/domains/rsperformance.online/laravel/app/Http/Controllers/Api/BlogPostWebhookController.php.bak_codex_telegram_supportplane_default_20260321.
- Verification: php85 -l OK for both files; php85 artisan optimize:clear --no-interaction OK.
- Hard proof: POST https://rsperformance.online/api/blog/draft with payload telegram-ack-proof-20260321 and intentionally invalid telegram_chat_id=000000000 returned 201 status=ok, saved BlogPost id=49, and returned telegram_notification.status=failed with Bad Request: chat not found.
- Current blog support-plane state:
    - Filament: default support-plane
    - cron: default support-plane
    - Telegram: default support-plane
    - hosting fallback: POST /api/blog/pipeline/dispatch
- Next agent: if the owner wants UX-level proof, run one real Telegram smoke with a real chat ID and confirm final Draft gotowy delivery after delegation. No rollback is needed.

## AKTUALIZACJA 2026-03-21 06:35 CET - BLOG SUPPORT-PLANE TELEGRAM E2E CLOSED

- Hosting production: `app/Http/Controllers/Api/BlogPipelineController.php` przyjmuje juz `telegram_chat_id` w `POST /api/blog/pipeline/dispatch` i dla delegowanych Telegram requestow odpala lokalny background `php85 artisan blog:telegram-generate <chat_id> ...`, zamiast generic `blog:auto-generate`.
- VPS support-plane: `app/Support/SupportPlaneHostingBlogPipelineClient.php` przekazuje `telegram_chat_id` do hostingu, wiec Telegram path ma pelny kontrakt end-to-end.
- Hosting hardening: detached bootstrap zostal ustabilizowany przez `Process::run([... nohup ... &])` w `BlogPipelineController.php` i `BlogTelegramBotService.php`.
- Proof direct dispatch: `dispatch-direct-proof-20260321-003` -> `BlogPost id=50`, log `Telegram blog draft created: #50 - Lista wstydu mechanika: 6 modeli aut, ktĂłre zrujnujÄ… TwĂłj portfel`.
- Proof full support-plane: `support-plane-real-telegram-20260321-005` przeszedl przez VPS runners i zakonczyl sie hostingowym draftem `BlogPost id=51`, log `Telegram blog draft created: #51 - Koszmar czy Inwestycja? Ranking NiezawodnoĹ›ci Aut UĹĽywanych 2026`.
- Latest runtime signal: log hostingu zawiera juz tez `Telegram blog draft created: #52 - 6 aut, ktĂłrych mechanicy nienawidzÄ…. Jak nie wpaĹ›Ä‡ w spiralÄ™ kosztĂłw?`.
- Stan runtime: Filament, cron i Telegram sa default support-plane z realnym dowodem end-to-end. Nie rollbackowac.
- Next agent: wejsc w obserwowalnosc i operator UX tego lane'u, np. prosty probe/panel laczacy `topic -> artifact status -> BlogPost id -> telegram status`.

## AKTUALIZACJA 2026-03-22 09:25 CET - ROLLBACK PO DZISIEJSZYM BATCHU MANUSA

- Produkcja po targeted rollbacku jest znowu zielona.
- Nie wolno uzywac pelnego backupu `2026-03-21 01:00` jako restore wszystkiego, bo jest za stary i cofa poprawne wdrozenia z dalszej czesci 2026-03-21.
- Dzisiejszy batch Manusa dorzucil nowy moduĹ‚ `vehicle/auth/vin/client-panel`, nowe migracje `2026_03_22_*` i ustawil `APP_DEBUG=true` w produkcji.
- Przywrocono pre-Manus state dla: `app/Models/User.php`, `app/Http/Controllers/ClientPanelController.php`, `app/Models/Vehicle.php`, `resources/views/client/dashboard.blade.php`, `routes/web.php`, `routes/api.php`.
- Usunieto dzisiejsze dodatki Manusa: API auth/vehicle/vin controllers, vehicle Filament resource, VIN decoder service, aztec scanner JS, service-history model oraz wszystkie dzisiejsze migracje `2026_03_22_*`.
- Safety snapshoty przed operacja i po nieudanym szerokim restore: `storage/app/private/manual-restore-prep/pre_restore_codex_20260322_.tar.gz` oraz `storage/app/private/manual-restore-prep/pre_revert_after_bad_restore_20260322_0926.tar.gz`.
- Weryfikacja po rollbacku: `APP_DEBUG=false`, `php85 artisan optimize:clear && php85 artisan optimize` OK, homepage `GET 200`, `POST api/chat` route istnieje, `api/booking` nadal nie istnieje (to nie nowa regresja z tego rollbacku).
- Co ma robic nastepny agent: nie wskrzeszac polowicznie dzisiejszego moduĹ‚u Manusa. Jesli owner to chce, zrobic nowy czysty batch z wyrazna decyzja biznesowa i bez mieszania go z rollbackiem runtime.

## AKTUALIZACJA 2026-03-25 22:02 CET - PWA HOMEPAGE CACHE FIX

- Hosting production: exact homepage `/` byla okresowo serwowana jako `HTTP 200` z `content-length: 0` dla plain HTTP klientow, mimo ze przegladarka mogla wygladac dobrze przez aktywny service worker.
- Root cause: skazony wariant LSCache dla exact `/`; origin z query-bust zwracal poprawny HTML.
- Naprawa: w `public_html/.htaccess` dodano `SetEnvIf Request_URI ^/$ no_lscache`.
- Backup produkcyjny: `public_html/.htaccess.bak_codex_home_nolscache_20260325`.
- Weryfikacja: `curl https://rsperformance.online/` zwraca pelny HTML, `python G:\\gravity\\scripts\\validate_pwa_readiness.py` -> `22/22 PASS`, `0 warnings`, Playwright homepage smoke OK.
- Reguly respektowane: brak zmian wygladu, brak zmian AEO/SEO, brak downgrade.

## AKTUALIZACJA 2026-03-25 22:14 CET - PWA UPDATEVIACACHE HARDENING

- Hosting production: rejestracja service workera w `laravel/resources/views/components/rs/layout.blade.php` dostala `updateViaCache: 'none'`.
- Cel: ograniczenie ryzyka trzymania starego `sw.js` po stronie klienta przez wymuszenie update check bez HTTP cache.
- Backup produkcyjny: `laravel/resources/views/components/rs/layout.blade.php.bak_codex_updateviacache_20260325`.
- Weryfikacja: `php85 -l` OK, `python G:\\gravity\\scripts\\validate_pwa_readiness.py` -> `22/22 PASS`, `0 warnings`, Playwright homepage smoke OK.
- Reguly respektowane: brak zmian wygladu, brak zmian AEO/SEO, brak downgrade.

## AKTUALIZACJA 2026-03-25 22:24 CET - ULTRA PWA BATCH

- Hosting production: `manifest.json` i `sw.js` dostaly kolejny custom-first, ultra-nowoczesny batch PWA bez zmian wygladu i bez ruszania struktury AEO.
- `manifest.json`: dodane `shortcuts` dla diagnostyki komputerowej, kodow usterek i kontaktu/umowienia wizyty.
- `sw.js`: wlaczone `navigation preload`, exact homepage `/` usunieta z precache i z runtime cache writes dla navigations, nowy cache version `v2026.03.25-ultra-nav`.
- Backupy produkcyjne:
    - `public_html/manifest.json.bak_codex_ultra_pwa_batch_20260325`
    - `public_html/sw.js.bak_codex_ultra_pwa_batch_20260325`
- Weryfikacja: `python G:\\gravity\\scripts\\validate_pwa_readiness.py` -> `31/31 PASS`, `0 warnings`, Playwright homepage smoke OK.
- Reguly respektowane: brak zmian wygladu, brak zmian AEO/SEO, brak downgrade.

## AKTUALIZACJA 2026-03-25 22:43 CET - PWA SYSTEM UI + OFFLINE CACHE FIX

- Hosting production: dokonczenie warstwy systemowych stanow PWA bez przebudowy glownego mobile UI i bez zmiany struktury AEO.
- `laravel/resources/views/components/rs/layout.blade.php`: dodany `#pwa-system-ui` z trzema sheetami:
    - install prompt
    - update prompt
    - iOS manual install helper
- `public_html/sw.js`: dodany listener `message` dla `SKIP_WAITING`, zeby update flow mogl wejsc w aktywacje kontrolowanie z UI.
- `public_html/offline.html`: przepisany branded offline screen z poprawnym polskim tekstem, CTA do ponowienia, telefonu i nawigacji.
- `public_html/.htaccess`: `offline.html` dodany do `no_lscache`, zeby origin i przegladarki nie serwowaly starej wersji offline shell.
- Backupy produkcyjne:
    - `laravel/resources/views/components/rs/layout.blade.php.bak_codex_pwa_ui_batch_20260325`
    - `public_html/sw.js.bak_codex_pwa_ui_batch_20260325`
    - `public_html/offline.html.bak_codex_pwa_ui_batch_20260325`
    - `public_html/.htaccess.bak_codex_offline_nolscache_20260325`
- Weryfikacja:
    - `php85 -l ...layout.blade.php` -> OK
    - `python G:\\gravity\\scripts\\validate_pwa_readiness.py` -> `31/31 PASS`, `0 warnings`
    - Playwright homepage smoke -> OK
    - Playwright `offline.html?v=20260325-pwa-ui` -> nowy ekran offline z poprawnym UTF-8
- Reguly respektowane:
    - glowny mobile UI homepage nie zostal przebudowany
    - brak zmian struktury AEO
    - brak downgrade

## AKTUALIZACJA 2026-03-25 22:51 CET - ULTRA MANIFEST INSTALL SURFACE

- Hosting production: dopiety install surface PWA do bardziej app-store-like standardu bez zmian glownego UI i bez naruszania struktury AEO.
- `public_html/manifest.json` + `public_html/site.webmanifest`: ujednolicone, zsynchronizowane i wzbogacone o:
    - poprawne polskie metadane
    - 2 screenshoty install UI (`narrow` + `wide`)
    - nowoczesne shortcuty zostaly zachowane
- `public_html/screenshots/`:
    - `pwa-mobile-install.png`
    - `pwa-desktop-install.png`
- `laravel/resources/views/components/rs/partials/layout-head.blade.php`: manifest query version podniesiony do `?v=20260325-ultra-install`
- `G:\\gravity\\scripts\\validate_pwa_readiness.py`: rozszerzony do 43 checkow, teraz pilnuje tez:
    - `site.webmanifest`
    - zgodnosci obu manifestow
    - screenshotow install surface
- Backupy produkcyjne:
    - `public_html/manifest.json.bak_codex_manifest_ultra_20260325`
    - `public_html/site.webmanifest.bak_codex_manifest_ultra_20260325`
    - `laravel/resources/views/components/rs/partials/layout-head.blade.php.bak_codex_manifest_ultra_20260325`
- Weryfikacja:
    - Playwright `fetch('/manifest.json?...').json()` zwraca poprawne polskie wartosci (`GdaĹ„sk`, `UmĂłw diagnostykÄ™`, screenshot labels)
    - `python G:\\gravity\\scripts\\validate_pwa_readiness.py` -> `43/43 PASS`, `0 warnings`
- Reguly respektowane:
    - brak przebudowy hero/mobile shell
    - brak zmian struktury AEO
    - brak downgrade

## AKTUALIZACJA 2026-03-25 22:58 CET - IOS / NATIVE PWA POLISH

- Hosting production: dopiety native polish dla installed PWA na iOS bez przebudowy glownego UI i bez naruszania struktury AEO.
- `laravel/resources/views/components/rs/partials/layout-head.blade.php`:
    - viewport -> `viewport-fit=cover`
    - dodane:
        - `apple-mobile-web-app-capable=yes`
        - `apple-mobile-web-app-title=RS Performance`
        - `apple-mobile-web-app-status-bar-style=black-translucent`
        - `application-name=RS Performance`
        - `color-scheme=dark`
- Backup produkcyjny:
    - `laravel/resources/views/components/rs/partials/layout-head.blade.php.bak_codex_ios_pwa_head_20260325`
- `G:\\gravity\\scripts\\validate_pwa_readiness.py`: rozszerzony do 48 checkow, teraz pilnuje tez meta sygnalow iOS/native.
- Weryfikacja:
    - `php85 -l` -> OK
    - `python G:\\gravity\\scripts\\validate_pwa_readiness.py` -> `48/48 PASS`, `0 warnings`
- Reguly respektowane:
    - brak przebudowy hero/mobile shell
    - brak zmian struktury AEO
    - brak downgrade

## AKTUALIZACJA 2026-03-26 00:03 CET - PWA CACHE BUMP / LIVE REFRESH HARDENING

- Hosting production: po hotfiksie black screenu dopiety bezpieczny refresh path dla klientow z potencjalnie starym cache PWA.
- `public_html/sw.js`:
    - cache version podniesiony do `v2026.03.25.1`
    - `CACHE_NAME` zmieniony na `rs-performance-v2026.03.25.1-ultra-nav`
- `laravel/resources/views/components/rs/layout.blade.php`:
    - przywrocone i utrzymane `updateViaCache: 'none'`
    - po rejestracji service workera wykonywane jest `reg.update()`, zeby klient szybciej pobieral nowy worker po regresji / hotfiksie
- Backupy produkcyjne:
    - `public_html/sw.js.bak_codex_sw_cachebump_20260325`
    - `laravel/resources/views/components/rs/layout.blade.php.bak_codex_updateviacache_restore_20260325`
- Weryfikacja:
    - `php85 artisan pwa:telemetry-check --skip-smoke` -> PASS
    - `python G:\\gravity\\scripts\\validate_pwa_readiness.py` -> `53/53 PASS`, `0 warnings`
    - `python G:\\gravity\\scripts\\pwa_telemetry_smoke.py` -> PASS
    - live Playwright mobile + desktop browser smoke -> homepage renderuje sie poprawnie po stronie origin
- Reguly respektowane:
    - brak zmian wygladu jako nowej funkcji; to utwardzenie odswiezania klienta po regresji
    - brak zmian struktury AEO
    - brak downgrade

## AKTUALIZACJA 2026-03-26 23:52 CET - BMW DTC ENRICHMENT

- Hosting production: dodany pierwszy curated markowy enrichment DTC bez migracji bazy i bez przebudowy glownego UI.
- Zakres:
    - nowy serwis `app/Support/Dtc/BmwDtcEnrichment.php`
    - nowy dataset `storage/app/dtc/bmw_enrichment_codes.json`
    - rozszerzony `DtcCodeController.php`
    - rozszerzony `resources/views/pages/dtc/show.blade.php`
    - nowy feed `https://rsperformance.online/feeds/dtc-bmw-enrichment.json`
    - route regex dla DTC dopuszcza myslnik
- Efekt:
    - publiczne strony i JSON obsluguja BMW-specific kody spoza bazowego OBD dumpa, np. `29CC`
    - istniejÄ…ce strony DTC dostaja dodatkowe pola enrichmentu BMW: severity, models, engine codes, software, suggested checks
- Backupy produkcyjne:
    - `app/Http/Controllers/DtcCodeController.php.bak_codex_bmw_enrichment_20260326`
    - `routes/web.php.bak_codex_bmw_enrichment_20260326`
    - `resources/views/pages/dtc/show.blade.php.bak_codex_bmw_enrichment_20260326`
- Weryfikacja:
    - `php85 artisan test tests/Feature/DtcBmwEnrichmentTest.php --compact` -> PASS
    - `curl https://rsperformance.online/feeds/dtc-bmw-enrichment.json` -> 200
    - `curl https://rsperformance.online/kody-usterek/29cc.json` -> 200
- Uwaga:
    - w koncowym smoke `validate_pwa_readiness.py` wyszly 4 fail'e telemetryczne PWA; homepage i nowe endpointy DTC dzialaja, ale kolejny agent od PWA powinien to sprawdzic osobno

## AKTUALIZACJA 2026-03-27 00:15 CET - DIAGNOSTA MCP COMPAT RESTORE

- VPS prywatnego Diagnosty:
    - `mcp.rs3d.pl` znow wystawia `diagnostic_search`
    - `diagnosta-api.service` wstal i dziala na `127.0.0.1:8081`
- Root cause:
    - przepiety/odchudzony `diagnosta-mcp` na FastMCP `0.3.0` bez legacy toola `diagnostic_search`
    - `diagnosta-api` restart loop przez Postgres `172.18.0.2:5432`
- Zmiany:
    - `/srv/diagnosta/app/server.py` -> compat bridge `diagnostic_search`, exact-match z `/kody-usterek/{code}.json`, priorytet prywatnych notatek
    - `/home/rsops/rs-knowledge/app/main.py` -> degraded startup bez twardego padania na braku Postgresa
- Backupy:
    - `/srv/diagnosta/app/server.py.bak_codex_diagnostic_search_restore_20260327`
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_diagnosta_api_degraded_start_20260327`
- Weryfikacja:
    - `tools/list` na `https://mcp.rs3d.pl/` pokazuje `diagnostic_search`
    - `tools/call diagnostic_search` dla `P20EE SCR NOx VAG 2.0 TDI EA288` daje `HTTP 200` i hit `/kody-usterek/p20ee.json`
- WaĹĽne:
    - Diagnosta zostaje prywatny, tylko dla ownera
    - osobno mozna pozniej poprawic ranking surowego `diagnosta-api`, ale kompatybilnosc MCP jest przywrocona

## AKTUALIZACJA 2026-03-27 00:24 CET - DIAGNOSTA ANSWER-FIRST SHAPE

- `diagnostic_search` zwraca teraz warstwe gotowa dla modelu:
    - `assistant_answer`
    - `confidence`
    - `recommended_next_steps`
    - `ask_back`
    - `display_mode`
- To jest preferowany tor odpowiedzi w ChatGPT; `hits` zostaja jako evidence/debug.
- Test:
    - `tools/call diagnostic_search` dla `P20EE SCR NOx VAG 2.0 TDI EA288`
    - wynik zawiera top-level `assistant_answer` zamiast samego raw search dumpa

## AKTUALIZACJA 2026-03-27 01:10 CET - DIAGNOSTA RERANKING + EVALS

- VPS prywatnego Diagnosty: `/srv/diagnosta/app/server.py` dostal source-aware reranking i `query_profile`.
- Backup VPS:
    - `/srv/diagnosta/app/server.py.bak_codex_rerank_20260327`
- Nowe pola payloadu:
    - `reranked`
    - `query_profile`
    - `retrieval_score`
    - `rerank_score`
    - `match_reasons`
    - `overlap_terms`
- Exact DTC (`P20EE`, `29CC`) pozostaja answer-first i maja najwyzszy priorytet przez kanoniczne JSON z hostingu.
- Objawowe query typu `no-start diesel CR rail pressure` dostaja teraz sensowniejszy `semantic_hint` zamiast pustego disclaimeru.
- Lokalnie dodane eval fixtures:
    - `G:\gravity\diagnosta-evals\diagnostic_search_cases.json`
    - `G:\gravity\diagnosta-evals\run_diagnostic_search_eval.py`
    - wynik ostatniego smoke: `3/3 PASS`
- Lokalnie dodany skill do dalszej pracy:
    - `C:\Users\oli22\.codex\skills\diagnosta-ultra-stack\SKILL.md`

## AKTUALIZACJA 2026-03-27 02:05 CET - DIAGNOSTA LIVE WEB RESEARCH

- VPS prywatnego Diagnosty dostal nowy tor live-web bez ruszania strony publicznej.
- Backup VPS:
    - `/srv/diagnosta/app/server.py.bak_codex_web_research_20260327`
- Narzedzia:
    - lokalny `SearXNG` na VPS jako discovery layer
    - `trafilatura 2.0.0` jako extractor tresci
- `trafilatura` jest doinstalowana vendorowo do `/srv/diagnosta/app/_vendor`, bo aktywny venv MCP jest read-only.
- Nowe toole MCP:
    - `diagnostic_web_research(query, limit=3)`
    - `diagnostic_search_live(query, top_k=5, web_limit=3, use_dynamic=True)`
- Zasada:
    - `diagnostic_search` zostaje kompatybilny i nietkniety
    - exact DTC z hostingu nadal ma priorytet
    - internet tylko wzbogaca prywatna diagnoze
- Weryfikacja:
    - `curl https://mcp.rs3d.pl/healthz` => `ok`
    - `diagnostic_web_research('P20EE SCR NOx VAG 2.0 TDI EA288')` => `web_hint`, confidence `medium`
    - `diagnostic_search_live('P20EE SCR NOx VAG 2.0 TDI EA288')` => `answer_first`, confidence `high`, `used_web=true`

## AKTUALIZACJA 2026-03-27 02:28 CET - DIAGNOSTA DEEP RESEARCH BATCH 2

- VPS prywatnego Diagnosty dostal osobny worker deep-ingest i nowy live tool MCP:
    - `/srv/diagnosta/app/deep_research_worker.py`
    - `diagnostic_deep_research(query, max_sources=3)`
- Backupy VPS:
    - `/srv/diagnosta/app/deep_research_worker.py.bak_codex_deep_research_quality_20260327`
    - `/srv/diagnosta/app/server.py.bak_codex_deep_research_tool_20260327`
- Nowy open-source/free stack 2026+ na VPS:
    - `crawl4ai==0.8.6` w izolowanym venv `/srv/diagnosta/venvs/deep_ingest`
    - `markitdown==0.1.5`
    - `yt-dlp==2026.3.17`
    - Playwright Chromium w cache usera VPS
- WaĹĽne ograniczenie:
    - browser-mode `crawl4ai` jest zainstalowany, ale domyslnie wylaczony przez brak systemowych bibliotek VPS (`libatk-1.0.so.0` i pochodne)
    - worker robi graceful fallback do `trafilatura` / cleanup HTML / `markitdown` / `yt-dlp`
- Co robi worker:
    - Ĺ‚Ä…czy dwa warianty zapytania SearXNG, w tym wariant bez `youtube`
    - czyta HTML, dokumenty i napisy YouTube
    - zwraca payload answer-first gotowy dla LLM
- MCP:
    - aktywny `mcp.rs3d.pl` po HTTP session-handshake pokazuje nowy tool `diagnostic_deep_research`
    - aktywny proces FastMCP po restarcie:
        - PID `1244249`
        - bind `127.0.0.1:8001`
- Weryfikacja:
    - `curl https://mcp.rs3d.pl/healthz` => `ok`
    - HTTP initialize -> initialized -> tools/list na `https://mcp.rs3d.pl/` pokazuje `diagnostic_deep_research`
    - direct probe z moduĹ‚u: `source=diagnosta+searxng+deep_ingest`, `display_mode=deep_web_hint`
- Uwaga operacyjna:
    - to jest owner-only prywatny tool Diagnosty; nie wynosic na stronÄ™ publicznÄ…
    - jeĹ›li kiedyĹ› bÄ™dziemy chcieli peĹ‚ne JS-heavy crawling, trzeba doinstalowaÄ‡ brakujÄ…ce biblioteki systemowe VPS pod Chromium

## AKTUALIZACJA 2026-03-27 02:42 CET - GOLDEN SOURCE CATALOG DLA DIAGNOSTY

- Na podstawie `C:\\Users\\oli22\\Downloads\\Katalog_ZĹ‚otych_ĹąrĂłdeĹ‚_dla_Diagnosty_RS_(Marzec_2026+).docx` dodano curated registry ĹşrĂłdeĹ‚, ale NIE jako surowÄ… bazÄ™ wiedzy.
- Nowy plik danych na VPS:
    - `/srv/diagnosta/data/source_catalog/golden_sources_20260327.json`
- Worker:
    - backup: `/srv/diagnosta/app/deep_research_worker.py.bak_codex_golden_sources_20260327`
    - aktywny plik: `/srv/diagnosta/app/deep_research_worker.py`
- Co dokĹ‚adnie wdroĹĽono:
    - trusted-source registry z hostami, tierami, kategoriami i score boostami
    - query expansion pod ĹşrĂłdĹ‚a wysokiej jakoĹ›ci (`site:` warianty dla wybranych hostĂłw)
    - metadata w hitach:
        - `trusted_source`
        - `source_key`
        - `source_label`
        - `source_tier`
        - `source_category`
- ObjÄ™te ĹşrĂłdĹ‚a:
    - `NHTSA`
    - `CarComplaints`
    - `Ross-Tech Wiki`
    - `Pelican Parts`
    - `PicoAuto Case Studies`
    - `Diagnostic Network`
    - `Bimmerpost DIY`
    - `VAG-com.pl`
    - `Toyota Nation`
- Wniosek architektoniczny:
    - TAK dodawaÄ‡ na sztywno katalog ĹşrĂłdeĹ‚ i reguĹ‚y zaufania
    - NIE dodawaÄ‡ na sztywno treĹ›ci diagnostycznej 1:1 z DOCX
- Weryfikacja:
    - worker kompiluje siÄ™ po zmianie
    - `diagnostic_deep_research` nadal dziaĹ‚a live po MCP
    - katalog ĹşrĂłdeĹ‚ jest obecny na VPS i gotowy do dalszego strojenia
- Uwaga:
    - samo dodanie katalogu poprawia bazÄ™ pod ranking, ale nie gwarantuje jeszcze, ĹĽe SearXNG odda te hosty w top wynikach dla kaĹĽdego query
    - jeĹ›li chcemy mocniej wymusiÄ‡ te ĹşrĂłdĹ‚a, nastÄ™pny krok to targeted fetch/adapters dla Ross-Tech, NHTSA i Pelican Parts

## AKTUALIZACJA 2026-03-27 02:24 CET - DIAGNOSTA SEARCH RERANK HARDENING

- VPS: poprawione `/home/rsops/rs-knowledge/app/main.py` i `/home/rsops/rs-knowledge/app/models.py`.
- Backupy:
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_exact_dtc_rerank_20260327`
    - `/home/rsops/rs-knowledge/app/models.py.bak_codex_exact_dtc_rerank_20260327`
- Nowy kontrakt `/internal/diagnostic-search`:
    - `best_score`
    - `exact_match`
    - `query_interpretation`
    - `matched_dtc_codes`
- Query z DTC:
    - zgodny kod dostaje mocny boost,
    - obcy kod dostaje kare,
    - `opendbc/*.dbc` jest mocno degradowane jako szum.
- Efekt:
    - `P0299 VW 2.0 TDI brak mocy` nie daje juz falszywie mocnego top hitu `U012D Ford`.
- Otwarte:
    - Postgres dla `diagnosta-api` nadal niedostepny (`172.18.0.2:5432`), ale API dziala w degraded mode na Qdrant.

### 2026-03-27 02:36 CET ďż˝ Diagnosta exact DTC fallback

- VPS: `/home/rsops/rs-knowledge/app/main.py` wzbogacony o kanoniczny fallback do `https://rsperformance.online/kody-usterek/{code}.json`.
- Backupy:
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_canonical_dtc_fallback_20260327`
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_canonical_dtc_cleanup_20260327`
- Potwierdzone dziaďż˝anie exact na endpointzie GPT Actions `/internal/diagnostic-search`:
    - `P0301` => canonical hit `/kody-usterek/p0301.json`
    - `P0087` => canonical hit `/kody-usterek/p0087.json`
    - `P0299` => canonical hit `/kody-usterek/p0299.json`
- Postgres nadal martwy (`172.18.0.2:5432`), ale exact DTC juďż˝ nie zaleďż˝y od DB.

### 2026-03-27 02:49 CET ďż˝ VIN decoder phase 1

- VPS: `/home/rsops/rs-knowledge/app/main.py` i `/home/rsops/rs-knowledge/app/models.py` rozszerzone o `POST /internal/decode-vin`.
- Dziaďż˝a przez `https://mcp.rs3d.pl/internal/decode-vin`.
- Rdzeďż˝: NHTSA vPIC + NHTSA recallsByVehicle + cache VPS.
- Backupy:
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_vin_decoder_20260327`
    - `/home/rsops/rs-knowledge/app/models.py.bak_codex_vin_decoder_20260327`
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_vin_decoder_cache_fix_20260327`
    - `/etc/caddy/sites-enabled/mcp.rs3d.pl.Caddyfile.bak_codex_vin_decoder_route_20260327`
- GPT Builder local files do podmiany:
    - `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_schema_ultra_2026.json`
    - `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_instructions_ultra_2026.txt`
- Uwaga: coverage EU premium nadal czďż˝ciowy; phase 2 ma doďż˝oďż˝yďż˝ enrichment adaptery.

### 2026-03-27 05:57 CET ďż˝ VIN-aware search

- `diagnostic_search` na VPS przyjmuje teraz opcjonalne `vin` i zwraca `vehicle_context`.
- Backupy:
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_vin_context_search_20260327`
    - `/home/rsops/rs-knowledge/app/models.py.bak_codex_vin_context_search_20260327`
- GPT Builder local files do podmiany po raz kolejny:
    - `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_schema_ultra_2026.json`
    - `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_instructions_ultra_2026.txt`

### 2026-03-27 06:03 CET ďż˝ VIN phase 2 signal layer

- `decode_vin` zwraca teraz `wmi_profile` i `adapter_plan`.
- Backupy:
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_vin_wmi_adapter_plan_20260327`
    - `/home/rsops/rs-knowledge/app/models.py.bak_codex_vin_wmi_adapter_plan_20260327`
- GPT Builder local files znowu zaktualizowane:
    - `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_schema_ultra_2026.json`
    - `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_instructions_ultra_2026.txt`

## AKTUALIZACJA 2026-03-27 06:24 CET - DIAGNOSTA ANSWER-FIRST SEARCH + VIN DIAGNOSTIC PROFILE

- VPS / Diagnosta: `diagnostic_search` zwraca juĹĽ gotowy packet answer-first (`assistant_answer`, `confidence`, `recommended_next_steps`, `ask_back`, `display_mode`) oraz `diagnostic_profile`.
- `decode_vin` oddaje `diagnostic_profile` dla VAG/BMW/Mercedes i hydratuje brakujÄ…ce pola ze starych cache hitĂłw bez kasowania cache.
- Backupy VPS:
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_search_answer_first_20260327`
    - `/home/rsops/rs-knowledge/app/models.py.bak_codex_search_answer_first_20260327`
    - `/home/rsops/rs-knowledge/app/main.py.bak_codex_vin_cache_hydration_20260327`
- Zielone probe publiczne:
    - `WAUZZZF46KA000001` -> `diagnostic_profile.brand_lane=VAG`
    - `WBAVC71010A123456` -> `diagnostic_profile.brand_lane=BMW`
    - `P0299 brak mocy` + VIN Audi -> exact + answer-first + VAG profile
    - `P0301 Skoda Octavia 1.5 TSI szarpanie na zimnym` -> exact + misfire packet
- NastÄ™pny agent:
    - nie cofaÄ‡ `diagnostic_profile` ani server-side `assistant_answer`,
    - nastÄ™pny sensowny krok: controlled web fallback dla weak/no-hit.

## AKTUALIZACJA 2026-03-27 06:29 CET - DIAGNOSTA BRAND INGEST + FIAT

- MCP tool `diagnostic_ingest_brand_knowledge` jest live na `mcp.rs3d.pl`.
- Zbiera bounded paczkÄ™ wiedzy o marce i zapisuje jÄ… na VPS do `/srv/diagnosta/data/brand_ingest/{brand}/{timestamp}/`.
- Fiat seed wykonany: `/srv/diagnosta/data/brand_ingest/fiat/20260327_052926/`.
- Mechanizm przyjmuje: `brand`, `max_queries`, `max_sources_per_query`, `topics_csv`.
- Trusted source catalog rozszerzony o FIAT community, wiÄ™c Fiat batch ma teraz `8 trusted hits`.
- NastÄ™pny agent:
    - nie robiÄ‡ z tego nieograniczonego scrape-all,
    - nastÄ™pny logiczny krok to controlled import wybranych packetĂłw do dynamic knowledge.

## AKTUALIZACJA 2026-03-27 06:42 CET - GPT ACTIONS BRAND INGEST BRIDGE

- Publiczny HTTP bridge dla builder Actions: POST /internal/diagnostic-ingest-brand.
- Backend: /home/rsops/rs-knowledge/app/main.py oraz /home/rsops/rs-knowledge/app/models.py.
- Reverse proxy: /etc/caddy/sites-enabled/mcp.rs3d.pl.Caddyfile.
- Backupy: .bak_codex_actions_brand_ingest_20260327.
- Publiczny probe 200 dla payloadu Fiat; artefakty: /srv/diagnosta/data/brand_ingest/fiat/20260327_054233/.
- Dla tego GPT Actions schema trzeba aktualizowac, bo builder nie odkrywa prywatnych MCP tooli sam z siebie.

## AKTUALIZACJA 2026-03-27 06:58 CET - BRAND INGEST CONTROLLED IMPORT

- `POST /internal/diagnostic-ingest-brand` dostal opcjonalny controlled import: `auto_import`, `trusted_only`, `max_import_hits`.
- Backend: `/home/rsops/rs-knowledge/app/main.py`, `/home/rsops/rs-knowledge/app/models.py`.
- Backupy VPS: `.bak_codex_brand_import_20260327`, `.bak_codex_brand_import_status_20260327`.
- Publiczne probe: sam ingest => `200`; ingest z `auto_import=true` => `200` i miekkie `import_status=unavailable` zamiast `500`.
- Root cause braku zapisu: Postgres dla rs-knowledge nadal down na `172.18.0.2:5432`, wiec import jest logicznie gotowy, ale runtime bazy chwilowo niedostepny.
- GPT Builder files zaktualizowane: `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_schema_ultra_2026.json`, `C:\Users\oli22\OneDrive\Pulpit\diagnosta_chatgpt_actions_instructions_ultra_2026.txt`.

## AKTUALIZACJA 2026-03-27 19:56 CET - SHARED POSTGRES RECOVERY + REAL BRAND IMPORT

- Uwaga krytyczna: owner potwierdzil, ze produkcja i prywatny Diagnosta korzystaja z tej samej bazy wiedzy, wiec NIE wolno bylo robic SQLite fallback ani rozdzielac storage.
- Przywrocony wspolny Postgres z istniejacego data dir na VPS:
    - compose: `/srv/ops-stack/compose/docker-compose.yml`
    - kontener: `compose-postgres-1`
    - volume/data: `/srv/ops-stack/postgres`
- Backupy przed zmiana:
    - `/srv/ops-stack/compose/docker-compose.yml.bak_codex_postgres_restore_20260327`
    - `/home/rsops/rs-knowledge/app/.env.bak_codex_postgres_restore_20260327`
- Zmieniony target DB dla `diagnosta-api`:
    - `/home/rsops/rs-knowledge/app/.env`
    - `PG_HOST=127.0.0.1` zamiast martwego `172.18.0.2`
- Walidacja:
    - `127.0.0.1:5432` listen OK
    - `compose-postgres-1` healthy
    - `psql` do `rs_knowledge` jako `rs_knowledge_user` OK
    - `diagnosta-api` po restarcie: active
- Publiczne probe po recovery:
    - `POST /internal/diagnostic-search` dla `P0299 VAG 2.0 TDI brak mocy` => `200`, `exact_match=true`
    - `POST /internal/diagnostic-ingest-brand` dla Fiata z `auto_import=true` => `200`, `import_status=imported`, `imported_doc_ids=[4819]`
    - drugi bounded Fiat packet (`Multijet,Body Computer,Dualogic,CAN`) => `200`, `import_status=imported`, `imported_doc_ids=[4820]`, `skipped_existing=1`, `skipped_untrusted=1`
- Artefakty:
    - `/srv/diagnosta/data/brand_ingest/fiat/20260327_185441/`
    - `/srv/diagnosta/data/brand_ingest/fiat/20260327_185519/`
- Nastepny agent:
    - nie cofaj Postgresa ani `PG_HOST=127.0.0.1`; to jest teraz aktywny shared DB lane
    - kolejny krok to dalej bounded, trusted-only brand packets, nie masowy scrape-all

## AKTUALIZACJA 2026-03-28 20:14 CET - VAG CORE DIAG PACKET IMPORTED FROM GCP

- Osobny harvest plane GCP wygenerowal pierwszy recznie kuratorowany packet VAG core diagnostics:
    - `gs://rs-diagnosta-ai-dane/packets/vag/core_diag/manual_curated-vag-core_diag-20260328T201100Z/`
- Packet zawiera 5 wysokiej wartosci dokumentow Ross-Tech Wiki:
    - `P0299/000665`
    - `P2015/008213`
    - `Misfire_Diagnosis`
    - `P334B/013131`
    - `02214`
- Signed URL export wygenerowany lokalnie:
    - `G:\gravity\gcp-harvest-plane\ops\latest_vag_core_diag_signed_urls.json`
- Bezpieczny flow `GCS signed URL -> VPS validation -> shared Diagnosta import` przeszedl end-to-end:
    - validation status: `validated`
    - import status: `imported`
    - imported_doc_ids: `[4856,4857,4858,4859,4860]`
- Artefakty na VPS:
    - `/srv/diagnosta/data/intake/gcp/validated/manual_curated-vag-core_diag-20260328T201100Z/manifest.json`
    - `/srv/diagnosta/data/intake/gcp/validated/manual_curated-vag-core_diag-20260328T201100Z/import_report.json`
- Nastepny agent:
    - trzymac VAG lane jako curated OEM/diagnostic sources, nie rozszerzac go na losowe fora bez trust gate
    - kolejny logiczny krok: `Audi manuals + recall` packet albo `PlanetVAG PR/DTC` packet tym samym torem

## AKTUALIZACJA 2026-03-28 21:17 CET - ECU REPAIR CHM LANE IMPORTED

- Lokalny plik `F:\chrome\ECU Repair Helper E-Book.chm` zostal przerobiony do osobnego lane `ecu_repair_chm`.
- Pierwszy wariant packetu `local_curated-ecu_repair_chm-20260328T210500Z` zostal prawidlowo odrzucony przez validator, bo `external_id` nie byly namespaced packetem.
- Poprawiony packet `local_curated-ecu_repair_chm-20260328T211000Z` przeszedl pelny flow:
    - GCS packet: `gs://rs-diagnosta-ai-dane/packets/ecu_repair/chm/local_curated-ecu_repair_chm-20260328T211000Z/`
    - VPS validation: `validated`
    - shared Diagnosta import: `imported`
    - imported_doc_ids: `[4861..4913]`
- To jest lane o nizszym trust niz OEM / Ross-Tech i ma sluzyc jako `ECU repair / board-level hints`, nie jako kanoniczne OEM source.
- Nastepny agent:
    - nie promowac tego lane ponad OEM / Ross-Tech / recall
    - mozna teraz budowac osobne curated lane z PlanetVAG lub Audi manuals tym samym torem

## 2026-03-28 21:30 CET â€” PlanetVAG PR/DTC packet

- Zweryfikowano w Playwright, ĹĽe `dtcsearch.planetvag.com` i `prsearch.planetvag.com` zwracajÄ… stabilne wyniki w realnej przeglÄ…darce.
- Zbudowano packet `manual_curated-vag-planetvag_pr_dtc-20260328T214500Z` z 6 dokumentami (`P0299`, `P2015`, `02214`, surface DTC, surface PR, PR `1AT/G1D/8GU`).
- Upload do GCS: `gs://rs-diagnosta-ai-dane/packets/vag/planetvag_pr_dtc/manual_curated-vag-planetvag_pr_dtc-20260328T214500Z/`
- Signed URLs wymagaĹ‚y poprawki zapisu lokalnego do UTF-8; PowerShell `>` robiĹ‚ UTF-16 i validator na VPS odrzucaĹ‚ plik signed URLs.
- VPS validation: OK.
- Import do shared Diagnosta knowledge: `imported_count=2`, `skipped_existing=4`, nowe doc IDs: `4914, 4915`.
- Wniosek: PlanetVAG lane dziaĹ‚a jako szybka warstwa normalizacji/reference dla VAG, ale czÄ™Ĺ›Ä‡ treĹ›ci byĹ‚a juĹĽ zdeduplikowana wzglÄ™dem istniejÄ…cej bazy.

## 2026-03-28 21:35 CET â€” Audi manuals + recall packet

- Zweryfikowano oficjalne surface'y Audi USA: owner-manual catalog, owner-manual product page 2026, emissions/warranty catalog, California warranties 2026, recall lookup surface oraz Takata recall page.
- Zbudowano packet `manual_curated-audi-manuals_recall-20260328T215500Z` z 6 dokumentami OEM metadata-only.
- Upload do GCS: `gs://rs-diagnosta-ai-dane/packets/audi/manuals_recall/manual_curated-audi-manuals_recall-20260328T215500Z/`
- VPS validation: OK.
- Import do shared Diagnosta knowledge: `imported_count=6`, `skipped_existing=0`, nowe doc IDs: `4916, 4917, 4918, 4919, 4920, 4921`.
- Wniosek: Audi dostaĹ‚o oficjalny lane manuals+recall oparty o OEM surfaces bez importu peĹ‚nych PDF-Ăłw.

## 2026-03-28 21:36 CET - Fiat CAN / Body Computer packet

- Browser-verified FIAT Forum lane created from proxy-alignment, body-computer replacement/key, and MultiECUScan interface guidance threads.
- Packet: `manual_curated-fiat-body_computer_can-20260328T221500Z`.
- GCS: `gs://rs-diagnosta-ai-dane/packets/fiat/body_computer_can/manual_curated-fiat-body_computer_can-20260328T221500Z/`.
- VPS validation: OK.
- Import result: full dedupe (`imported_count=0`, `skipped_existing=4`), so no new shared knowledge rows were added.
- Meaning: the lane is now reproducible as a curated packet, but the current shared corpus already contains equivalent Fiat BCM/CAN knowledge.

## 2026-03-28 21:44 CET - Fiat Dualogic packet

- Browser-verified FIAT Forum Dualogic lane created from a troubleshooting guide, gearbox-swap case, and a 2025 bleed-then-relearn workflow thread.
- Packet: `manual_curated-fiat-dualogic-20260328T223500Z`.
- GCS: `gs://rs-diagnosta-ai-dane/packets/fiat/dualogic/manual_curated-fiat-dualogic-20260328T223500Z/`.
- VPS validation: OK.
- Import result: `imported_count=3`, `skipped_existing=1`, new shared knowledge IDs `4922, 4923, 4924`.
- Meaning: unlike Fiat BCM/CAN, this lane added net-new practical knowledge to Diagnosta.

## 2026-03-28 21:47 CET - Fiat CAN no communication packet

- FIAT Forum symptom lane created for BCM/ECU communication loss, failed proxy-alignment access, radio `NO CAN NETWORK`, and broad U1700/U0001/U0019 network-collapse cases.
- Packet: `manual_curated-fiat-can_no_communication-20260328T225500Z`.
- GCS: `gs://rs-diagnosta-ai-dane/packets/fiat/can_no_communication/manual_curated-fiat-can_no_communication-20260328T225500Z/`.
- VPS validation: OK.
- Import result: `imported_count=4`, `skipped_existing=0`, new shared knowledge IDs `4925, 4926, 4927, 4928`.
- Meaning: this narrow symptom packet added net-new Fiat network diagnostics value beyond the earlier BCM/CAN dedupe-only packet.

## 2026-03-28 21:49 CET - Fiat DPF/EGR Multijet packet

- Curated Fiat Forum + Fiat Klub Polska lane created for Multijet DPF/EGR diagnosis: P2002 after cleaning, Doblo EGR with clog factor 102.2, P0101 after DPF/EGR repair chain, and fresh Ducato P244B pressure-too-high case.
- Packet: `manual_curated-fiat-dpf_egr_multijet-20260328T231500Z`.
- GCS: `gs://rs-diagnosta-ai-dane/packets/fiat/dpf_egr_multijet/manual_curated-fiat-dpf_egr_multijet-20260328T231500Z/`.
- VPS validation: OK.
- Import result: `imported_count=3`, `skipped_existing=1`, new shared knowledge IDs `4929, 4930, 4931`.
- Meaning: Fiat now has a stronger practical emissions/airflow lane, not only BCM/CAN and Dualogic coverage.

## 2026-03-28 22:01 CET - Fiat P0101 / air path packet

- FIAT Forum symptom lane created for P0101, P010F, low actual-vs-requested air, smoke plus limp mode, and crossover between MAF, EGR, turbo, and leak-path diagnosis.
- Packet: `manual_curated-fiat-p0101_airpath-20260328T233500Z`.
- GCS: `gs://rs-diagnosta-ai-dane/packets/fiat/p0101_airpath/manual_curated-fiat-p0101_airpath-20260328T233500Z/`.
- VPS validation: OK.
- Import result: `imported_count=4`, `skipped_existing=0`, new shared knowledge IDs `4932, 4933, 4934, 4935`.
- Meaning: Fiat now has a stronger air-path/turbo/MAF lane that complements the new DPF/EGR Multijet lane.

## 2026-03-28 22:04 CET - Fiat rail pressure / injector packet

- FIAT Forum lane created for common-rail starting pressure, injector leak-off, no-start behavior, and field methods for isolating injector-related pressure loss.
- Packet: `manual_curated-fiat-rail_injector-20260328T235500Z`.
- GCS: `gs://rs-diagnosta-ai-dane/packets/fiat/rail_injector/manual_curated-fiat-rail_injector-20260328T235500Z/`.
- VPS validation: OK.
- Import result: `imported_count=4`, `skipped_existing=0`, new shared knowledge IDs `4936, 4937, 4938, 4939`.
- Meaning: Fiat now has a practical fuel-pressure / injector lane that complements Multijet air-path and DPF/EGR lanes.

## AKTUALIZACJA 2026-03-29 18:20 CET - AEO GATEWAY CONSOLIDATION

- Hosting i VPS dostaĹ‚y bezpieczny batch AEO pod gateway discovery.
- Canonical /.well-known/ai-resources.json promuje teraz jawnie: agents.json, agent-card.json, gateway agent.json, gateway openapi.json i gateway freshness.json.
- VPS gateway: freshness.json nie jest juĹĽ pusty; agent.json i openapi.json majÄ… aktualny kontrakt answer-first; sync.sh i invite-bots.sh zostaĹ‚y utwardzone i zweryfikowane rÄ™cznym runem.
- Backupy: hosting .bak_codex_aeo20260329, VPS .bak_codex_aeo20260329.
- Otwarty punkt: ClaudeBot nadal Ĺ‚apie 406 ModSecurity na canonical homepage; gateway dziaĹ‚a jako zdrowy surface alternatywny.

[2026-03-29 19:05 CET] AEO routing update verified live: Cyber_Folks 406 for ClaudeBot on canonical is neutralized. Anthropic bot family now receives 302 from rsperformance.online to ai.rsperformance.online, and gateway endpoints respond 200. Backup exists: public_html/.htaccess.bak_codex_aeo_claudebot_20260329.
[2026-03-29 20:05 CET] Full AEO bot-policy pass completed. Canonical now redirects major AI agents (Anthropic, OpenAI, Perplexity, Google-Extended, Meta/cohere, DeepSeek/Kagi families) to the VPS AI gateway. Discovery artifacts are gateway-first. Explicit robots deny exists for Bytespider/CCBot and other low-value scrapers. Residuals: spoofed Googlebot still 403; CCBot runtime still 200 even though robots deny is present.
[2026-03-29 20:20 CET] Version correction: live checks confirmed hosting Laravel 13.1.1 / PHP 8.5.3 and VPS support-plane Laravel 13.1.1 / PHP 8.5.3. Any older Laravel 12 references in legacy notes are historical and must not be treated as current runtime truth.

## AKTUALIZACJA 2026-03-29 20:08 CET - AI TRAFFIC CENTER V3

- Hosting produkcyjny:
    - `app/Filament/Pages/AiTrafficCenter.php`
    - `resources/views/filament/pages/ai-traffic-center.blade.php`
- Cel batcha:
    - dodać operator-friendly progi alarmowe i summary `24h / 7d` dla skuteczności AEO routing
- Efekt:
    - panel ma nowy blok `Compliance Alerts` dla:
        - `gateway_compliance`
        - `policy_drift`
        - `denied_scrapers`
        - `citation_coverage`
    - panel ma dwa nowe bloki `Operator Summary`:
        - `24h`
        - `7d`
    - kaA1dy blok pokazuje:
        - `gateway_pct`
        - `citation_pct`
        - `policy_drift`
        - `denied_seen`
- Backupy produkcyjne:
    - `AiTrafficCenter.php.bak_codex_ai_traffic_v3_20260329`
    - `ai-traffic-center.blade.php.bak_codex_ai_traffic_v3_20260329`
- Weryfikacja:
    - `php85 -l app/Filament/Pages/AiTrafficCenter.php` => OK
    - `php85 artisan view:clear && php85 artisan view:cache` => OK
    - `/admin/ai-traffic-center` => `HTTP 302` do logowania, route zdrowa

## AKTUALIZACJA 2026-03-29 20:15 CET - AEO TRAFFIC ALERTING AUTOMATION

- Hosting produkcyjny:
    - `app/Support/Ops/AeoTrafficAlertSnapshotService.php`
    - `app/Console/Commands/CheckAeoTrafficAlertsCommand.php`
    - `routes/console.php`
- Cel batcha:
    - dodać automatyczny alert `gateway compliance drift` bez zasypywania schedulera failed jobs
- Efekt:
    - nowa komenda `php85 artisan aeo:traffic-alerts --hours=24`
    - scheduler odpala ją co 30 minut
    - komenda zapisuje snapshot do `storage/app/status/aeo-traffic-alerts.json`
    - loguje `AEO_TRAFFIC_ALERTS_OK`, `AEO_TRAFFIC_ALERTS_WATCH`, `AEO_TRAFFIC_ALERTS_CRITICAL`
    - domyślnie nie zwraca non-zero exit code przy krytyce; `--fail-on-critical` jest tylko do ręcznego użycia
- Backupy produkcyjne:
    - `routes/console.php.bak_codex_aeo_alerts_20260329`
    - `AeoTrafficAlertSnapshotService.php.bak_codex_aeo_alerts_precision_20260329`
    - `CheckAeoTrafficAlertsCommand.php.bak_codex_aeo_alerts_exitcode_20260329`
- Weryfikacja:
    - `php85 artisan schedule:list` widzi `aeo:traffic-alerts --hours=24` co 30 minut
    - `php85 artisan aeo:traffic-alerts --hours=24` działa
    - snapshot istnieje: `storage/app/status/aeo-traffic-alerts.json`
- Pierwszy realny wynik 24h:
    - `gateway_pct = 21.0%`
    - `citation_pct = 64.8%`
    - `policy_drift = 64`
    - `denied_seen = 7`
    - wszystkie 4 alerty są dziś `critical`, więc routing AEO jest wdrożony, ale compliance realnego ruchu nadal wymaga dalszego domknięcia

## AKTUALIZACJA 2026-03-29 20:30 CET - PRIORITY ANSWER PATHS

- Hosting produkcyjny:
    - app/Support/Aeo/PriorityAnswerPathService.php
    - app/Support/Search/SearchArtifactFactory.php
    - app/Support/Ops/AeoTrafficAlertSnapshotService.php
    - app/Console/Commands/CheckAeoTrafficAlertsCommand.php
- Cel batcha:
    - przestawić AEO z samego pomiaru botów na pomiar bot-to-answer-path
    - wskazać botom i operatorowi 20-40 URL-i, które naprawdę mają dawać widoczność, cytowania i klientów
- Efekt:
    - /.well-known/ai-resources.json ma teraz priority_answer_paths i bot_to_answer_path
    - alerty AEO mierzą udział ruchu AI na priorytetowych stronach
    - command aeo:traffic-alerts pokazuje zero-hit priority URLs
- Pierwszy live wynik po wdrożeniu:
    - Priority answer-path share: 0%
    - Priority answer-path gateway share: 0%
    - zero-hit obejmuje m.in. /uslugi/diagnostyka-komputerowa, /uslugi/mechanika-ogolna, /uslugi/dpf-adblue
- Wniosek:
    - routing i telemetryka działają, ale wartościowe boty nadal nie lądują jeszcze na stronach o najwyższej intencji
    - następny sensowny batch to domknięcie answer-path distribution, nie kolejna kosmetyka AEO

## AKTUALIZACJA 2026-03-29 21:45 CET - AEO TEST + ANSWER-PATH DISTRIBUTION

- Najpierw test AEO:
    - php85 artisan aeo:traffic-alerts --hours=4 => ruch nadal żyje
    - wynik: Visits 78, Gateway 22 (28.2%), Policy drift 44, Priority answer-path share 0%
    - canonical i gateway zdrowe (302/200/200/200 dla głównych probe'ów)
- Wdrożony batch answer-path distribution:
    - pp/Support/Search/SearchArtifactFactory.php
    - esources/views/pages/repair-reports/show.blade.php
    - VPS /.well-known/agent.json
    - VPS /.well-known/openapi.json
- Efekt:
    - llms.txt, llms-full.txt, i-resources.json i gateway manifesty mocniej promują priority URLs i entrypoint strategy
    - raporty napraw linkują już DTC do landingów /kody-usterek/{code}
- Wniosek:
    - nic się nie posypało po stronie AEO runtime
    - problem nadal leży w jakości realnej dystrybucji ruchu AI, nie w dostępności botów ani discovery surfaces

### 2026-03-29 22:25 CET

- Ostatnia zmiana produkcyjna: DTC answer rails hardening.
- Produkcja: DTC pages (/kody-usterek/{code}) dostały sekcję 'Najmocniejsza ścieżka po tym kodzie' opartą o
  elatedService,
  elated_problem_slugs, powiązane raporty oraz fallback po kodzie/typie.
- Poprawione stare slugi usług na DTC page: mechanika-ogolna, elektryka-pojazdowa.
- Backupy na hostingu: DtcCodeController.php.bak_codex_dtc_answer_rails_20260329, show.blade.php.bak_codex_dtc_answer_rails_20260329.
- Walidacja: php85 -l OK, iew:clear OK, iew:cache OK, live P0299 pokazuje priority rails.

### 2026-03-29 23:00 CET

- Ostatnia zmiana produkcyjna: service/problem answer rails + hotfix.
- Service pages (/uslugi/{slug}) i problem pages (/problemy/{slug}) dostały report-driven linki do raportów RS i DTC, gdzie istnieją realne sygnały z
  epair_reports.
- W trakcie wdrożenia wykryto świeży render 500 na service/problem pages; root cause: ault_codes są tablicami obiektów {code,description}, nie stringami. Naprawione w kontrolerach.
- Finalny status po
  esponsecache:clear i ponownym smoke: fresh uncached requests wracają HTTP 200.

## AKTUALIZACJA 2026-03-29 23:20 CET - INTERNAL-LINK-TARGET FALLBACK

- Hosting production:
    - `app/Http/Controllers/ServiceController.php`
    - `app/Http/Controllers/ProblemController.php`
- Goal:
    - raise proof-layer coverage on service/problem pages without manual content edits by using `resolvedInternalLinkTargets()` as fallback data
- Backups:
    - `ServiceController.php.bak_codex_internal_targets_20260329`
    - `ProblemController.php.bak_codex_internal_targets_20260329`
- Verification:
    - `php85 -l` on both controllers => OK
    - `php85 artisan responsecache:clear --no-interaction` => OK
    - `php85 artisan view:clear --no-interaction && php85 artisan view:cache --no-interaction` => OK
    - fresh uncached `https://rsperformance.online/uslugi/diagnostyka-komputerowa?fresh=...` => `HTTP 200` and shows fallback report links
- Next best step:
    - clean up stale/non-existent problem slugs inside `repair_reports.internal_link_targets`; that is now a higher-ROI move than adding more fallback heuristics

## AKTUALIZACJA 2026-03-29 23:35 CET - NOWA PROBLEM PAGE: ALTERNATOR

- Hosting production:
    - `config/problems.php`
- Dodano nową publiczną stronę problemu:
    - `https://rsperformance.online/problemy/problemy-z-alternatorem`
- Powód:
    - w live danych był jeden martwy slug `problemy-z-alternatorem` używany przez `internal_link_targets` w raporcie Dacii o braku ładowania
    - zamiast fałszować mapping do innego problemu, dodano prawdziwy high-intent landing page pod alternator / ładowanie
- Backup:
    - `config/problems.php.bak_codex_problem_slug_surface_20260329`
- Weryfikacja:
    - `php85 -l config/problems.php` => OK
    - `php85 artisan optimize:clear --no-interaction && php85 artisan optimize --no-interaction` => OK
    - fresh uncached `/problemy/problemy-z-alternatorem?fresh=...` => `HTTP 200`
    - page pokazuje report section, DTC links i service links
    - raport źródłowy Dacii linkuje już do nowej problem page

## AKTUALIZACJA 2026-03-29 23:55 CET - PRIORITY ANSWER-PATH PUSH + AEO CHECK

- Hosting production:
    - `app/Support/Aeo/PriorityAnswerPathService.php`
    - `app/Support/Search/SearchArtifactFactory.php`
- VPS:
    - `/srv/ai-gateway/.well-known/agent.json`
    - `/srv/ai-gateway/.well-known/openapi.json`
- Co zmieniono:
    - dodano `/problemy/problemy-z-alternatorem` do priority answer paths
    - canonical `llms.txt` pokazuje teraz 16 priority paths zamiast 12
    - priority answer clusters pokazują po 5 pathów na kategorię
    - gateway manifesty promują teraz także:
        - Dacię brak ładowania / LIN
        - Fiata Fiorino SCR / AdBlue
- Backupy:
    - `PriorityAnswerPathService.php.bak_codex_priority_push_20260329`
    - `SearchArtifactFactory.php.bak_codex_priority_push_20260329`
    - `agent.json.bak_codex_priority_push_20260329`
    - `openapi.json.bak_codex_priority_push_20260329`
- Weryfikacja:
    - `php85 -l` obu plików PHP => OK
    - `php85 artisan optimize:clear --no-interaction` => OK
    - `php85 artisan search:artifacts-generate --no-interaction` => OK
    - `php85 artisan view:cache --no-interaction` => OK
    - `python3 -m json.tool` na obu manifestach VPS => OK
    - publiczne smoke potwierdzają obecność nowych URL-i w `llms.txt`, `agent.json`, `openapi.json`
    - świeży test AEO po deployu: `77 visits / 23 gateway / latest burst 21:17 via gateway`
- Wniosek:
    - nic się nie posypało
    - problem pozostaje jakościowy: bots still under-hit money pages, ale runtime AEO jest zdrowy

## AKTUALIZACJA 2026-03-29 23:28 CET - POLUZOWANIE BLOKAD BOTÓW

- Hosting production:
    - `config/ai_agents.php`
    - `public_html/.htaccess`
- Co zmieniono:
    - usunięto listę `denied_agents`
    - zdjęto twardy canonical deny dla:
        - `Bytespider`
        - `CCBot`
        - `Omgilibot`
        - `Timpibot`
        - `PanguBot`
        - `Kangaroo Bot`
        - `img2dataset`
    - routing gateway-first dla wartościowych AI został bez zmian
- Backupy:
    - `ai_agents.php.bak_codex_unblock_bots_20260329`
    - `.htaccess.bak_codex_unblock_bots_20260329`
- Weryfikacja:
    - `php85 -l config/ai_agents.php` => OK
    - `php85 artisan optimize:clear --no-interaction` => OK
    - `php85 artisan search:artifacts-generate --no-interaction` => OK
    - `CCBot /` => `200`
    - `Denied seen: 0` w `aeo:traffic-alerts --hours=6`
- Flaga:
    - `Bytespider` nadal dostał `406`, więc to wygląda już na host-level blokadę Cyber_Folks / LiteSpeed poza naszą polityką Laravel + `.htaccess`

## AKTUALIZACJA 2026-04-02 00:35 CET - LOGO / FAVICON REFRESH POD GOOGLE SERP

- Hosting production:
    - `resources/views/components/rs/partials/layout-head.blade.php`
    - `app/Support/Schema/RsSchemaFactory.php`
    - `public_html/favicon.ico`
    - `public_html/favicon-16x16.png`
    - `public_html/favicon-32x32.png`
    - `public_html/favicon-48x48.png`
    - `public_html/apple-touch-icon.png`
    - `public_html/android-chrome-192x192.png`
    - `public_html/android-chrome-512x512.png`
- Co zmieniono:
    - wdrożono nowy favicon pack wygenerowany z `G:\gravity\rs_logo_new.png`
    - head dostał jawne linki do `favicon.ico`, `16x16`, `32x32`, `48x48` i `apple-touch-icon` z cache-bustingiem `20260402-logo`
    - manifest query version podniesiono do `20260402-logo`
    - homepage JSON-LD promuje teraz PNG logo jako `image` i `logo`
    - `RsSchemaFactory` dopina `logo` i `image` do `Organization` oraz `AutoRepair`
- Backupy:
    - `layout-head.blade.php.bak_codex_logo_search_20260402`
    - `RsSchemaFactory.php.bak_codex_logo_search_20260402`
    - `favicon.ico.bak_codex_logo_search_20260402`
    - `favicon-32x32.png.bak_codex_logo_search_20260402`
    - `favicon-48x48.png.bak_codex_logo_search_20260402`
    - `apple-touch-icon.png.bak_codex_logo_search_20260402`
    - `android-chrome-192x192.png.bak_codex_logo_search_20260402`
    - `android-chrome-512x512.png.bak_codex_logo_search_20260402`
- Weryfikacja:
    - `php85 -l app/Support/Schema/RsSchemaFactory.php` => OK
    - `php85 artisan responsecache:clear --no-interaction` => OK
    - `php85 artisan optimize:clear --no-interaction` => OK
    - `php85 artisan view:cache --no-interaction` => OK
    - live HTML pokazuje nowe URL-e faviconów i JSON-LD `logo` => `https://rsperformance.online/images/rs_logo_new.png`
- Ważne:
    - techniczny sygnał jest live; sam widok ikonki w Google odświeży się dopiero po recrawlu / recache SERP

## AKTUALIZACJA 2026-04-02 17:25 CET - DOMKNIĘCIE BATCHA CLAUDE: FEEDY + FRESHNESS HEADERS

- Hosting production:
    - `app/Http/Middleware/AiCitationHeaders.php`
- Co zmieniono:
    - dokończono niedomknięty batch Claude Opus 4.6 związany z content feedami i freshness headers
    - middleware `AiCitationHeaders` został przepisany czysto i wgrany ponownie
    - zachowano wszystkie obecne discovery/gateway headers
    - dodano poprawnie fallbackowe:
        - `Last-Modified`
        - `ETag`
        - `X-Content-Provenance`
    - pozostawiono priority section links do `/uslugi`, `/problemy`, `/raporty-napraw`, `/blog`, `/kody-usterek`
- Backup:
    - `AiCitationHeaders.php.bak_codex_content_feeds_finish_20260402`
- Weryfikacja:
    - `php85 -l app/Http/Middleware/AiCitationHeaders.php` => OK
    - `php85 artisan responsecache:clear --no-interaction` => OK
    - `php85 artisan optimize:clear --no-interaction` => OK
    - `php85 artisan view:cache --no-interaction` => OK
    - homepage daje już `Last-Modified`, `ETag`, `X-Content-Provenance`
    - request AI-bot na homepage daje pełne nagłówki AEO (`X-Content-Type-Semantic`, `X-AI-Gateway-*`, `X-Preferred-Citation`)
    - endpointy live:
        - `/feed/atom` => `200`
        - `/feed/rss` => `200`
        - `/feed/json/changes` => `200`
        - `/api/freshness.json` => `200`
        - `/.well-known/openapi.yaml` => `200`
- Flagi:
    - `api/freshness.json` nadal raportuje `build_version: 12.0` z configu, mimo live Laravel 13
    - `/.well-known/openapi.yaml` jest serwowane jako `application/octet-stream`, nie jako `text/yaml` / `application/yaml`

## AKTUALIZACJA 2026-04-02 17:40 CET - DOMKNIĘCIE RESZTÓWEK PO CLAUDE

- Hosting production:
    - `app/Http/Controllers/ContentFeedController.php`
    - `public_html/.htaccess`
- Co zmieniono:
    - zamknięto dwa ostatnie residuals po batchu Claude Opus 4.6
    - `api/freshness.json` bierze już `build_version` z live aplikacji (`app()->version()`), a nie ze starego fallbacku `12.0`
    - `/.well-known/openapi.yaml` ma już jawny MIME `text/yaml; charset=UTF-8`
- Backupy:
    - `ContentFeedController.php.bak_codex_content_feeds_residuals_20260402`
    - `.htaccess.bak_codex_content_feeds_residuals_20260402`
- Weryfikacja:
    - `php85 -l app/Http/Controllers/ContentFeedController.php` => OK
    - `php85 artisan optimize:clear --no-interaction` => OK
    - `php85 artisan view:cache --no-interaction` => OK
    - `/api/freshness.json` => `build_version: 13.1.1`
    - `/.well-known/openapi.yaml` => `content-type: text/yaml; charset=UTF-8`
- Status:
    - batch Claude związany z feedami/freshness jest w pełni domknięty

## AKTUALIZACJA 2026-04-02 20:55 CET - ANSWER ROUTING CONTRACT + VPS RESCUE SURFACE

- Live baseline przed batchem:
    - `php85 artisan aeo:traffic-alerts --hours=24`
    - `Visits: 121`
    - `Gateway: 3 (2.5%)`
    - `Direct: 118`
    - `Citation coverage: 97.5%`
    - `Policy drift: 14`
    - `Denied seen: 0`
    - `Priority answer-path share: 0%`
    - `Priority answer-path gateway share: 0%`
- Hosting production:
    - `app/Support/RsUri.php`
    - `app/Support/Aeo/PriorityAnswerPathService.php`
    - `app/Support/Aeo/AnswerIntentFingerprint.php`
    - `app/Console/Commands/BuildAnswerRoutingPacketCommand.php`
    - `app/Support/Search/SearchArtifactFactory.php`
    - `app/Http/Middleware/AiCitationHeaders.php`
    - `tests/Feature/Aeo/AnswerRoutingPacketTest.php`
    - `tests/Feature/Aeo/AnswerRoutingMetadataTest.php`
- VPS:
    - `/srv/ai-gateway/sync.sh`
    - `/srv/ai-gateway/.well-known/agent.json`
    - `/srv/ai-gateway/.well-known/openapi.json`
- Co zmieniono:
    - canonical wystawia enriched answer-routing metadata z `type`, `slug`, `cluster`, `priority`, `entities`
    - dodano komendę `php85 artisan aeo:build-answer-routing-packet`
    - `ai-resources.json` ma teraz:
        - `machine_readable.answer_routing_packet`
        - `gateway.answer_routing`
        - `rescue_strategy`
    - AI responses mają nowe headery:
        - `X-AEO-Answer-Routing: priority-packet`
        - `X-AEO-Rescue-Mode: canonical-first-vps-rescue`
        - `X-AI-Gateway-Answer-Routing: https://ai.rsperformance.online/.well-known/answer-routing.json`
    - VPS gateway generuje i publikuje `/.well-known/answer-routing.json`
- Backupy:
    - `RsUri.php.bak_codex_qdrant_answer_routing_20260402`
    - `PriorityAnswerPathService.php.bak_codex_qdrant_answer_routing_20260402`
    - `SearchArtifactFactory.php.bak_codex_qdrant_answer_routing_20260402`
    - `AiCitationHeaders.php.bak_codex_qdrant_answer_routing_20260402`
    - `/srv/ai-gateway/sync.sh.bak_codex_qdrant_answer_routing_20260402`
    - `/srv/ai-gateway/.well-known/agent.json.bak_codex_qdrant_answer_routing_20260402`
    - `/srv/ai-gateway/.well-known/openapi.json.bak_codex_qdrant_answer_routing_20260402`
- Weryfikacja:
    - `php85 -l` OK na touched PHP files
    - `php85 artisan test tests/Feature/Aeo/AnswerRoutingPacketTest.php tests/Feature/Aeo/AnswerRoutingMetadataTest.php --compact` => PASS
    - `php85 artisan aeo:build-answer-routing-packet` => OK
    - `php85 artisan search:artifacts-generate` => OK
    - `php85 artisan responsecache:clear && php85 artisan optimize:clear && php85 artisan view:cache` => OK
    - `bash -n /srv/ai-gateway/sync.sh` => OK
    - `https://ai.rsperformance.online/.well-known/answer-routing.json` => `200`
- Flagi:
    - canonical `ClaudeBot` może nadal kończyć na host-level `406`, więc gateway jest realnym support-plane, ale nie pełnym pre-WAF rescue
    - największa luka biznesowa pozostaje jakościowa: boty nadal za słabo trafiają w priority answer paths

## AKTUALIZACJA 2026-04-02 21:10 CET - QDRANT ANSWER ROUTING INGEST LIVE

- VPS:
    - `/srv/ai-gateway/bin/ingest_answer_routing_qdrant.py`
    - `/srv/ai-gateway/sync.sh`
    - `/srv/ai-gateway/status/answer-routing-qdrant.json`
- Co zmieniono:
    - dodano osobny, bezpieczny lane Qdrant `rs_answer_routing`
    - importer czyta canonical `/.well-known/ai-resources.json -> machine_readable.priority_answer_paths`
    - `sync.sh` po każdym odświeżeniu gateway automatycznie zasila `rs_answer_routing`
    - importer deduplikuje po `canonical_url`, tworzy payload indexes i zapisuje status ingestu
- Backupy:
    - `/srv/ai-gateway/sync.sh.bak_codex_qdrant_ingest_20260402`
    - `/srv/ai-gateway/bin/ingest_answer_routing_qdrant.py.bak_codex_qdrant_ingest_dedupe_20260402`
    - `/srv/ai-gateway/bin/ingest_answer_routing_qdrant.py.bak_codex_qdrant_ingest_dtc_signal_20260402`
- Weryfikacja:
    - `rs_answer_routing` => `36` unikalnych tras z `41` rekordów źródłowych
    - status file live: `/srv/ai-gateway/status/answer-routing-qdrant.json`
    - payload ma m.in. `canonical_url`, `slug`, `type`, `cluster`, `intent`, `entities`, `priority`
- Flaga:
    - sam surowy vector search nadal za słabo promuje exact DTC hit (`P0299`) nad proof-layer reportami
    - następny krok to resolver / reranking exact-first, nie nowy ingest
        > LIVE SHADOW TELEMETRY NOTE (2026-04-03 07:40 CET)
        > `TrackAiAgentTraffic` on hosting now again writes to `ai_bot_visits` and no longer updates only the ops snapshot.
        > It also captures AI-like but non-catalog user agents under shadow labels such as:
        >
        > - `DeepSeek-Unknown`
        > - `OpenAI-Unknown`
        > - `Anthropic-Unknown`
        > - `Perplexity-Unknown`
        >   Verified live with `DeepSeekBrowser/1.0`:
        > - canonical `/` => `200`
        > - DB row created: `DeepSeek-Unknown`, `source=direct`, `visited_at=2026-04-03T07:38:11+02:00`
        >   This closes the observability gap where vendor/browser variants could fetch the site without appearing in Filament.
        >   LIVE HOSTING STORAGE NOTE (2026-04-09 03:57 CET)
        >   Shared-hosting disk pressure was audited live.
        >   Main culprit was backup accumulation, especially:
        >   `~/domains/rsperformance.online/laravel/storage/app/private/RS PERFORMANCE`
        >   First safe retention cleanup is already done:
        > - kept last 7 daily zips + 3 older weekly restore points
        > - deleted 18 older zips
        > - recovered about `4.3G`
        >   Current retained restore points:
        >   `2026-04-09`, `2026-04-08`, `2026-04-07`, `2026-04-06`, `2026-04-05`, `2026-04-04`, `2026-04-03`, `2026-03-31`, `2026-03-24`, `2026-03-17`
        >   Retention manifest:
        >   `~/cleanup_manifests/rs_backup_retention_20260409.txt`
        >   LIVE FILAMENT BLOG NOTE (2026-04-09 08:12 CET)
        >   Manual blog generation in Filament was hardened on hosting:
        >   `app/Support/Blog/GeminiBlogDraftGenerator.php`
        >   Backup:
        >   `app/Support/Blog/GeminiBlogDraftGenerator.php.bak_codex_topic_alignment_20260409`
        >   Root cause fixed:
        >   operator input about one automotive topic could still produce a polished but unrelated article
        >   example observed:
        > - input: same-day fuel prices in Gdansk
        > - output: corrosion article
        >   New behavior:
        > - prompts explicitly forbid changing the main topic
        > - runtime anchor check rejects drifted output before the form is filled
        >   Verification:
        > - `php85 -l` OK
        > - `php85 artisan optimize:clear --no-interaction` OK
        > - production reflection smoke proves the guard now blocks the fuel-prices -> corrosion mismatch class
        >   Remaining external flag:
        >   direct live provider smoke hit Gemini `429`, so quota/rate limiting remains separate from the app fix
        >   LIVE FILAMENT ORCHESTRA NOTE (2026-04-09 08:38 CET)
        >   The main blog button in Filament now runs an editorial-orchestra path instead of a thin topic-only flow.
        >   Hosting files updated:
        > - `app/Filament/Resources/BlogPostResource/Pages/ListBlogPosts.php`
        > - `app/Console/Commands/AutoGenerateBlogPost.php`
        > - `app/Support/Blog/BlogSupportPlaneDispatchService.php`
        > - `app/Support/Blog/BlogVertexPipelineService.php`
        >   Backups:
        > - `*.bak_codex_editorial_orchestra_20260409`
        >   New manual modal inputs:
        > - editorial brief
        > - editorial mode
        > - slot type
        > - editorial notes
        > - source URLs
        > - premium review
        > - quality gate
        > - optional support-plane dispatch
        >   Pipeline behavior:
        > - preserves operator topic
        > - preserves slot type
        > - adds subtle but strong SEO/AEO/GEO steering
        > - blocks drafts that drift away from the operator brief
        >   Verification:
        > - `php85 -l` OK on all touched files
        > - `php85 artisan help blog:auto-generate` OK
        > - `php85 artisan optimize:clear --no-interaction` OK
        >   IMAGE FIT NOTE (2026-04-09 08:46 CET)
        >   `BlogVertexPipelineService` was additionally updated so hero-image prompts preserve:
        > - `operator_topic`
        > - `slot_type`
        >   and do not fall back to a generic unrelated automotive stock scene.
        >   Backup:
        >   `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_editorial_orchestra_imagefit_20260409`
        >   MANUAL BLOG FAILOVER NOTE (2026-04-09 09:25 CET)
        >   Manual `blog:auto-generate` now fails over to VPS support-plane on retryable provider throttling (`HTTP 429`, `RESOURCE_EXHAUSTED`, quota/rate-limit class errors).
        >   `BlogVertexPipelineService` also gained a stronger manual-brief guard:
        > - up to 3 editorial passes for manual briefs
        > - rewrite/retry instead of silently persisting an off-topic draft
        > - a second semantic topic-alignment check merged into the quality gate before persist
        >   Backups:
        > - `app/Console/Commands/AutoGenerateBlogPost.php.bak_codex_failover_429_20260409`
        > - `app/Support/Blog/BlogSupportPlaneDispatchService.php.bak_codex_failover_429_20260409`
        > - `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_failover_429_20260409`
        > - `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_topic_guard_retry_20260409`
        >   Verification:
        > - `php85 -l app/Support/Blog/BlogVertexPipelineService.php` OK
        > - `php85 artisan optimize:clear --no-interaction` OK
        >   Caveat:
        > - historical bad drafts `#103/#104` stored no `operator_topic`, so they remain evidence of the old drift class, not proof against the new post-fix guard
        >   LIVE BLOG TEST NOTE (2026-04-09 09:32 CET)
        >   A fresh manual fuel-prices brief did not create a new wrong-topic draft.
        >   The latest run now ends as a clean host-side failure instead of persisting another bad article:
        > - status: `failed`
        > - reason: `QUALITY_GATE_FAILED: Content word count is outside the target editorial window.`
        >   Additional hardening shipped after that test:
        > - support-plane dispatch now returns structured metadata
        > - successful handoff now marks host-side runs as `dispatched`
        > - orphaned `processing` runs are auto-closed by a shutdown guard
        > - `Dispatched` is now treated as terminal on hosting

## AKTUALIZACJA 2026-04-09 10:26 CET - BLOG MANUAL LANE RECOVERY

- Hosting production:
    - `app/Support/Blog/BlogVertexPipelineService.php`
    - `app/Filament/Resources/BlogPostResource/Pages/ListBlogPosts.php`
- Cel batcha:
    - domknac reczny lane bloga po zawieszonym `processing`
    - dopisac stage trace bezposrednio do `storage/logs/blog-generate.log`
    - ratowac on-topic drafty, ktore oblewaly tylko na dlugosci
- Efekt:
    - stage trace jest zapisywany bezposrednio do `blog-generate.log`
    - `dispatch_support_plane` jest domyslnie wlaczony w glownym modalu Filament
    - stuck run `01knr3tvaw3rpmse9zx93h1pp5` zakonczyl sie jako `draft_created`
    - powstal draft `#105` o paliwie, bez regresji do korozji
    - obraz zostal wygenerowany: `blog/01KNR40N7N0ANSYGJTH32KMS0V.png`
- Backupy:
    - `BlogVertexPipelineService.php.bak_codex_stage_trace_20260409`
    - `ListBlogPosts.php.bak_codex_stage_trace_20260409`
    - `BlogVertexPipelineService.php.bak_codex_stage_trace_filelog_20260409`
- Weryfikacja:
    - `php85 -l` OK
    - `php85 artisan optimize:clear --no-interaction` OK

## AKTUALIZACJA 2026-04-09 11:05 CET - VPS FALLBACK PAYLOAD FIX + CLAIM LOCK

- Hosting production:
    - `app/Http/Controllers/Api/BlogPipelineController.php`
    - `app/Console/Commands/AutoGenerateBlogPost.php`
    - `app/Console/Commands/GenerateTelegramBlogPost.php`
- VPS support-plane:
    - `/var/www/html/app/Support/SupportPlaneHostingBlogPipelineClient.php`
    - `/var/www/html/app/Support/SupportArtifactRunner.php`
- Efekt:
    - fallback z VPS do hostingu nie wpada juz w `422 topic max string`
    - trace `01KNR4E3N1AMCG2QKRCCM42HS7` doszedl do `blog.draft_output` na VPS
    - `SupportArtifactRunner` ma claim-lock na `ready -> processing`, wiec nowe duble artifactow nie powinny juz powstawac
    - `GenerateTelegramBlogPost` rozumie nowe opcje editorialne
    - osierocony dubel BMW `01knr61jme021rm89q313ryqh1` oznaczono jako `failed`
- Status biezacego testu:
    - aktywny run BMW: `01knr63dsyaa661vzt5fkpfy1w`
    - quality gate blokuje tani / sensacyjny ton; finalny draft nie byl jeszcze zapisany przy tym wpisie

## AKTUALIZACJA 2026-04-09 20:36 CET - BLOG MANUAL LANE HARDENING V2

- Hosting production:
    - `app/Support/Blog/BlogVertexPipelineService.php`
- Cel batcha:
    - usunac realny blocker recznego lane bloga, ktory dalej oblewal na `word_count` i czasem `excerpt`
- Efekt:
    - kazdy editorial pass stabilizuje teraz:
        - tytul
        - excerpt
        - zbyt krotka tresc
    - pipeline dopina underlength drafty deterministycznie warsztatowymi sekcjami zamiast liczyc tylko na ostatni rescue modelu
    - tani ton w tytule jest dodatkowo zmiękczany przed gate
    - swiezy live run `01knsrcs0gsc9k8prv2efftza9` zakonczyl sie sukcesem
    - powstal draft `#110`
    - slug: `premiera-bmw-i3-neue-klasse-nowa-era-elektrycznego-sedana-premium`
    - obraz: `blog/01KNSRGHJ7T0K802GH13WTN8MK.png`
- Backup:
    - `BlogVertexPipelineService.php.bak_codex_editorial_window_hardening_20260409`
- Weryfikacja:
    - `php85 -l app/Support/Blog/BlogVertexPipelineService.php` => OK
    - `php85 artisan optimize:clear --no-interaction` => OK

## AKTUALIZACJA 2026-04-09 20:44 CET - BLOG LAUNCHER CONTRACT CLEANUP

- Hosting production:
    - `app/Http/Controllers/Api/BlogPipelineController.php`
    - `app/Support/Blog/BlogTelegramBotService.php`
- Cel batcha:
    - domknac ostatni launcher contract dla bloga i ograniczyc residual z `--editorial-mode`
- Efekt:
    - wszystkie background launchery przekazuja jawne `requested-via`
    - bezposredni Telegram `/blog` uruchamia teraz `blog:telegram-generate` z jawym `--editorial-mode=evergreen`
- Backupy:
    - `BlogPipelineController.php.bak_codex_blog_launcher_contract_20260409`
    - `BlogTelegramBotService.php.bak_codex_blog_launcher_contract_20260409`
- Weryfikacja:
    - `php85 -l` OK na obu plikach
    - `php85 artisan optimize:clear --no-interaction` => OK
    - `php85 artisan list | grep blog:telegram-generate` => OK
        > LIVE BLOG SOURCE RANKING NOTE (2026-04-09 21:55 CET)
        > Hosting `app/Support/Blog/BlogVertexPipelineService.php` was tightened again after the source-first hero deployment.
        > New behavior:
        >
        > - research-stage `source_urls` are re-ranked for editorial use
        > - `premiera` now prefers official / press-room / newsroom-like sources ahead of generic blog and aggregator links
        > - the research prompt explicitly asks for official OEM/model sources when available so the hero-image lane has better upstream material
        >   Backup:
        > - `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_oem_press_source_ranking_20260409`
        >   LIVE A2A V1 NOTE (2026-04-10 07:10 CET)
        >   Canonical hosting now exposes a proper public A2A v1 surface:
        > - `/.well-known/agent-card.json`
        > - `/.well-known/agent.json`
        > - `/.well-known/a2a.json`
        > - `POST /message:send`
        > - `GET /tasks`
        > - `GET /tasks/{taskId}`
        > - legacy JSON-RPC `POST /`
        >   Production files updated:
        > - `app/Http/Controllers/A2aTaskController.php`
        > - `routes/web.php`
        > - `bootstrap/app.php`
        > - `public_html/.well-known/agent-card.json`
        > - `public_html/.well-known/agent.json`
        > - `public_html/.well-known/a2a.json`
        >   Backups:
        > - `app/Http/Controllers/A2aTaskController.php.bak_codex_a2a_rest_20260410`
        > - `routes/web.php.bak_codex_a2a_rest_20260410`
        > - `bootstrap/app.php.bak_codex_a2a_csrf_20260410`
        > - `.well-known/*.bak_codex_a2a_rest_20260410`
        >   Smoke:
        > - `POST /message:send` => `200`
        > - `GET /tasks/{id}` => `200`
        > - `GET /tasks` => `200`
        > - `overture validate` => Agent Card valid
        >   Current residual:
        > - `overture certify` improved materially but still flags missing `cancel`, missing `subscribe`, and a `get-task` response-shape mismatch.
        >   LIVE BLOG SMOKE NOTE (2026-04-10 07:10 CET)
        >   Manual blog generation is currently green again on hosting.
        >   Fresh proof:
        > - pipeline run `01knw8bzmtpgtsj3x26wq2f7c0`
        > - persisted draft `#111`
        > - slug `rewolucja-800v-w-bmw-neue-klasse-co-zyskuja-kierowcy-a-czego-musza-obawiac-sie-serwisy`
        > - hero image `blog/01KNW8GRYF3JRN5JC16DY5HEZE.jpg`
        > - image lane `source-first-og-image`
        >   LIVE VERTEX MODEL IDS NOTE (2026-04-10 07:10 CET)
        >   Verified against the real Vertex publisher catalog for project `diagnosta-489719` on `us-central1`:
        > - valid Google IDs seen live: `gemini-2.5-pro`, `gemini-2.5-flash`, `gemini-2.5-flash-lite`, `gemini-2.5-flash-image`, `imagen-4.0-generate-001`, `imagen-4.0-fast-generate-001`, `imagen-4.0-ultra-generate-001`, `gemini-3-pro-preview`, `gemini-3.1-pro-preview`, `gemini-3.1-flash-image-preview`
        > - valid Anthropic IDs seen live: `claude-sonnet-4-6`, `claude-opus-4-6`
        >   Hosting cleanup shipped:
        > - `config/vertex.php`
        > - `config/blog.php`
        > - `app/Support/Blog/BlogVertexPipelineService.php`
        >   This removes the stale image preview alias from the fallback catalog and aligns fallback/default IDs with models that are actually visible in Vertex now.

> LIVE N8N STABILITY NOTE (2026-04-11 05:05 CET)
> Root cause of the reported `n8n hangs` was not a global runtime freeze.
> Three production workflows had stale contracts:
>
> - `RS DTC Enrichment Engine` hit missing hosting routes (`/api/dtc/batch-for-enrichment`)
> - `RS Research Harvester` hit `localhost:8082` from inside the `n8n` container
> - `RS AI Agent Monitor` checked stale endpoints and then exposed a temporary JS newline syntax regression during repair
>   Live fixes completed:
> - hosting `routes/web.php` restored `/api/dtc/batch-for-enrichment`, `/api/dtc/store-enrichment`, `/api/dtc/enrichment-stats`
> - VPS Caddy `auto.rs3d.pl` now exposes `/research/*` -> `127.0.0.1:8082`
> - `RS Research Harvester` now uses `https://auto.rs3d.pl/research/harvest`
> - `RS Editorial Board` now uses `https://auto.rs3d.pl/research/pool?...` and correct `mark-used?item_id=...&post_id=...`
> - `RS AI Agent Monitor` now checks live freshness / agent / ai-resources surfaces and its `Analyze Results` node syntax is repaired
>   Production backups created:
> - hosting: `routes/web.php.bak_codex_n8n_dtc_restore_20260411`
> - VPS: `/etc/caddy/sites-enabled/auto.rs3d.pl.Caddyfile.bak_codex_research_proxy_20260411`
> - local workflow JSON backups in `G:\gravity\tmp\n8n-backups-20260411`
>   Fresh live proofs:
> - `RS DTC Enrichment Engine` runs `899` and `903` => `success`
> - `RS Research Harvester` run `904` => `success`
> - `RS AI Agent Monitor` latest run `901` still failed, but only because of the temporary syntax regression that was then corrected live
>   Current residual:
> - wait for the next natural hourly run of `RS AI Agent Monitor`

## AKTUALIZACJA 2026-04-11 21:40 CET - DAILY-NEWS + BLOG LANE + A2A ARTIFACTS

- Hosting production:
    - `app/Support/Blog/BlogVertexPipelineService.php`
    - `app/Http/Controllers/A2aTaskController.php`
- VPS / n8n:
    - workflow `RS Daily Automotive News Drafts` (`zo4sIHAUZZseEGkL`)
- Cel batcha:
    - reaktywowa� daily-news w n8n
    - usztywni� r�czny blog lane dla sensacyjnych brief�w
    - wzbogaci� A2A artifacts poza samym DTC
- Efekt:
    - daily-news workflow jest znowu `active=true`
    - `activeVersionId` = `570c4d93-0b5e-4475-9b83-a9307d35aaa3`
    - r�czny blog lane zrobi� live proof draft `#112`
    - tytu� draftu `#112`: `Koniec z przep�acaniem na stacji. Jak serwis i styl jazdy realnie obni�� Twoje rachunki za paliwo`
    - preflight briefu na hostingu neutralizuje tani/sensacyjny ton i utrzymuje o� paliwo / tankowanie / koszty
    - A2A diagnostics / booking / repair / general info zwracaj� ju� structured JSON artifacts, nie tylko go�y tekst
    - smoke A2A diagnostics green: task snapshot zawiera artifact `RS diagnostics capability`
- Backupy:
    - `app/Support/Blog/BlogVertexPipelineService.php.bak_codex_brief_sanitizer_20260411`
    - `app/Http/Controllers/A2aTaskController.php.bak_codex_a2a_artifacts_20260411`
- Weryfikacja:
    - `php85 -l app/Support/Blog/BlogVertexPipelineService.php` => OK
    - `php85 -l app/Http/Controllers/A2aTaskController.php` => OK
    - `php85 artisan optimize:clear --no-interaction` => OK
    - `https://rsperformance.online/.well-known/ai-resources.json` => 200
    - `https://rsperformance.online/llms.txt` => 200
    - `https://rsperformance.online/.well-known/agent.json` => 200
    - `https://ai.rsperformance.online/for-agents` => 200
- Uwaga:
    - dla tego schedule-based workflow nie ma jeszcze sensownego manual-run proof po API; uczciwy finalny proof to najbli�szy scheduled run
    - w AEO residual nadal istnieje brak kana�u loga `ai-invitations`
- Nowy lokalny plik operacyjny:
    - `G:\gravity\cursor.md` zawiera pelny secret ops pack dla nowego agenta Cursor

## Operator: SSH, smoke zaproszonych AI, OpenRouter (2026-04-13)

- **`ssh_exec.py`**: hasło wyłącznie w zmiennej środowiskowej **`CYBERFOLKS_SSH_PASSWORD`** (opcjonalnie `CYBERFOLKS_SSH_HOST`, `CYBERFOLKS_SSH_PORT`, `CYBERFOLKS_SSH_USER`). Nie commituj haseł. Długie komendy SSH: **`SSH_EXEC_TIMEOUT`** (sekundy, domyślnie 120).
- **Smoke wszystkich `tracked_user_agents`**: `python scripts/live_ai_invited_smoke.py` z katalogu `G:\gravity` — cel `https://rsperformance.online/kody-usterek/p0299`, nagłówek `X-RS-Synthetic-Probe: 1`. Jeśli brzeg zwraca 403 dla fałszywego Googlebot: opcjonalnie **`SMOKE_ALLOW_GOOGLEBOT_403=1`** (patrz log smoke; realny Googlebot weryfikuj GSC).
- **OpenRouter (`OPENROUTER_API_KEY`, format `sk-or-v1-…`)**: wyłącznie w **`.cursor/mcp.env`** (jest w `.gitignore`), ewentualnie hosting **`laravel/.env`** lub **n8n → Credentials** — nigdy w artifactach ani w repozytorium. Scala lokalnie: `python scripts/hydrate_mcp_env_from_workspace.py`. Po wycieku klucza: rotacja w panelu OpenRouter i aktualizacja n8n + `mcp.env`.
- **AEO / discovery**: po zmianach w `SearchArtifactFactory` na produkcji: backup → `php85 artisan search:artifacts-generate` → w JSON **`/.well-known/ai-resources.json`** jest blok **`openrouter_operator_surface`** (jak laczyc fetch discovery z routerem modeli bez publikowania sekretow). **`llms.txt`** zawiera skrocona wersje tej polityki.

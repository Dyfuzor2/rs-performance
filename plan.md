# RS Performance Master Plan

Last update: 2026-03-15 (VPS programy: backup, Prometheus/Grafana, Portainer, Fail2ban)
Mode: production locked
Source of truth runtime: Cyber-Folks production + VPS support stack

## Rules

- Every agent starts with `start.md`, then verifies production and VPS over SSH.
- Every agent reads this file before touching code.
- This file must always contain the full active project plan until the project is finished. Do not replace it with a short summary.
- This file must always show the current focus, current blocker and current next step for the batch in progress.
- After every production batch, update both `start.md` and this file.
- Production is the execution environment. Local workspace is only a helper.
- Vertex AI Studio directive: if a task requires Vertex usage, available API keys, service-account JSON, Vertex AI Studio access and model configuration may be used without asking for an extra approval.
- If a needed model is missing in Vertex AI Studio, add or enable the required model as part of the task, then explicitly report that it was done.

## Current Focus

- Current batch: stage 2 Vertex-backed publication draft pipeline for `rs-support-plane`.
- Current objective: keep VPS as the async AI producer while shared hosting remains the canonical review and publish surface, and progressively turn VPS into the DTC/AI knowledge layer.
- Current status: review queue on shared hosting is live, the DTC layer is imported on VPS, the first AI Lighthouse surfaces for DTC are public, the public `/kody-usterek` hub is live on shared hosting with local DTC storage and report linkage, answer/provenance/freshness JSON surfaces are public, the broader shared-host discovery layer now includes DTC in `ai-resources`, `llms`, `llms-full`, `content index` and `sitemap.xml`, the homepage diagnostics section already contains the live DTC search widget with `/kody-bledow` redirecting into the canonical hub, both desktop and mobile navigation now expose a direct `Kody usterek` entry, `llms.txt` now opens with an explicit public DTC hub declaration and alias, `ai-resources.json` now promotes the canonical DTC hub earlier in fetch order, the global head now advertises DTC feeds plus Umami Cloud tracking, and the global footer now also links directly to `Kody usterek (DTC)` to strengthen human navigation plus crawler link-graph discovery. The hub now has the first selective-indexing layer with family slices, report-linked codes and dynamic `index/noindex` behavior for detail pages, curated manufacturer plus family slices are live on both public URLs and discovery/sitemap surfaces, the strongest-landings DTC feed based on real RS repair-report signals is public and wired into discovery, and the public DTC detail surfaces are now enriched with `expandedAnswer`, `nextChecks`, `entityContext`, `aiDiscovery`, a visible `AI provenance` block, and a mobile-safe CTA back to the full DTC hub so the canonical pages carry stronger answer/provenance/freshness/entity/discovery cues directly in the user-facing page, not only in feeds. Part 2 now has a production roster of editorial/SEO/AEO/ops agents, a Filament operator control loop, bounded recommendation summaries, real source-data signals from published reports plus DTC, persisted recommendation snapshots stored in the database, the first bounded execution preview lane for SEO/AEO/maintenance, the review workflow for those previews, the first ultra-low-risk apply lane for maintenance housekeeping, private internal exports for SEO/AEO/editorial/diagnostic lanes, grouped artifact history, artifact inspector/download, approved chain packages, coverage matrix, comparison tooling, safe cache optimize, `Search Ops` packet telemetry/inspector, and a site-level command center in `AutomationCenter` for services/discovery/workflow/crawler/cache readiness. `AutomationCenter` now also exposes a private `Search Ops taskboard` with the latest SEO+AEO packet counts and top targets, and `Search Ops` has now gained live private PageSpeed signals through `search-ops:pagespeed-fetch`, daily scheduler `05:20`, packet support for `pagespeed_signals` and panel telemetry for PSI targets/scores. Search Console ingestion is deployed end-to-end in internal-only mode, and the HTML verification file for Search Console is live at `https://rsperformance.online/google7a4352194aefd6df.html`. Google-side API enablement is done; the only missing piece is Search Console property access for the service account. Active `n8n` branding and configuration have also been removed from shared-host Filament and `config/ops.php`. Both `500` incidents on `/admin/diagnosta-center` are fixed.
- Current blocker: no production runtime blocker. The external blocker for the controlled `GSC -> analysis -> approval` loop (Search Console property access) has been resolved. The service account now successfully fetches GSC signals.
- Current next step: wire the combined GSC + PageSpeed review-first `GSC -> analysis -> approval` lane now that signals are successfully fetching, and then re-check external AI/browser visibility.

## Plan In 2 Parts

### Part 1. Public Foundation

Goal: finish the visible, canonical, production-facing layer that users, Google, AI browsers and operators can already rely on, including ultra modern SEO and ultra modern AEO as core product surfaces, not as an afterthought.

1. Keep runtime stability and watchdog health as the release gate.
2. Keep shared hosting as the canonical public source and VPS as the async producer.
3. Keep the verified delivery backbone:
    - `VPS -> shared hosting` callback for `repair_report.case_study_draft`
    - review queue via `support_plane_*`
    - Filament approve / reject / publish actions
4. Finish the knowledge and visibility foundation:
    - VPS DTC layer and lookup endpoints
    - public `/kody-usterek` hub
    - homepage diagnostics widget
    - FAQ DTC helper
    - `/kody-bledow` alias into the canonical hub
    - ultra modern SEO/AEO layer:
        - answer-first blocks
        - provenance
        - freshness
        - machine-readable JSON surfaces
        - AI discovery surfaces
        - curated internal linking
        - manufacturer and family slices
        - curated discovery/sitemap promotion of strongest landing pages
        - no prices in AI/SEO/AEO/DTC surfaces
5. Continue curated public expansion only:
    - featured code pages
    - report-linked codes
    - family slices `P/C/B/U`
    - manufacturer slices
    - curated DTC sitemaps
    - status:
        - family slices live
        - manufacturer slices live
        - discovery/sitemap layer already advertises curated manufacturer slices

### Part 2. Autonomous Growth

Goal: add the editorial/ops agent system, model routing and controlled automation on top of the stable public foundation.

1. Add the editorial and ops agent team:
    - editor-in-chief
    - technical automotive journalist
    - workshop diagnostician
    - SEO strategist
    - AEO strategist
    - fact-checker
    - realistic visual photographer
    - optimization & maintenance lead
2. Expose agents in Filament:
    - manual run
    - dry run
    - analyze only
    - execute allowed actions
    - audit trail
    - guardrails per role
    - status:
        - production roster and guardrails are now visible in `Diagnosta AI`
        - first operator actions and cached audit trail are now live
        - bounded recommendation summaries are now live
        - real data-backed signals from published reports and DTC are now live
        - recommendation snapshots are now persisted for review
        - first non-destructive execution lane is now live as execution previews
        - private artifact browser/history for approved SEO/AEO exports is now live
        - selected private artifact inspector is now live
        - private artifacts can now be downloaded directly from Filament
        - internal execution/export lane now also covers `technical_journalist`, `visual_photographer`, `fact_checker`, `editor_in_chief` and `workshop_diagnostician`
        - filtered `Editorial review chain` view is now live for the private SEO/AEO/editorial/diagnostic workflow
        - filtered chain telemetry counters are now live for `total`, `ready`, `approved`, and `internal exports`
        - private artifacts are now labeled by lane to reduce operator ambiguity in history and inspector views
        - private artifact history is now grouped by lane for faster operator scanning
        - snapshot-level lane badges are now visible directly in review surfaces
        - combined private `approved_chain_package` export is now available from the filtered editorial/diagnostic chain
3. Expand model routing by task:
    - `Gemini 3.1 Pro Preview` for high-quality reports/blog/AEO work
    - `Gemini 3.1 Flash` or `Flash Lite` for lighter batch SEO/admin work
    - `Imagen` for realistic article/report visuals
    - `Claude` lanes only after quota is raised
4. Build the controlled SEO Ops loop:
    - `GSC -> analysis -> Telegram approval -> controlled apply -> verification`
5. Harden background automation:
    - retry/backoff for transient `Vertex 429` now live for publication drafts
    - publication-draft queue/cooldown telemetry command now live on VPS
    - historical stale draft-error metadata cleanup command now live on VPS
    - later GitHub-backed DTC sync/validation
    - stronger VPS AI lighthouse exports and telemetry

## Full Delivery Plan

### Phase 0. Runtime Stability

Goal: keep production healthy while all AI layers grow around it.

- Keep shared hosting at HTTP 200 before any visual or content-layer work.
- Keep config, routes, views and events cached after each production batch.
- Keep watchdog green and treat fresh `production.ERROR` entries as blockers before new risky changes.
- Keep VPS support services healthy:
    - `auto.rs3d.pl`
    - `mcp.rs3d.pl`
    - `analytics.rs3d.pl`
    - `status.rs3d.pl`

Exit criteria:

- homepage `200`
- blog `200`
- watchdog green
- VPS health endpoints green

### Phase 1. Close The AI Report Loop

Goal: convert VPS AI output into an operator-usable workflow on shared hosting.

- Keep the verified `VPS -> shared hosting` callback contract for `repair_report.case_study_draft`.
- Keep callback results in the shared-hosting review queue.
- Keep Filament review actions:
    - approve
    - reject
    - edit before publish
    - publish
- Keep VPS as async producer only; no public render depends on VPS availability.

Exit criteria:

- a controlled repair-report draft completes end-to-end
- operator sees it in Filament
- operator can accept/reject/publish without touching VPS manually

### Phase 2. DTC Knowledge Base On VPS

Goal: turn fault-code data into reusable source material for reports, blog, SEO, AEO and AI agents.

- Upload and normalize `dtc_complete.db` as seed data on VPS.
- Keep the full corpus internally on VPS.
- Normalize fields:
    - code
    - manufacturer
    - type `P/C/B/U`
    - generic/manufacturer-specific
    - description
    - source
    - freshness
    - confidence
- De-duplicate repeated `code + manufacturer` rows.
- Later add GitHub sync/validation layer on top of the seed:
    - primary target: `OBDb`
    - generic fallback: `todrobbins/dtcdb`
    - optional validation target: `Wal33D/dtc-database`

Exit criteria:

- VPS exposes an internal DTC lookup service/feed
- support-plane can enrich report analysis with DTC meaning/context

### Phase 3. Public DTC Hub For People + SEO + AEO

Goal: turn the DTC layer into a strong public long-tail acquisition and answer surface.

- Add a public section on shared hosting:
    - `/kody-usterek`
- Keep `/kody-usterek` as the canonical hub and treat `/kody-bledow` only as an alias / entrypoint, not as a second canonical catalog.
- Add code pages:
    - `/kody-usterek/{code}`
- Add a homepage conversion widget inside the diagnostics section:
    - input for code or phrase
    - direct jump to `/kody-usterek/{code}` for exact DTC input
    - fallback search into `/kody-usterek?q=...`
    - clear CTA into `diagnostyka-komputerowa`
- Add a lighter FAQ helper widget:
    - "Masz juz kod bledu? Sprawdz co oznacza"
    - link or inline search entrypoint
- Status:
    - widget delivered on homepage
    - FAQ helper delivered
    - alias `/kody-bledow` delivered as 301 entrypoint
- Do not publish all 18k codes equally on day one.
- Use selective rollout:
    - top generic codes
    - codes seen in RS reports
    - codes mapped to active services
    - codes with strongest intent and quality
- Status:
    - first selective-indexing pass is already live
    - DTC hub now exposes family browse cards (`P/C/B/U`)
    - DTC hub now highlights report-linked codes from real RS repair reports
    - detail pages dynamically switch between indexable and helper-only status
- Each public code page should include:
    - short answer
    - expanded explanation
    - symptoms
    - likely causes
    - first checks
    - when to stop driving / seek help
    - related RS services
    - related RS reports
    - related blog posts
    - provenance/freshness/source

Exit criteria:

- public DTC hub exists
- first curated set of code pages is indexable and linked internally
- pages are useful to people, not thin-content placeholders

### Phase 4. Modern AEO Layer

Goal: make content answer-first for AI browsers and answer engines, not just classic SEO.

- Add `answer_blocks` to reports, blog posts and DTC pages.
- Add `provenance_blocks`:
    - source of answer
    - evidence type
    - confidence
    - last verified at
- Add `freshness` metadata:
    - `last_verified_at`
    - `updated_reason`
    - `evidence_version`
- Add machine-readable exports:
    - `answers.json`
    - `freshness.json`
    - stronger report/blog/DTC JSON and markdown mirrors
- Use FAQ/HowTo only as supporting structure, not as the main AEO tactic.

Exit criteria:

- key report/blog/DTC pages expose answer-first sections
- AI-facing feeds expose answers + provenance + freshness

### Phase 5. Blog Redaction System

Goal: build a professional, human-like expert editorial workflow.

- Create the blog team with distinct roles and personalities:
    - editor-in-chief
    - technical automotive journalist
    - workshop diagnostician
    - SEO strategist
    - AEO strategist
    - fact-checker
    - realistic visual photographer
- Make the visual role generate realistic article images from article briefs.
- Use blog workflows for:
    - topic selection
    - outline
    - draft
    - technical review
    - SEO/AEO pass
    - image brief and image generation
    - final approval

Exit criteria:

- one full article can be produced by the team flow
- article includes image, SEO/AEO structure and review trail

### Phase 6. AI Lighthouse Hub On VPS

Goal: make VPS visibly invite AI agents and AI browsers into the knowledge network.

- Expand VPS beacon/discovery surfaces:
    - `/for-agents`
    - `/for-ai-browsers`
    - `rs-ai-beacon.json`
    - `mcp-agent-card.json`
    - `answers.json`
    - `freshness.json`
    - stronger markdown mirrors
- Make VPS clearly advertise:
    - canonical source on shared hosting
    - machine-readable feeds
    - MCP transport
    - recommended crawl order
    - latest changes and freshness

Exit criteria:

- VPS works as a visible AI-facing beacon
- shared hosting remains canonical public source

### Phase 7. Agent Control Layer In Filament

Goal: make agents autonomous within strict guardrails and operable from the panel.

- Expose agents in Filament with:
    - run now
    - dry run
    - analyze only
    - execute allowed actions
    - audit trail
    - last run status
- Add explicit per-agent guardrails:
    - allowed actions
    - forbidden actions
    - automatic rollback rules
    - backup rules
    - review-required actions
- Status:
    - roster, persona, model lane and guardrails are now surfaced in `Diagnosta AI`
    - execution controls are still pending
- Add a dedicated optimization & maintenance lead agent for:
    - performance
    - low-risk package/app updates
    - technical hygiene
- Status:
    - `Diagnosta AI` now records first operator commands in cache-backed audit trail
    - no public apply path is live yet
    - first bounded summaries for SEO/AEO and maintenance are now generated in the operator loop
    - those summaries now see real source-data from published reports and DTC
    - recommendation snapshots are now persisted in `ops_agent_snapshots`
    - `run allowed lane` now generates execution previews with concrete recommendation items for review

Exit criteria:

- operators can run/review agents from Filament
- agents can work autonomously only inside defined rules

### Phase 8. Model Routing By Task

Goal: avoid using one expensive model for every workflow.

- `Gemini 3.1 Pro Preview`
    - repair-report analysis
    - case-study drafts
    - high-quality blog drafting
    - strategic SEO/AEO work
- `Gemini 3.1 Flash` / `Flash Lite`
    - extraction
    - batch SEO work
    - tagging
    - summaries
    - lighter admin automations
- `Imagen`
    - realistic article/report visuals
- `Claude Sonnet` / `Claude Opus`
    - optional premium review lanes only after quota is raised

Exit criteria:

- every workflow has an assigned model tier
- no heavy model is used by default for light batch tasks

### Phase 9. SEO Ops Agent

Goal: move from isolated content generation to controlled continuous SEO operations.

- Build controlled loop:
    - `GSC -> analysis -> Telegram approval -> controlled apply -> verification`
- Feed it with:
    - reports
    - blog
    - DTC hub
    - AI discovery surfaces
- Add content refresh scoring and answer/freshness scoring.

Exit criteria:

- SEO ops can suggest and apply bounded improvements with human approval

### VPS — programy do dodania (darmowe, 2026+, wow)

Goal: rozbudowa VPS o darmowe, top 2026+ narzędzia przy zachowaniu dyrektyw (backup i rollback w gotowości, produkcja bezpieczna, tylko OSS). Pełna specyfikacja: `D:\gravity-agent\PLAN-VPS-PROGRAMY-2026.md`.

1. **Backup VPS (dyrektywa backup/rollback)**
    - Restic lub BorgBackup: szyfrowane backupy `/srv` (i wybranych volumów) do zdalnego repozytorium lub drugiego dysku.
    - Cron + retencja (np. 7 dni); obowiązkowa próba restore.
    - Reguła `cp plik plik.bak_*` przed każdą zmianą na VPS pozostaje.

2. **Monitoring i metryki (wow)**
    - Prometheus — zbieranie metryk (Docker, Caddy, aplikacja, Postgres).
    - Grafana — dashboards na Prometheus; opcjonalnie Loki (logi).
    - Uptime Kuma pozostaje pod HTTP/port checks.

3. **Zarządzanie Dockerem (wow)**
    - Portainer Community Edition (UI do kontenerów/stosów) lub Dozzle (logi w czasie rzeczywistym).
    - Dostęp tylko z localhost lub VPN.

4. **Bezpieczeństwo (wow)**
    - Fail2ban lub CrowdSec na hoście — ochrona SSH i usług przed brute-force.

5. **Opcjonalnie**
    - Loki (agregacja logów); ekspozycja `/metrics` dla przyszłych agentów ops.

Kolejność: (1) Backup VPS, (2) Prometheus + Grafana, (3) Portainer/Dozzle, (4) Fail2ban/CrowdSec. VPS pozostaje async producer i warstwa DTC/AI; hosting = canonical.

## Goal

Build RS Performance into an AI-agent-first local authority:

- fast and stable production
- live Vertex control plane in Filament
- AI reports that can read attachments and publish usable case studies
- AI reviews and SEO enrichment
- VPS as backup, monitoring and automation node
- n8n ready for controlled workflows
- public surfaces that act like a lighthouse for AI agents and AI browsers

## Status Summary

### Done

- Production healthy: homepage HTTP 200, Laravel 12.43.1, PHP 8.5.3
- Shared-host runtime smoke closed: homepage, blog, repair reports, services and problems return HTTP 200
- VPS support stack healthy: `auto`, `status`, `analytics`, `mcp`
- n8n reinstalled, licensed and API key verified
- Vertex live catalog enabled in Automation Center with sane model filtering
- Repair report AI agent foundation deployed for:
    - reports
    - reviews
    - seo
- Repair reports support attachments
- AI pack can generate content from `txt`, `docx` and `pdf`
- OCR for image attachments via Vertex parser deployed and live-verified
- Parser-first hydration for mixed attachments improved and live-verified
- Bulk AI actions added in Filament for repair reports
- Title and slug policy fixed for published repair reports
- Public report flow verified on live report `ID 3`
- Blog Telegram flow is live:
    - draft webhook
    - pipeline run endpoint
    - Telegram bot delivery
    - manual Telegram commands
- MCP repair report search fix deployed and live-verified

### In progress

- Agent-first report publication flow with richer AI discovery artifacts
- Reviews and SEO enrichment beyond the current baseline
- AI discovery lighthouse expansion for report-specific publish surfaces
- VPS backup automation and n8n repair-report bridge verification
- DTC / fault-code knowledge-base planning for AI agents and AI browsers

### Pending

- SEO Ops Agent controlled flow: `GSC -> analysis -> Telegram approval -> controlled apply -> verification`
- VPS backup architecture with retention and verification
- VPS programy (darmowe, 2026+, wow): Restic/Borg backup, Prometheus+Grafana (+ opcjonalnie Loki), Portainer/Dozzle, Fail2ban/CrowdSec — zgodnie z `D:\gravity-agent\PLAN-VPS-PROGRAMY-2026.md`
- AI crawler policy matrix and agent traffic telemetry
- n8n workflow bridge for repair report automation
- review workflow orchestration
- SEO enrichment post-publish automation
- markdown mirrors and freshness surfaces for reports
- DTC import pipeline from GitHub-backed repositories into VPS knowledge storage
- answer feed / provenance feed / freshness feed for AI browsers and AI agents

## Workstreams

### 1. Production and VPS

Status: in progress

- Keep production stable at HTTP 200 before any visual work
- Keep config, routes, events and views cached after each production batch
- Use VPS for backups, monitoring, n8n and support services
- Add repeatable DB/file backup jobs with restore path verification

### 2. Vertex Live Control Plane

Status: done

- Live model listing from Vertex in Filament
- Curated filtering to useful Google and Anthropic models
- Routing stored in site settings

### 3. Repair Report AI Engine

Status: in progress

- Attachment ingestion:
    - txt: done
    - docx: done
    - pdf: done
    - image OCR through Vertex: done and live-verified
- AI channels:
    - reports: done
    - reviews: done
    - seo: done
- Panel actions:
    - single record AI pack: done
    - single record AI pack and publish: done
    - bulk AI pack: done
    - bulk AI pack and publish: done
- Remaining:
    - callback of VPS publication drafts back to hosting
    - report discovery artifacts after publication
    - markdown mirrors and freshness surfaces

### 4. Reviews and SEO

Status: in progress

- Review response draft generation: done
- SEO title/description/keywords generation: done
- Blog schema and page-specific JSON-LD: done
- Stronger internal link targets and FAQ candidates: pending
- Post-publish SEO artifact generation: pending
- SEO Ops Agent track: not started yet

### 5. AI Discovery Lighthouse

Status: in progress

- `llms.txt`, `llms-full.txt`, feeds and MCP card already exist
- VPS beacon layer is live for `mcp.rs3d.pl`
- Need report-specific AI discovery surfaces after publish
- Need bot telemetry and crawl governance
- Need dedicated AI lighthouse surfaces on VPS:
    - `/for-agents`
    - `/for-ai-browsers`
    - `answers.json`
    - `freshness.json`
    - stronger machine-readable repair-report and blog exports

### 7. DTC Knowledge Base

Status: planned

- Goal:
    - add a VPS-hosted diagnostic trouble code knowledge layer as source material for support-plane, blog, SEO/AEO, AI agents and AI browsers
- Recommended source mix:
    - `OBDb` as the long-lived, community-maintained, vehicle/make-aware upstream with active 2026 updates and CC BY-SA licensing
    - `todrobbins/dtcdb` as a lightweight generic fallback dataset under MIT for basic generic OBD-II mappings
    - optional evaluation target: `Wal33D/dtc-database` because it exposes a larger SQLite-oriented corpus, but it needs direct repo/license verification before production use
- Planned VPS shape:
    - scheduled importer job pulling upstream GitHub data into normalized local storage
    - normalized tables or artifacts for:
        - code
        - code family
        - generic vs manufacturer-specific
        - make / model applicability
        - short meaning
        - diagnostic notes
        - source repo and freshness metadata
- Planned uses:
    - support-plane analysis enrichment for repair reports
    - internal blog and report drafting
    - AEO answer blocks and code-specific landing answers
    - machine-readable knowledge surfaces for AI browsers and agents
- Constraints:
    - do not present imported GitHub text blindly as canonical expert advice
    - every imported record must retain source attribution, freshness and confidence level
    - high-risk repair recommendations still require human-reviewed RS-specific interpretation

### 6. n8n Automation Layer

Status: ready for next phase

- Clean install complete
- License active
- API key verified
- Blog pipeline foundation is live on production and VPS
- Next step is controlled workflow creation from live app events

## Batch Log

### 2026-03-10

- Enabled Vertex live catalog and report/review/seo agent foundation.
- Deployed AI pack workflow for repair reports from attachments.
- Fixed report title and slug policy; published report `ID 3`.
- Added PDF parsing and bulk AI actions.
- Added Vertex image OCR path for report attachments.
- Added master plan governance in `plan.md` plus `start.md` trigger note.
- Live-verified image OCR on production with a synthetic smoke test report.
- Live-verified parser-first hydration merge from multiple image attachments.

### 2026-03-11 01:10-01:18 CET

- Blog automation foundation went live on production and VPS.
- Added protected blog draft webhook and `config/blog.php`.
- Imported workflow `RsBlogAi031126 | RS Blog AI Draft Pipeline` into production n8n.
- Verified `POST /api/blog/pipeline/run` with successful draft creation.
- Activated Telegram branch with real chat id `6534705697`.
- Verified live `sendMessage` delivery to `@rsperformance_bot`.
- Added Telegram commands:
    - `/blog`
    - `/blog temat: ...`
    - `/blog premium temat: ...`
    - `/status`

### 2026-03-11 01:39 CET

- Runtime smoke was closed on shared hosting production and VPS.
- Public URLs `/`, `/blog`, article page, `/raporty-napraw`, `/uslugi` and `/problemy` returned `HTTP 200`.
- `php85 artisan watchdog:run --window-minutes=10 --error-threshold=5` returned `All systems operational`.
- Fixed `app/Support/Ops/ContentInvalidationService.php` to remove `OPS_ARTIFACT_REFRESH_FAILED`.
- Follow-up verification on 2026-03-11 14:35 CET proved the incident is only partially fixed: fresh errors still come from `App/Support/Search/IndexNowSubmissionService::queue()` not being explicitly ignored or consumed.
- Fixed VPS postgres healthcheck so fresh logs stopped spamming the missing `rsops` database error.

### 2026-03-11 01:55 CET

- Expanded the AI lighthouse layer on shared hosting and VPS beacon endpoints.
- Regenerated AI discovery artifacts and enabled beacon handling before the MCP reverse proxy.
- Verified `/.well-known/ai-resources.json`, `/feeds/content.json`, `llms.txt`, `llms-full.txt` and VPS beacon URLs on production.

### 2026-03-11 02:14 CET

- Fixed watchdog false alarms by filtering self-noise and operator/manual false positives, then counting actionable error fingerprints.
- Verified `php85 artisan watchdog:run -vvv` returns `All systems operational` on production.
- Moved blog schema generation into `RsSchemaFactory` and `SeoMetaData`.
- Blog index and blog article now emit page-specific JSON-LD on production.
- Automation Center now exposes content health, AI workflows, AI crawler analytics and response-cache hit-rate from live snapshots.

### 2026-03-11 02:31 CET

- Fixed `app/Http/Controllers/Api/RepairReportMcpController.php` after live `500` on `/api/mcp/repair-reports/search`.
- Root cause was unsafe handling of nested `fault_codes` data that triggered `Array to string conversion`.
- Search now safely flattens mixed `fault_codes` payloads into string lists before filtering and response serialization.
- Kept ASCII normalization for Polish queries, so `ladowania` matches `ladowania`-style data in production.
- Verified production REST endpoint with MCP header: `GET /api/mcp/repair-reports/search?query=ladowania&limit=2` -> `200`, `match_count=2`.
- Verified MCP client path through `fastmcp.Client("https://mcp.rs3d.pl")` and tool call `search_repair_reports(query="ladowania", limit=2)` -> success.

### 2026-03-11 06:45 CET

- Shared hosting got the first real repair-report publish contract:
    - new webhook controller for repair reports
    - new config `config/repair_reports.php`
    - migration adding `internal_link_targets` and `faq_candidates`
    - AI SEO channel now returns and saves internal link targets plus FAQ candidates
    - report page now renders extra internal-link section from the publish contract
- AI discovery layer for reports was expanded:
    - new static artifact `public_html/feeds/repair-reports.json`
    - AI resources, content index, head links and artifact snapshots now include the report feed
    - search artifacts were regenerated successfully on production
- VPS backup layer was deployed:
    - `/srv/ops-stack/bin/backup_ops_stack.sh`
    - `/srv/ops-stack/bin/verify_restore_ops_stack.sh`
    - cron added for nightly backup and restore verification
    - live restore smoke passed against the latest backup after fixing the verification script
- n8n repair-report bridge foundation is live on VPS:
    - workflow `RS Repair Report Publish Bridge` imported and published
    - production webhook path registered in n8n
    - shared-host `.env` now points `REPAIR_REPORTS_AUTOMATION_WEBHOOK_URL` to the real production n8n webhook path
    - smoke POST to the bridge returns `200` with `Workflow was started`
- Open issue from this batch:
    - latest row in `n8n.execution_entity` for workflow `RsRepairReports031126` is still `status=error`
    - this means the bridge starts, but downstream execution still needs debugging before the batch is fully closed

### 2026-03-11 06:52 CET

- Verified shared hosting is still healthy:
    - `https://rsperformance.online` -> `200`
    - `https://rsperformance.online/feeds/repair-reports.json` -> `200`
    - `php85 artisan watchdog:run --window-minutes=10 --error-threshold=5` -> `All systems operational`
- Narrowed the n8n repair-report bridge failure in multiple steps:
    - initial blocker was env access denial in expressions
    - then `JSON parameter needs to be valid JSON`
    - then `URL parameter must be a string, got undefined`
    - current blocker is Telegram API `400 Bad Request: message text is empty`
- Reimported and republished `RS Repair Report Publish Bridge` on VPS multiple times.
- Final active export from n8n now shows `Send Telegram Alert` using `sendQuery=true`.
- Latest live smoke still ends with `execution_entity.id=9`, `status=error`, but the error is now isolated to Telegram message text generation.

### 2026-03-11 06:55 CET

- Created VPS backup before the final workflow edit:
    - `/srv/ops-stack/n8n/rs-repair-report-bridge.json.bak_cli_bridge_static_text_20260311`
- Applied a production-safe fallback in `RS Repair Report Publish Bridge`:
    - Telegram `text` was switched to a static notification string
    - workflow was reimported, published and the n8n container was restarted
- Live verification:
    - smoke POST to `https://auto.rs3d.pl/webhook/RsRepairReports031126/repair-report-webhook/rs-repair-reports` returned `200` with `Workflow was started`
    - latest `execution_entity.id=10` is `success`
    - `execution_data` shows Telegram response `ok:true`, `message_id=12`
    - shared hosting still healthy: homepage `200`, `feeds/repair-reports.json` `200`
- Operational outcome:
    - the bridge is now operational end-to-end

### 2026-03-11 22:36 CET

- Verified Vertex auth on VPS for project `diagnosta-489719` using service account `vertex-express@diagnosta-489719.iam.gserviceaccount.com`.
- Live smoke results:
    - `AnthropicVertex(... model='claude-opus-4-6')` reaches Vertex but returns `429 RESOURCE_EXHAUSTED`
    - `google-genai` with `model='gemini-3.1-pro-preview'` returns `VERTEX_GEMINI_OK`
- `rs-support-plane` now defaults to `gemini-3.1-pro-preview` for support-plane analysis and publish tasks.
- Fixed Vertex routing for preview models by switching support-plane to `VERTEX_LOCATION=global` and using the global `aiplatform.googleapis.com` endpoint path when location is `global`.
- Added a second VPS artifact worker stage:
    - input: completed `repair_report.analysis_output`
    - output: completed `repair_report.case_study_draft`
- New scheduler command:
    - `support-publication-drafts:run --limit=25`
- Controlled intake smoke for trace `repair-report-pending-ai-9005` finished green:
    - `support_events.id=8` -> `completed`, `result_type=repair_report_job_queued`
    - `support_jobs.id=6` -> `completed`, `attempts=1`
    - `support_artifacts.id=7` -> `repair_report.analysis_input`, `completed`
    - `support_artifacts.id=10` -> `repair_report.analysis_output`, `completed`
    - `support_artifacts.id=12` -> `repair_report.case_study_draft`, `completed`
- Example publication draft output for artifact `12`:
    - public title: `BMW 320d (2016) - Nierówna praca silnika po zimnym rozruchu (Błędy P0401, P0101)`
    - SEO title: `BMW 320d nierówna praca po odpaleniu | Błędy P0401 P0101`
    - publication risk: `medium`
    - needs manual review: `true`
- Shared hosting stayed green during the batch:
    - `https://rsperformance.online` -> `200`
    - `https://rsperformance.online/blog` -> `200`
    - `php85 artisan watchdog:run --window-minutes=10 --error-threshold=5` -> `All systems operational`

### 2026-03-11 22:58 CET

- Installed `Google Cloud SDK 560.0.0` on VPS `vps72785462` from the official Google apt repository.
- Activated the existing service account key on VPS:
    - account: `vertex-express@diagnosta-489719.iam.gserviceaccount.com`
    - project: `diagnosta-489719`
- Verified `gcloud auth print-access-token` works on VPS.
- Verified the CLI does not unblock the Anthropic quota issue by itself; inference calls were already working and still fail on quota for Anthropic models.
- New operational finding:
    - the service account can mint access tokens and call model endpoints
    - the service account cannot inspect enabled services or quota through `gcloud services ...`
    - `cloudresourcemanager.googleapis.com` is disabled or inaccessible for this principal/project path
    - `serviceusage` listing returns `AUTH_PERMISSION_DENIED`
- Operational implication:
    - `gcloud` is now available for future Vertex work and easier curl/token tests
    - quota diagnostics from CLI need either broader IAM on the service account or a different operator principal
    - richer dynamic Telegram content can be restored later without blocking the automation track

### 2026-03-11 21:11 CET

- Fixed `app/Support/Ops/ContentInvalidationService.php` on the still-open path:
    - added `(void)` before `IndexNowSubmissionService::queue(...)`
    - earlier `(void)` fix for `SearchArtifactFactory::writeAll()` remains in place
- Created production backup before overwrite:
    - `app/Support/Ops/ContentInvalidationService.php.bak_cli_indexnow_void_20260311`
- Verification after deploy:
    - `php85 -l app/Support/Ops/ContentInvalidationService.php` -> OK
    - `php85 artisan optimize:clear && php85 artisan optimize` -> OK
    - `php85 artisan search:artifacts-generate` -> OK
    - `php85 artisan search:indexnow-flush` -> `IndexNow result: idle`, `Pending URLs: 0`
    - public smoke still green: `/`, `/blog`, `mcp.rs3d.pl/healthz`, `auto.rs3d.pl`, `analytics.rs3d.pl`
    - tail of `laravel.log` after deploy showed no new `OPS_ARTIFACT_REFRESH_FAILED`; latest confirmed entry remained `2026-03-11 13:32:16`
    - one scheduler observation cycle later, `php85 artisan watchdog:run --window-minutes=10 --error-threshold=5` still returned `All systems operational` with `0` actionable errors in the last 10 minutes

### 2026-03-11 21:20 CET

- Stage 2 foundation started on VPS without touching the public frontend runtime.
- Created a separate Laravel workspace:
    - `/srv/workspaces/rs-support-plane`
    - base framework: Laravel `12.54.1`
    - PHP in the composer container: `8.5.3`
- Installed foundation packages:
    - `laravel/ai v0.2.8`
    - `laravel/mcp v0.6.2`
    - `laravel/scout v11.0.0`
    - `laravel/horizon v5.45.3`
    - `laravel-workflow/laravel-workflow 1.0.70`
    - `laravel-workflow/waterline 1.0.15`
- Added local vector DB runtime on the VPS:
    - `qdrant/qdrant:latest`
    - compose service added to `/srv/ops-stack/compose/docker-compose.yml`
    - backup created: `/srv/ops-stack/compose/docker-compose.yml.bak_cli_qdrant_20260311`
- Verification:
    - `docker run --rm -v /srv/workspaces/rs-support-plane:/app -w /app composer php artisan about` -> OK
    - `compose-qdrant-1` is up on `127.0.0.1:6333-6334`
    - `curl http://127.0.0.1:6333/healthz` -> `healthz check passed`
    - public frontend remained unaffected

### 2026-03-11 21:22 CET

- Built the first non-critical shared-host -> support-plane contract inside `rs-support-plane`.
- Added:
    - `SupportEvent` model
    - `support_events` table
    - `Api/Internal/SupportEventController`
    - `routes/api.php`
    - API routing in `bootstrap/app.php`
    - `SUPPORT_PLANE_INGEST_SECRET` config wiring
- Contract behavior:
    - endpoint: `POST /api/internal/support-events`
    - verifies HMAC via `X-RS-Signature`
    - stores idempotent events by `idempotency_key`
    - returns `202 Accepted` for newly created events
- Verification:
    - `php artisan route:list` shows the route
    - migration ran successfully
    - local VPS HTTP smoke returned `202` with event payload persisted as `event_id=1`
    - public frontend remained unaffected

### 2026-03-11 21:27 CET

- Added a safe sender on shared hosting for the first mirrored support-plane event.
- New production-side pieces:
    - `app/Support/Ops/SupportPlaneEventSender.php`
    - `AppServiceProvider` singleton registration
    - `RepairReportAiWorkflowService::generatePack()` now attempts to mirror `repair_report.pending_ai`
    - `config/ops.php` now contains `support_plane` settings
- Safety model:
    - default state is off via `RS_SUPPORT_PLANE_ENABLED=false`
    - no public rendering path depends on the sender
    - if VPS is unavailable or signing fails, hosting only logs a warning and continues
- Verification:
    - `php85 -l` OK for all changed files
    - `php85 artisan optimize:clear && php85 artisan optimize` OK
    - public smoke still green

### 2026-03-11 21:38 CET

- Activated the shared-host sender with production env wiring:
    - `RS_SUPPORT_PLANE_ENABLED=true`
    - `RS_SUPPORT_PLANE_INGEST_URL=https://auto.rs3d.pl/support-plane/api/internal/support-events`
    - `RS_SUPPORT_PLANE_INGEST_SECRET` set to the VPS intake secret
    - `RS_SUPPORT_PLANE_TIMEOUT_SECONDS=4`
- Created hosting backup before the env change:
    - `.env.bak_cli_support_plane_env_20260311`
- Verified the end-to-end mirror path:
    - sender smoke on shared hosting returned `true`
    - VPS `support_events.id=2` persisted with:
        - `event_type=smoke.shared_host`
        - `idempotency_key=shared-host-smoke-20260311-2039`
        - `source=shared-host`
        - `status=received`
- Post-activation safety checks stayed green:
    - `php85 artisan optimize:clear && php85 artisan optimize` OK
    - `php85 artisan watchdog:run --window-minutes=10 --error-threshold=5` -> `All systems operational`
    - public frontend still healthy

### 2026-03-11 21:43 CET

- Added the first durable VPS-side processor for mirrored support-plane events.
- VPS code changes:
    - new migration `2026_03_11_214500_add_processing_fields_to_support_events_table.php`
    - new service `app/Support/SupportEventProcessor.php`
    - new command `app/Console/Commands/ProcessSupportEventsCommand.php`
    - updated `app/Models/SupportEvent.php`
    - updated `routes/console.php`
- Runtime behavior:
    - `support-events:process` now marks `received -> processing -> completed|failed`
    - durable result fields now exist on `support_events`:
        - `processing_started_at`
        - `result_type`
        - `result_payload`
        - `last_error`
    - scheduler now runs `php artisan support-events:process --limit=25` every minute
- Live verification:
    - `php artisan migrate --force` OK
    - syntax checks for new PHP files OK
    - `php artisan support-events:process --limit=10` -> `processed=2 completed=2 failed=0`
    - `support_events.id=1` -> `completed`, `result_type=repair_report_mirror_ack`
    - `support_events.id=2` -> `completed`, `result_type=smoke_ack`
    - shared hosting stayed green: homepage `200`, blog `200`, watchdog green

### 2026-03-11 21:48 CET

- Added the first real downstream path for `repair_report.pending_ai` on VPS.
- VPS code changes:
    - new migration `2026_03_11_215500_create_support_jobs_table.php`
    - new model `app/Models/SupportJob.php`
    - updated `app/Support/SupportEventProcessor.php`
- Runtime behavior:
    - `repair_report.pending_ai` no longer ends with plain ack only
    - the processor now creates a durable `support_job` with:
        - `job_type=repair_report.analysis`
        - `status=queued`
        - `trace_id=<event idempotency key>`
    - `support_events.result_type` becomes `repair_report_job_queued`
- Live verification:
    - migration for `support_jobs` passed
    - controlled sender on shared hosting returned `true`
    - fresh mirrored event `support_events.id=3` was processed to:
        - `status=completed`
        - `result_type=repair_report_job_queued`
    - new durable row `support_jobs.id=1` exists with:
        - `support_event_id=3`
        - `job_type=repair_report.analysis`
        - `status=queued`
        - `entity_id=9001`
    - shared hosting stayed green: homepage `200`, blog `200`, watchdog green

### 2026-03-11 21:51 CET

- Added the first runner for queued support-plane jobs.
- VPS code changes:
    - new service `app/Support/SupportJobRunner.php`
    - new command `app/Console/Commands/ProcessSupportJobsCommand.php`
    - updated `routes/console.php`
- Runtime behavior:
    - `support-jobs:run` now advances `queued -> running -> completed|failed`
    - for `repair_report.analysis`, it stores a minimal local analysis artifact in `support_jobs.result_payload`
    - scheduler now runs both:
        - `php artisan support-events:process --limit=25`
        - `php artisan support-jobs:run --limit=25`
        - both every minute
- Live verification:
    - syntax checks for the new runner files passed
    - `php artisan list | grep support-jobs` shows the new command
    - `php artisan support-jobs:run --limit=10` -> `processed=1 completed=1 failed=0`
    - `support_jobs.id=1` moved to `completed`
    - `support_jobs.id=1.result_payload.analysis_artifact` now contains:
        - title
        - vehicle label
        - recommended next step
        - admin edit URL
    - shared hosting stayed green: homepage `200`, blog `200`, watchdog green

### 2026-03-11 21:55 CET

- Enriched the shared-host mirror payload and VPS-side artifact for `repair_report.analysis`.
- Shared-host code changes:
    - updated `app/Support/RepairReports/RepairReportAiWorkflowService.php`
    - mirror payload now includes richer source fields such as:
        - `vehicle_year`
        - `diagnostic_tool`
        - `reported_problem`
        - `root_cause`
        - `risk_level`
        - `seo_title`
        - `seo_description`
        - `fault_codes`
        - `raw_report_excerpt`
        - `analysis_excerpt`
        - `recommended_repair_excerpt`
        - `attachments_count`
- VPS code changes:
    - updated `app/Support/SupportJobRunner.php`
- Runtime behavior:
    - enriched artifacts now include:
        - `priority`
        - `vehicle_profile`
        - `source_snapshot`
        - `technical_snapshot`
        - `seo_snapshot`
        - `routing_hints`
        - `checklist`
- Live verification:
    - shared-host syntax and optimize rebuild passed
    - controlled sender for report `9002` returned `true`
    - `support_events.id=4` -> `completed`, `result_type=repair_report_job_queued`
    - `support_jobs.id=2` -> `completed`, `attempts=1`
    - enriched artifact for `support_jobs.id=2` shows:
        - `priority=high`
        - `vehicle_profile.label=BMW 520d 2.0 diesel`
        - `source_snapshot.fault_codes=['P0299','boost-pressure-low']`
        - `technical_snapshot.reported_problem` and `root_cause`
        - `seo_snapshot`
        - `routing_hints.needs_manual_review=true`
    - shared hosting stayed green: homepage `200`, blog `200`, watchdog green

### 2026-03-11 21:59 CET

- Added the first dedicated AI-ready artifact record on VPS.
- VPS code changes:
    - new migration `2026_03_11_220000_create_support_artifacts_table.php`
    - new model `app/Models/SupportArtifact.php`
    - updated `app/Support/SupportJobRunner.php`
- Runtime behavior:
    - when `repair_report.analysis` finishes, the runner now stores:
        - `support_jobs.result_payload`
        - and a separate `support_artifacts` row with:
            - `artifact_type=repair_report.analysis_input`
            - `status=ready`
            - normalized `analysis_context`
            - `execution_hints`
            - `quality_gates`
            - `source_links`
- Live verification:
    - migration for `support_artifacts` passed
    - controlled sender for report `9003` returned `true`
    - `support_events.id=5` -> `completed`, `result_type=repair_report_job_queued`
    - `support_jobs.id=3` -> `completed`, `attempts=1`
    - `support_artifacts.id=1` created with:
        - `artifact_type=repair_report.analysis_input`
        - `status=ready`
        - `trace_id=repair-report-pending-ai-9003-manual-20260311-2200`
    - artifact payload includes:
        - `analysis_context.problem_headline`
        - `analysis_context.vehicle_profile`
        - `analysis_context.source_snapshot`
        - `analysis_context.technical_snapshot`
        - `execution_hints`
        - `quality_gates`
    - shared hosting stayed green: homepage `200`, blog `200`, watchdog green

### 2026-03-11 22:02 CET

- Added the first artifact worker on VPS for `repair_report.analysis_input`.
- VPS code changes:
    - new service `app/Support/SupportArtifactRunner.php`
    - new command `app/Console/Commands/ProcessSupportArtifactsCommand.php`
    - updated `routes/console.php`
- Runtime behavior:
    - `support-artifacts:run` now processes `repair_report.analysis_input`
    - input artifacts move `ready -> processing -> completed`
    - the worker creates a second artifact:
        - `artifact_type=repair_report.analysis_output`
        - `status=completed`
        - `metadata.worker_stage=vps_ai_preparation_v1`
    - scheduler now runs all three VPS lanes every minute:
        - `support-events:process --limit=25`
        - `support-jobs:run --limit=25`
        - `support-artifacts:run --limit=25`
- Live verification:
    - syntax checks for the new artifact worker files passed
    - `php artisan list | grep support-artifacts` shows the new command
    - controlled sender for report `9004` returned `true`
    - `support_events.id=6` -> `completed`
    - `support_jobs.id=4` -> `completed`, `attempts=1`
    - `support_artifacts.id=2` -> `repair_report.analysis_input`, `status=completed`
    - `support_artifacts.id=4` -> `repair_report.analysis_output`, `status=completed`
    - output artifact payload includes:
        - `analysis_brief`
        - `analysis_plan`
        - `handoff_bundle`
    - shared hosting stayed green: homepage `200`, blog `200`, watchdog green

### 2026-03-11 23:08 CET

- Enabled `cloudresourcemanager.googleapis.com` in project `diagnosta-489719` from VPS using the existing service account.
- Verified project-level administration is now working from VPS:
    - `gcloud projects get-iam-policy diagnosta-489719` -> success
    - `gcloud services list --enabled` -> success
    - `gcloud alpha services quota list --service=aiplatform.googleapis.com` -> success
- Confirmed current service account `vertex-express@diagnosta-489719.iam.gserviceaccount.com` already has broad roles, including:
    - `roles/owner`
    - `roles/aiplatform.admin`
    - `roles/aiplatform.user`
    - `roles/aiplatform.expressAdmin`
    - `roles/browser`
    - `roles/viewer`
    - `roles/serviceusage.serviceUsageAdmin`
    - `roles/serviceusage.serviceUsageViewer`
    - `roles/serviceusage.serviceUsageConsumer`
    - `roles/cloudquotas.admin`
    - `roles/servicemanagement.quotaAdmin`
- Re-running `add-iam-policy-binding` for `roles/aiplatform.admin` succeeded but was redundant because the binding already existed.
- Operational conclusion:
    - no further IAM role additions are currently needed for self-service Vertex administration from this service account
    - remaining Anthropic blocker is quota, not permissioning

### 2026-03-12 00:12 CET

- Connected the VPS AI lighthouse concept to the main product plan instead of treating it as a side project.
- New planning direction:
    - VPS becomes the AI-facing discovery and knowledge beacon
    - shared hosting remains the canonical public content source and review surface
- Added a dedicated DTC knowledge-base workstream for VPS.
- Repository assessment for DTC sources:
    - `OBDb` looks strongest as the primary upstream because it is active in March 2026, community-maintained and organized around vehicle makes/models
    - `todrobbins/dtcdb` is useful as a generic MIT-licensed fallback but appears much smaller and less active
    - `Wal33D/dtc-database` looks promising from metadata because of the larger SQLite corpus, but needs direct repo/license validation before production adoption
- AEO/SEO implication:
    - DTC knowledge is not a standalone dataset; it feeds repair reports, answer blocks, provenance, freshness surfaces and blog content
    - modern AEO should focus on answer-first structures, provenance and freshness, not on over-investing in FAQ/HowTo rich-result tricks

## Next Actions

1. Optionally run one broader smoke for shared hosting, report webhook, artifacts feed, n8n webhook and VPS backup/restore after the green bridge confirmation.
2. Resume the planned SEO Ops Agent track: `GSC -> analysis -> Telegram approval`.
3. In a separate safe batch, enrich the Telegram notification body from the current static fallback back to dynamic content.
4. Verify Google API and Search Console access for the service account used by the planned SEO Ops Agent.
5. Add bot telemetry and crawl governance for report surfaces.

## 2026-03-13 07:19 CET - FILAMENT POLONIZATION STATUS

- active Filament operator surfaces are now standardized to Polish labels
- covered surfaces:
    - `Diagnosta AI`
    - `AutomationCenter / Command Center`
- completed scope:
    - action buttons
    - section headings
    - telemetry counters
    - artifact/packet inspectors
    - review/export labels
- rule now treated as active UI standard:
    - all visible Filament buttons and operator-facing labels should use Polish names
- known runtime note:
    - `optimize:clear/optimize` can still trigger a brief transient `routes-v7.php` 500 during cache turnover
    - final post-rebuild runtime remains green

## 2026-03-13 07:34 CET - AUTOMATION CENTER HARDENING

- `AutomationCenter` no longer depends on successful live Vertex publisher listing to render
- `VertexModelCatalogService` now falls back gracefully to the curated catalog when publisher listing fails
- current active rule:
    - Vertex catalog/network problems may degrade model freshness in the panel
    - but they must not crash Filament operator pages

## 2026-03-13 07:40 CET - BLOG DRAFT GENERATION ENTRYPOINT

- Filament blog management now has a dedicated operator entrypoint for AI drafting
- active behavior:
    - `Generuj artykul` in blog list opens a modal
    - optional brief/topic can be passed by the operator
    - existing `BlogVertexPipelineService` creates a draft for review
    - success notification includes a direct edit link to the generated draft
- current UI rule:
    - AI generation entrypoints in Filament should create reviewable drafts, not bypass editorial verification

## 2026-03-13 07:52 CET - REPORT DRAFT GENERATION ENTRYPOINT

- Filament repair-report management now has a dedicated operator entrypoint for AI drafting
- active behavior:
    - `Generuj raport AI` in repair reports list opens a modal
    - operator passes a brief or raw diagnostic notes
    - the system creates a new draft report and immediately runs `RepairReportAiWorkflowService`
    - success or warning notification includes a direct `Otworz draft` link
- current UI rule:
    - report-side AI generation entrypoints also create reviewable drafts and keep the existing approve/reject/publish workflow intact

## 2026-03-13 07:59 CET - REPAIR REPORT PANEL POLISH UX

- repair-report review and queue handling in Filament is now fully aligned with the Polish operator UI standard
- covered scope:
    - queue labels
    - review actions
    - bulk actions
    - filter labels
- current UI rule:
    - operator-facing report review and AI workflow labels should remain Polish and consistent with the blog/operator surfaces

## 2026-03-13 08:31 CET - COMMAND CENTER AI QUEUES

- `AutomationCenter` now exposes a shared AI queue board for:
    - blog drafts
    - repair-report drafts
- active behavior:
    - blog counters show:
        - `Szkice`
        - `Szkice AI`
        - `Opublikowane`
    - repair-report counters show:
        - `Szkice`
        - `Do weryfikacji AI`
        - `Zatwierdzone AI`
        - `Opublikowane`
    - both lanes list the newest drafts with direct `Otworz` actions
- current UI rule:
    - site-level command center should expose AI backlogs for core editorial/reporting workflows, not only infra and search telemetry

## 2026-03-13 08:39 CET - COMMAND CENTER PRIORITIES AND QUICK ACTIONS

- `AutomationCenter` now escalates AI backlog priorities when:
    - blog AI drafts are waiting
    - repair-report AI drafts are pending review
- active behavior:
    - `Centrum dowodzenia` includes backlog-aware priority cards
    - `Szybkie akcje operatora` now link directly to:
        - blog AI workflow
        - repair-report AI workflow
        - `Diagnosta AI`
        - public DTC hub
- current UI rule:
    - site-level command center should not only display telemetry; it should also route the operator into the right workflow with one click

## 2026-03-12 21:30 CET - ACTIVE STATUS UPDATE

- Part 1 / `Public Foundation`:
    - effectively live and stable
    - DTC hub, widget, manufacturer/family/combo slices, strongest feed, `llms`/`ai-resources`/sitemaps and modern SEO/AEO foundations are already deployed
    - architectural note:
        - modern AEO on this project explicitly means:
            - answer-first blocks
            - provenance
            - freshness
            - entity framing
            - AI-readable discovery/export
        - not just FAQ schema or longer informational copy
- Part 2 / `Autonomous Growth`:
    - live in `Diagnosta AI`
    - internal review/export chain covers:
        - `seo_strategist`
        - `aeo_strategist`
        - `technical_journalist`
        - `visual_photographer`
        - `fact_checker`
        - `editor_in_chief`
        - `workshop_diagnostician`
    - persisted snapshots, review workflow, low-risk maintenance apply lane, internal exports, private artifact history/inspector/download, approved chain packages, package filters, coverage matrix, package comparison, quick-compare shortcuts, package strength summary and private `Search Ops packet` export are live
    - `Search Ops` lane already has telemetry and a dedicated inspector for answer/provenance/freshness/AI-readability targets
    - VPS publication-draft lane already has retry/backoff, status telemetry and stale metadata cleanup
    - daily cache rebuild now uses `ops:safe-optimize` instead of raw `optimize`
    - operator entrypoints are now symmetrical in Filament:
        - blog list -> `Generuj artykul`
        - repair reports list -> `Generuj raport AI`
    - repair-report review UI is now also aligned to the same Polish operator standard
- Safest next steps:
    1. deeper operator ergonomics on snapshots/packages and packet inspection
    2. widen private `Search Ops packet` toward controlled `GSC -> analysis -> approval -> apply -> verify`
    3. only later: public apply lanes behind separate review gates

> LEGACY PLAN NOTE (2026-03-29)
> This plan contains historical Laravel 12 entries. Current verified runtime is Hosting = Laravel 13.1.1 / PHP 8.5.3 and VPS support-plane = Laravel 13.1.1 / PHP 8.5.3.
> Do not use old Laravel 12 notes here as source of truth without a fresh live check.

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

- Current batch: stage 0 AEO hardening as the first-class product priority, before further VPS support-plane expansion.
- Current objective: make AEO the strongest surface in the entire project under a strict `top of the top 2026+` standard, while keeping VPS as support-plane and hosting as canonical runtime.
- Current status: [-W TRAKCIE-] exact lookup metadata is live, DTC public routes are restored, **live hosting now registers `AiCitationHeaders` in `bootstrap/app.php` so `/kody-usterek` surfaces always emit `X-Citation-Policy` and `X-Preferred-Citation` (including generic fetcher UAs)**, VPS exact-first resolver returns canonical DTC/service/problem winners for exact intents, the invitation plane now exposes a real standards-first hospitality fast lane (`X-AI-Hospitality`, preferred fetch order, `for-agents`, `for-ai-browsers`) instead of only silent manifests, the weak-boost / underboost lane now resolves to the canonical problem page across multiple human symptom phrasings, clean telemetry now shows real homepage / priority-path shares instead of false zeroes, canonical discovery now exposes a homepage-exit map plus agent-specific first-hop hints for the homepage-heavy AI families, the homepage still emits exact answer-path hints in response headers, canonical `home.md` mirrors that same first-hop contract for markdown-consuming agents, the public UI no longer shows the technical answer-rail block, the exact Anthropic crawler identities blocked by shared-host `406` are now narrowly rescued to VPS again, the blog system now has a same-day `daily_news` quality-gated workflow on VPS `n8n` (`preview -> quality gate -> persist`) with Telegram operator reporting, hosting telemetry again writes to `ai_bot_visits` while shadow-capturing non-catalog AI-like user agents such as `DeepSeek-Unknown`, and the daily-news lane is now materially harder against junk because both Laravel and `n8n` enforce anti-bait editorial rules plus slot-specific `porada/news/premiera` routing before any draft can persist.
- Current blocker: the biggest remaining operational uncertainty is still the daily-news `n8n` lane proof, not A2A/AEO discovery. The hospitality layer is now live and green under smoke.
- Current next step: verify the next real scheduled run of `RS Daily Automotive News Drafts`, then decide whether to add richer A2A artifacts / support-plane search affordances on top of the now-green invitation layer.

### 2026-04-12 gateway chain + relay addendum

- Live LiteSpeed `.htaccess` gateway is **verified** after the `ChatGPT\x20Atlas` / `mod_rewrite` structure fix: `GPTBot` on canonical `/` performs **exactly one** `302` to `https://ai.rsperformance.online/` then **200**; direct `https://ai.rsperformance.online/` returns **200** for both bot and browser UAs (**no redirect loop**). Deep paths (example `/uslugi`) redirect to the matching path on `ai.*`. Relay docs updated the same day: `SESSION_LOG.md`, `HANDOFF.md`, `handoff-log.md`.

### 2026-04-09 blog-manual-lane addendum

- Manual Filament blog generation now also has:
    - retryable-provider failover to VPS support-plane
    - multi-pass editorial rewrite before persist for operator briefs
    - semantic topic-alignment folded into the quality gate
- New live proof on 2026-04-09:
    - manual fuel-prices brief no longer drifted into corrosion
    - latest corrected run finished as `draft_created`
    - created draft `#105`
    - title stayed on-topic: `Diesel za 7,83 zł w Gdańsku...`
    - hero image was generated: `blog/01KNR40N7N0ANSYGJTH32KMS0V.png`
- Latest hardening for the manual lane:
    - stage tracing appended directly to `storage/logs/blog-generate.log`
    - automatic editorial-window rescue now expands otherwise-valid underlength drafts before the final quality gate
    - Filament modal now defaults `dispatch_support_plane = true` for the main operator path
- Remaining blog lane proof still needed:
    - one fresh post-patch run with visible `BLOG_PIPELINE_STAGE` lines in `blog-generate.log`
    - verify whether support-plane dispatch is actually consumed end-to-end or still only acts as a soft fallback contract

### 2026-04-09 source-first hero addendum

- `premiera` / `news` blog slots on hosting now prefer source-first hero acquisition over AI image generation.
- The current contract is:
    - use approved `source_urls`
    - fetch source page
    - prefer `og:image` / `twitter:image` / strong article images
    - validate mime, byte size, dimensions
    - store locally
    - only then fall back to AI image models
- This is now the required direction for exact-model premiere coverage because generic AI hero images break trust and frequently miss the described vehicle.
- Current next refinement:
    - improve research quality so `source_urls` skew more strongly toward official manufacturer / press / newsroom pages and contain fewer mistyped or low-value domains.

## AEO Prime Directive 2026-03-21

This project treats AEO as the highest strategic priority.

Binding rule for all future agents:

- AEO is more important than classic SEO.
- AEO must always target `top of the top 2026+`.
- No agent may treat AEO as a secondary polish layer.
- Every major decision should be checked against the question:
    - does this improve discoverability, interpretability and trust for AI agents and AI browsers?

### What "top of the top 2026+" means here

- answer-first content architecture
- strong entity framing
- explicit provenance
- explicit freshness
- AI-readable discovery surfaces
- curated machine-readable exports
- MCP-ready capability surfaces
- canonical URL discipline
- no thin or noisy AI-facing surfaces
- no legacy SEO-first thinking that ignores AI agents

### AEO is the first refinement lane

From now on, the first strategic refinement lane in this plan is AEO hardening.
Only after that should agents expand lower-priority support infrastructure, unless the user explicitly reprioritizes or production stability is at risk.

## Stage 0. AEO Hardening - First Priority

Goal:

- make RS Performance visibly and structurally stronger for AI agents, AI browsers and answer engines than a normal SEO-first Laravel site

Required outcomes:

- all AI-facing surfaces become curated, deliberate and canonical
- all key content families expose answer, provenance, freshness and entity clarity
- discovery files stop being just "present" and become strategically curated
- AEO quality becomes part of editorial and architectural review, not an afterthought

### Stage 0A. Discovery Surface Curation

Required work:

- treat `llms.txt` as a curated routing document, not a dump
- keep only highest-value canonical URLs in `llms.txt`
- make `llms-full.txt` a structured knowledge export, not a noisy archive
- ensure `/.well-known/*`, `mcp.json`, `ai-plugin.json`, `mcp-agent-card.json` and related discovery surfaces stay aligned
- keep sitemap, llms surfaces and canonical routes in sync

Definition of done:

- discovery surfaces intentionally guide models toward the best public knowledge pages

### Stage 0B. Answer / Provenance / Freshness System

Required work:

- every key public content type should expose answer-first structure
- every key public content type should expose provenance
- every key public content type should expose freshness metadata
- provenance and freshness must not be cosmetic; they should be tied to real workflow state

Primary content types:

- services
- DTC hub and code pages
- repair reports
- blog articles

Definition of done:

- AI agents can easily answer:
    - what is the answer
    - why should I trust it
    - how current is it

### Stage 0C. Entity And Canonical Discipline

Required work:

- strengthen entity framing for services, manufacturers, DTC families, faults and repair scenarios
- keep internal linking aligned to entity clusters
- avoid duplicate or ambiguous surfaces that weaken AI interpretation
- keep canonical hosting routes as the authoritative public source

Definition of done:

- the project reads as a coherent knowledge graph, not a pile of pages

### Stage 0D. MCP And AI Interface Maturity

Required work:

- evolve MCP from "available" to "production-grade AI interface"
- keep tool boundaries clear
- expose relevant resources cleanly
- align AI discovery files with real MCP capabilities
- add auth / trust boundaries where needed without breaking AI usefulness

Definition of done:

- AI agents can reliably discover both public knowledge and controlled machine interfaces

### Stage 0E. Editorial AEO Gate

Required work:

- define AEO review checks for blog, reports, DTC and service content
- require AEO checks before calling work "done"
- validate:
    - answer-first block quality
    - provenance quality
    - freshness quality
    - entity clarity
    - discovery/export alignment

Definition of done:

- no new important content ships without passing an AEO gate

### Stage 0F. AEO Quality Standard For Agents

Every future agent should judge AEO quality using this order:

1. Is the page useful to an AI agent in one pass?
2. Is the answer explicit and fast to extract?
3. Is provenance explicit?
4. Is freshness explicit?
5. Is the entity context unambiguous?
6. Is the page linked from curated discovery surfaces?
7. Does it avoid thin, duplicate or noisy AI-facing content?

If the answer is "no" on any of the early questions, AEO is not top-tier yet.

## Canonical Architecture Directive 2026-03-21

This section is now the binding operating blueprint for every agent on this project.

### Non-negotiable architecture

- Shared hosting `rsperformance.online` remains the only canonical production runtime.
- Public website rendering, admin review, publish actions, source-of-truth database writes and operator UX stay on shared hosting.
- VPS exists only as real production support:
    - async execution
    - AI control plane
    - queue workers
    - artifact generation
    - search / knowledge / enrichment
    - monitoring and restore support
- No agent may redesign the stack into `VPS as main app server`.
- No agent may move the public website or canonical admin runtime from hosting to VPS.
- All new architecture decisions must target `Laravel 13.x + PHP 8.5` only.

### What VPS is supposed to do

- Execute long-running and heavy jobs that should never block shared-hosting HTTP requests.
- Protect shared hosting from AI latency, quota spikes, retries and duplicate work.
- Produce artifacts and send results back to hosting for review and publication.
- Hold support services that improve production reliability without becoming the canonical frontend.

### What hosting is supposed to do

- Accept public traffic and admin traffic.
- Persist canonical application state.
- Trigger async work.
- Review, approve, reject and publish results returned from VPS.
- Remain operational even when VPS is degraded; VPS loss may reduce automation, but must not make the site disappear.

## Target VPS Support Plane Blueprint

This is the concrete target state. Agents should build toward it in this order and avoid side quests outside this blueprint.

### Stage A. Async Control Plane

Goal:

- turn VPS into the official async execution layer for production support

Required outcomes:

- hosting no longer performs heavy AI/report/blog/image work inside interactive requests
- hosting creates intents/jobs only
- VPS executes jobs and reports completion back to hosting

Primary workloads:

- blog generation
- blog hero image generation
- repair-report AI generation
- DTC enrichment
- SEO/AEO exports
- feed crawling
- maintenance and cleanup jobs

Definition of done:

- every heavy workflow has a clear `dispatch on hosting -> execute on VPS -> return result to hosting` path

### Stage B. Queue Segmentation On VPS

Goal:

- separate critical business jobs from slower batch work

Required queues:

- `critical`
- `ai-high`
- `ai-low`
- `ops`

Queue meaning:

- `critical`:
    - callbacks to hosting
    - publication sync
    - approval follow-up jobs
    - state repair jobs required for operator workflows
- `ai-high`:
    - blog writer
    - premium review
    - blog image generation
    - repair report generation
- `ai-low`:
    - research
    - enrichment
    - summaries
    - embeddings
    - feed analysis
- `ops`:
    - cleanup
    - backup verification
    - artifact rebuilds
    - cache/search maintenance

Definition of done:

- Horizon workers are split by queue tier
- slow AI batch jobs can no longer starve critical production support jobs

### Stage C. Shared Redis Discipline

Goal:

- use Redis as the common state and protection layer between hosting and VPS

Required usage:

- queue backend
- atomic locks
- dedupe keys
- throttling buckets
- transient workflow state

Required Laravel 13 patterns:

- `WithoutOverlapping`
- `ThrottlesExceptions`
- cache locks
- queue priority routing

Definition of done:

- duplicate job dispatches are blocked
- repeated webhook/operator actions do not fork duplicate executions
- transient AI/API failures degrade cleanly instead of creating chaos

### Stage D. AI Gateway And Vertex Quota Governor

Goal:

- create one shared policy layer for all AI calls

The gateway must decide:

- which model is used for which task
- when `pro` is allowed
- when to fall back to `flash`
- retry/backoff policy
- cooldown after `429`
- concurrency per model
- priority of hosting-critical jobs over VPS background jobs

Required telemetry:

- request count by model
- retry count
- fallback count
- `429` count
- average execution time by task

Definition of done:

- model routing is no longer scattered across many unrelated code paths
- shared hosting can no longer lose quota priority to low-value VPS jobs

### Stage E. Filament Operations Surface

Goal:

- keep the operator in control from canonical hosting admin

Required operator views:

- queued jobs
- running jobs
- failed jobs
- stale jobs
- artifact status
- callback status
- used model / fallback used

Required operator actions:

- retry
- requeue to fallback tier
- cancel
- inspect artifact
- force sync to hosting

Definition of done:

- no VPS shell access is needed for normal production operations

### Stage F. Audit Spine

Goal:

- every support-plane action must be traceable

Every important workflow must record:

- who started it
- from where it was started
- payload summary
- queue name
- model used
- artifact produced
- callback result
- final status

Definition of done:

- agents and operators can reconstruct what happened without guesswork

### Stage G. Artifact Lifecycle

Goal:

- manage all generated assets as first-class production outputs

Artifact families:

- `blog/*`
- `reports/*`
- `seo/*`
- `aeo/*`
- `diagnosta/*`

Every artifact should have:

- owner job
- type
- version
- checksum
- visibility
- retention rule
- publication state

Definition of done:

- generated outputs are not random files; they are trackable production artifacts

### Stage H. Backup And Restore Verification

Goal:

- VPS must strengthen production recovery, not merely store copies

Required outcomes:

- hosting backups remain canonical
- VPS stores secondary copies and restore metadata
- restore path is tested, not assumed
- operator can see restore-readiness state

Definition of done:

- there is a verified restore path for DB + key artifacts + support-plane state

### Stage I. Search Plane

Goal:

- move heavy search and retrieval support to VPS after queues and AI governance are stable

Allowed direction:

- `Laravel Scout`
- `Meilisearch`

Primary search targets:

- DTC knowledge
- blog
- repair reports
- internal artifacts

Definition of done:

- hosting gains faster search/support capabilities without becoming search-infrastructure heavy

### Stage J. Realtime Only After Stability

Goal:

- evaluate realtime only if it improves operator workflows after the core async plane is stable

Allowed direction:

- `Laravel Reverb`

Allowed use cases:

- live job status
- progress updates
- operator dashboards

Not allowed:

- introducing realtime as a vanity feature before queues, throttling and audit are stable

Definition of done:

- realtime is additive, not compensating for missing operational discipline

## Build Order For Every Agent

Agents should treat this as the mandatory order of execution unless the user explicitly reprioritizes:

1. Protect hosting stability first.
2. Move heavy workflows into VPS async execution.
3. Segment queues and workers.
4. Add Redis-based locking, throttling and dedupe.
5. Centralize AI model routing and quota protection.
6. Expose operational control in Filament on hosting.
7. Harden artifact lifecycle and audit trail.
8. Verify backups and restores.
9. Expand search plane.
10. Add realtime only if it solves a proven operational problem.

## Anti-Goals

The following are explicitly out of scope unless the user overrides this plan:

- moving public rendering from hosting to VPS
- moving canonical admin from hosting to VPS
- turning VPS into a second public application server
- bringing back `n8n` as an active architecture component
- adding infrastructure because it is fashionable but not operationally necessary
- building realtime features before queues / throttling / audit are stable
- supporting any architecture target older than Laravel 13.x

## Laravel 13.x Packages Decision Table For VPS

This table is binding guidance for future agents. It answers a narrow question only:

- which Laravel 13.x ecosystem additions make sense on VPS as real production support for shared hosting

| Package / Service                               | Decision              | When       | Why                                                                                                                |
| ----------------------------------------------- | --------------------- | ---------- | ------------------------------------------------------------------------------------------------------------------ |
| `laravel/horizon`                               | KEEP / EXPAND         | now        | Core of the VPS queue plane; required for segmented queues, supervisors, retries and operator visibility.          |
| `laravel/nightwatch`                            | KEEP / EXPAND         | now        | Correct fit for production monitoring and support-plane telemetry under Laravel 13.x.                              |
| `spatie/laravel-activitylog`                    | ADD                   | now        | Best fit for audit spine between hosting and VPS: who triggered what, which job ran, what artifact came back.      |
| `spatie/laravel-backup`                         | ADD or HARDEN         | now        | Good fit for backup + restore verification plane on VPS as support for canonical hosting backups.                  |
| `laravel/scout`                                 | ADD                   | later      | Correct when search plane becomes active for DTC, blog, reports and internal artifacts.                            |
| `meilisearch`                                   | ADD                   | later      | Preferred companion service for Scout when VPS search plane is enabled.                                            |
| `laravel/reverb`                                | MAYBE                 | later only | Useful only if live operator status / realtime job progress is proven necessary after queues and audit are stable. |
| `laravel/pulse`                                 | DO NOT ADD            | no         | Current architecture already leans on Nightwatch; adding Pulse again increases overlap and operational noise.      |
| `laravel/octane`                                | DO NOT ADD            | no         | Not aligned with the support-plane role; does not solve the main shared-hosting support problems here.             |
| Generic AI orchestration package                | DO NOT ADD by default | no         | Prefer a project-owned AI gateway / quota governor instead of another abstraction layer unless a hard gap appears. |
| Frontend / SSR / public-runtime package for VPS | DO NOT ADD            | no         | Violates canonical architecture: hosting remains the public runtime.                                               |

### Short reading of the table

- Add now:
    - `spatie/laravel-activitylog`
    - `spatie/laravel-backup` if restore verification is not already fully hardened
- Keep and extend now:
    - `laravel/horizon`
    - `laravel/nightwatch`
- Add later:
    - `laravel/scout`
    - `meilisearch`
    - maybe `laravel/reverb`
- Do not add:
    - `laravel/pulse`
    - `laravel/octane`
    - random AI orchestration layers
    - anything that turns VPS into the canonical public runtime

## GitHub 2026+ Signal For Laravel 13.x

This section captures the current top-tier direction from GitHub and official Laravel ecosystem signals for `Laravel 13.x`.

### High-confidence signal

The strongest 2026+ pattern is:

- prefer official Laravel components first
- add only a small number of proven infrastructure packages
- keep architecture operationally simple and queue-first

### What GitHub clearly supports as top-tier

- `laravel/horizon`
    - remains a top-tier choice for queue supervision, worker visibility and async production control
    - for this project it is part of the VPS support-plane core, not an optional extra
- `laravel/reverb`
    - is the modern official direction for realtime in the Laravel ecosystem
    - if realtime is needed later, Reverb is the preferred path instead of older websocket alternatives
- `laravel/scout`
    - remains the clean top-tier Laravel abstraction for search integration
    - fits the planned VPS search plane for DTC, blog, reports and internal artifacts
- `meilisearch`
    - remains one of the strongest practical companions for Scout
    - fits the VPS role much better than pushing heavy search onto shared hosting
- `spatie/laravel-activitylog`
    - is still a top-tier ecosystem choice for audit trail and operational traceability
    - strongly aligned with the planned audit spine between hosting and VPS
- `spatie/laravel-backup`
    - remains a top-tier package for backup workflows
    - fits the support-plane role when combined with restore verification
- `stefanzweifel/laravel-backup-restore`
    - is a valid complement if backup restores are to become a first-class operator workflow

### What GitHub does not signal as top-tier for this project

- `laravel/octane`
    - not a priority for this architecture
    - does not solve the main shared-hosting support problems here
- legacy websocket stacks instead of `laravel/reverb`
    - no reason to prefer them under Laravel 13.x
- random third-party AI orchestration layers
    - current top-tier direction is to keep AI control explicit and project-owned unless there is a hard proven gap
- anything that shifts canonical public runtime from hosting to VPS
    - conflicts with project architecture, even if technically attractive

### Translation for this project

For RS Performance under `Laravel 13.x`, the GitHub 2026+ reading is:

- build around official queue-first Laravel infrastructure
- use VPS as `Horizon + Redis + AI control + artifacts + search support`
- keep hosting as canonical UI, persistence and publish surface
- add only ecosystem packages that strengthen operations, traceability, restore readiness and search

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

## 2026-03-18 17:45 CET - Laravel 13 upgrade gate

- [BLOKER] Safe upgrade do Laravel 13 na produkcji i VPS blokowany przez stabilne constraints pakiet�w laravel/pulse i laravel/horizon (ekosystem nie daje jeszcze kompletnej stabilnej �cie�ki L13 bez trade-off�w).
- [NEXT] Wr�ci� do upgrade, gdy pojawi� si� stabilne wersje wspieraj�ce L13 albo po �wiadomej decyzji o trybie ryzykownym (dev branches / tymczasowe usuni�cie pakiet�w).

## 2026-03-18 18:05 CET - Laravel 13 gate (potwierdzenie po rollback)

- [ZACHOWANE] System zostaje na Laravel 12.54.1 (hosting + VPS), bo bezpieczny upgrade in-place do L13 nie zamyka stabilnie zaleznosci w obecnym stacku.
- [WARUNEK] Wrocic do migracji po pojawieniu sie stabilnych wersji pakietow wspierajacych L13 albo po decyzji o przebudowie stacku (oddzielny rollout).

## 2026-03-18 21:15 CET - POST-UPDATE ERROR FIX + STABLE ROLLBACK

- [DONE] Po testach Laravel 13 przywrocono produkcje do stabilnego stacku Laravel 12.54.1 / Filament 3.3.45.
- [DONE] Naprawiono bledy 500 wynikajace z niekompatybilnosci Filament v5 API z obecnym kodem zasobow.
- [CHECK] Panel admin wraca poprawnie do /admin/login (302), front ma HTTP 200.
- [NEXT] Migracje L13 zaplanowac jako oddzielny rollout: najpierw refactor Filament Resource/Page API, potem upgrade frameworka.

## 2026-03-18 22:08 CET - STATUS

- [DONE] Laravel 13 wdrożony na produkcyjnym hostingu `rsperformance.online`.
- [DONE] Staging L13 zweryfikowany pełnym smoke i test suite (`12 passed`).
- [DONE] Produkcyjny rollout zabezpieczony backupami i rollbackiem pośrednim; finalny stan live stabilny.
- [NEXT] Osobny follow-up: naprawa `watchdog:run` i konfiguracji blog pipeline (`GEMINI_API_KEY`) bez ryzyka dla live runtime.

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

## 2026-03-19 03:27 CET - Completed

- Filament admin recovery after Laravel 13/Filament 5 migration completed.
- Verified pages: blog-posts, gallery-images, services, seo-aeo-center, automation-center, diagnosta-center, company-settings, users, repair-reports.
- Follow-up: automate sync of /laravel/public/css|js|fonts/filament to /public_html in every deploy.

## 2026-03-19 03:43 CET - Completed

- Added hosting post-deploy asset sync script and executed it.
- Fixed blog draft FAQ serialization and repair report notification action contract.
- VPS support-plane audited and updated safely to latest Laravel 12 patch line; stable Vertex models applied.
- Deferred: full VPS Laravel 13 rollout pending safe official dependency path for support-plane stack.

- 2026-03-19 03:48 CET: hosting Filament table actions compacted into `ActionGroup` for blog posts and repair reports after L13 UI regression; browser smoke confirmed layout is stable again.

## 2026-03-19 04:05 CET - Codex

- hosting production: naprawiony konflikt cache dla `/admin*` w `public_html/.htaccess`; admin nie jest juz serwowany jako `public, max-age=14400`
- hosting verification: `/admin/login` zwraca `Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0`; Playwright renderuje poprawny layout `/admin` i `/admin/services`
- vps audit: suchy resolver Composer potwierdzil, ze obecny blocker L13 to `laravel/pulse`; po zmianie `Pulse -> Nightwatch` i `laravel/tinker:^3.0` support-plane rozwiazuje sie poprawnie do `Laravel 13.1.1`

## 2026-03-19 04:21 CET - Codex

- VPS production: support-plane migrowany do `Laravel 13.1.1`; usuniety `Pulse`, dodany `Nightwatch`, podbity `Tinker` do `3.0.0`
- VPS production: `.env` przestawiony na `APP_ENV=production`, `APP_DEBUG=false`; dodany scaffold `NIGHTWATCH_*`
- VPS production: `docker-compose.yml` zmieniony z `pulse-worker` na `nightwatch-agent` profilowany pod `nightwatch`; agent nieuruchomiony z powodu braku `NIGHTWATCH_TOKEN`
- VPS verification: support-plane HTTP OK, MCP health OK, Horizon running, bootstrap caches odbudowane

## 2026-03-19 04:40 CET - Nightwatch live

- VPS
  s-support-plane ma aktywny Nightwatch z poprawnym tokenem i samplingiem 0.1.
- Zamkniety blocker monitoringu po migracji do Laravel 13.
- Nastepny sensowny krok: biznesowy smoke support-plane oraz osobna aplikacja Nightwatch dla hostingu
  sperformance.online.

## 2026-03-19 04:50 CET - Support-plane smoke domkniety

- s-support-plane przeszedl twardy smoke biznesowy po L13 i Nightwatch.
- Naprawiony realny blocker Vertex path; analysis + publication draft pipeline znowu dzialaja.
- Dalszy krok dla monitoringu hostingu: osobna aplikacja Nightwatch dla
  sperformance.online, z agentem uruchomionym na VPS i zdalnym ingestem z hostingu.

## 2026-03-19 04:58 CET - Hosting monitored via VPS

- sperformance.online jest monitorowany przez osobnego agenta Nightwatch uruchomionego na VPS.
- Split runtime jest poprawny: support-plane ma swoj agent i token, hosting ma swoj agent i token.
- Dalszy krok: potwierdzic dane w dashboardzie Nightwatch po kilku minutach ruchu produkcyjnego i ewentualnie podniesc sampling dla wybranych zdarzen.

## 2026-03-19 05:02 CET - Baseline cleaned

- Hosting i VPS maja czysty baseline logow po migracji Laravel 13 + Nightwatch.
- Kolejny sensowny krok: obserwacja dashboardow Nightwatch przez kilka godzin i ewentualne strojenie sampling / alerting, nie dalsze naprawy migracyjne.

## 2026-03-19 05:16 CET - Codex

- hosting production: finalny fix nawrotu Filament wykonany na poziomie cache HTML dynamicznych tras /admin/\*; poprzedni fix assetow zostaje jako warstwa dodatkowa
- verification: komplet kluczowych tras admina zwraca cache-control: no-cache, private; Playwright potwierdza poprawny render i treść na wszystkich glownych ekranach panelu

## 2026-03-19 05:31 CET - Codex

- hosting production: zidentyfikowany browser-specific root cause po stronie Chrome: sw.js byl cacheowany z dlugim TTL, a aktywny service worker na scope / mogl utrzymywac stary stan admina mimo poprawnego runtime serwera
- backup: /home/tyurjydtpw/domains/rsperformance.online/public_html/sw.js.bak_codex_sw_cachefix_20260319
- zmiana: podbity service worker do RS Performance — Service Worker v2026.03.19, CACHE_NAME=rs-performance-v2026.03.19-admin-fix; dodatkowo sw.js ma teraz Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0
- weryfikacja: curl -I https://rsperformance.online/sw.js -> cache-control: private, no-store, no-cache, must-revalidate, max-age=0; body serwuje nowa wersje workera

## 2026-03-19 06:10 CET - Codex

- [DONE] Blog Filament: przywr�cone Generuj artyku� AI + SEO i widoczno�� Akcje przez poprawk� kompatybilno�ci RecordActionsPosition oraz admin self-heal dla service worker/cache w Chrome.

## 2026-03-19 06:45 CET

- [done] Blog Filament table fit on production admin.
- [done] Blog AI draft generation changed from blocking Livewire request to async background CLI on hosting.
- [done] Vertex researcher fallback added for HTTP 429 / RESOURCE_EXHAUSTED.
- [next] Optional cleanup: remove smoke drafts #32-#36 if user does not want them kept.

## 2026-03-20 03:52 CET - Telegram blog agent async fix

- [DONE] Hosting Telegram blog agent no longer runs the full generation pipeline inside the webhook request.
- [DONE] Added `blog:telegram-generate` command and moved Telegram draft generation to background CLI execution.
- [CHECK] Production verification passed: backup created, remote files uploaded, `php85 -l` clean, `php85 artisan optimize` clean, command visible in artisan list.
- [NEXT] Perform one real Telegram smoke and decide whether test drafts #37 and #41 stay or get removed.

## 2026-03-20 04:29 CET - Blog fix

- [DONE] Hosting blog generation restored to async Filament flow in `ListBlogPosts.php`.
- [DONE] Default premium review disabled in blog UI and CLI to reduce shared Vertex quota pressure.
- [DONE] Premium reviewer fallback switched to `gemini-2.5-flash`.
- [CHECK] Dry-run passed and real Filament smoke created then cleaned up draft `#45`.
- [NEXT] Optional: surface a direct link to page 1 or auto-refresh after background generation completes.

## 2026-04-03 06:10 CET - AEO watchdog + n8n operational follow-up

- [DONE] Hosting now has a rolling-window `aeo:traffic-watchdog` scheduled every 30 minutes.
- [DONE] VPS `n8n` was audited live:
    - `RS AI Bot Invitation Hub` active, latest execution success
    - `Content Freshness Monitor` active, latest execution success
    - `SEO Health Dashboard` active, latest execution success
    - `DTC IndexNow Drip` definition repaired and re-activated
- [NEXT] Verify the first post-fix hourly execution of `DTC IndexNow Drip` and only then mark the `n8n` lane fully green.
- [NEXT] Keep treating operational proof as primary:
    - rolling-window bot traffic
    - workflow success
    - real routing outcomes
        > 2026-04-09 STORAGE NOTE
        > Shared-hosting storage pressure is mainly backup-driven.
        > First retention pass on `~/domains/rsperformance.online/laravel/storage/app/private/RS PERFORMANCE` is complete:
        >
        > - kept 7 latest daily zips + 3 weekly restore points
        > - deleted 18 older zip backups
        > - recovered about `4.3G`
        >   Next optional infra pass: audit stale full-backup directories, tar.gz archives, and old staging trees before deleting anything else.

## 2026-04-09 08:12 CET - Manual blog generator topic integrity

- [DONE] manual Filament lane no longer trusts Gemini output blindly
- [DONE] `GeminiBlogDraftGenerator` now:
    - tells the model not to replace the core topic with another automotive topic
    - preserves topic anchors like fuel type / model / code / action / technology
    - rejects off-topic output at runtime
- [CHECK] exact mismatch class reproduced and blocked:
    - input: fuel-prices story
    - bad output: corrosion article
    - result: rejected instead of filling the form
- [NEXT] if Gemini `429` keeps happening during manual generation, add a provider-side fallback or backoff path for this Filament lane

## 2026-04-09 08:38 CET - Filament editorial orchestra

- [DONE] replaced thin manual topic flow with a richer editorial brief flow from the main blog button
- [DONE] command lane now understands:
    - editorial mode
    - slot type
    - editorial notes
    - source URLs
    - quality gate
    - premium review
    - optional support-plane dispatch
- [DONE] pipeline now enforces operator-topic fidelity and stronger subtle SEO/AEO/GEO guidance
- [NEXT] run one full real editorial generation through the new modal and inspect:
    - final title
    - factual fidelity
    - image relevance
    - used_models
    - quality_gate score
- [DONE] hero-image prompt now also preserves operator topic and slot type more aggressively

## 2026-04-09 11:05 CET - Current next steps

- [IN PROGRESS] Observe live completion of the surviving BMW manual run `01knr63dsyaa661vzt5fkpfy1w` and verify whether it creates a clean draft with matching hero image.
- [NEXT] After the BMW run resolves, clean stale `dispatched` host-side test runs that were created before the richer editorial contract was fully aligned.
- [NEXT] If any remaining `--editorial-mode` errors appear after current workers drain, trace and remove the last legacy caller path.

## 2026-04-09 20:36 CET - Manual BMW lane status update

- [DONE] Hard blocker on manual BMW drafts is removed.
- [DONE] `BlogVertexPipelineService` now applies deterministic editorial stabilization before the quality gate:
    - title tone cleanup
    - excerpt normalization
    - underlength content expansion
- [CHECK] Fresh live run `01knsrcs0gsc9k8prv2efftza9` succeeded and created draft `#110` with hero image.
- [NEXT] Clean stale host-side test runs from the pre-fix BMW attempts.
- [NEXT] Trace the remaining legacy caller that still emits `The \"--editorial-mode\" option does not exist.` in `blog-generate.log`.

## 2026-04-09 20:44 CET - Launcher cleanup update

- [DONE] Direct Telegram and API launchers now use a more explicit, unified command contract.
- [DONE] Added explicit `requested-via` across background launch paths.
- [DONE] Direct Telegram `/blog` now passes explicit `--editorial-mode=evergreen`.
- [NEXT] Observe whether any fresh `The \"--editorial-mode\" option does not exist.` entries still appear in `blog-generate.log`; if yes, the remaining source is outside the current hosting code paths and should be traced from live process origins instead of code search.

### 2026-04-11 n8n stability addendum

- The reported `n8n hang` was traced to stale contracts, not runtime exhaustion:
    - missing hosting DTC routes
    - bad container-local `localhost:8082` research targets
    - stale monitor URLs
    - one transient syntax regression in the monitor repair itself
- Live fixes now in place:
    - hosting `routes/web.php` again exposes `/api/dtc/batch-for-enrichment`, `/api/dtc/store-enrichment`, `/api/dtc/enrichment-stats`
    - VPS `auto.rs3d.pl` proxies `/research/*` to the FastAPI editorial engine on `127.0.0.1:8082`
    - `RS Research Harvester` now uses `https://auto.rs3d.pl/research/harvest`
    - `RS Editorial Board` now uses `https://auto.rs3d.pl/research/pool` and correct `mark-used?item_id=...&post_id=...`
    - `RS AI Agent Monitor` now checks current freshness/agent/discovery surfaces and has repaired code-node syntax
- Fresh execution proof already captured:
    - `RS DTC Enrichment Engine` runs `899` and `903` => `success`
    - `RS Research Harvester` run `904` => `success`
- Remaining proof needed:
    - next hourly `RS AI Agent Monitor` run after the JS syntax fix
    - next scheduled `RS Editorial Board` run after the research URL rewiring

## 2026-04-11 21:40 CET - Daily-news reactivated, manual blog proof, richer A2A artifacts

- [DONE] `RS Daily Automotive News Drafts` (`zo4sIHAUZZseEGkL`) is active again in n8n.
- [DONE] Manual blog lane on hosting was hardened again for sensational operator briefs.
- [CHECK] Fresh live production proof: draft `#112` created from the fuel-price brief.
- [DONE] `A2aTaskController` now emits structured artifacts for diagnostics / booking / repair / general-info.
- [CHECK] A2A diagnostics smoke returns artifact `RS diagnostics capability`.
- [DONE] Local secret ops pack prepared in `G:\gravity\cursor.md` for a new Cursor agent.
- [NEXT] Catch one natural success of the reactivated daily-news schedule.
- [NEXT] Add/fix the `ai-invitations` log channel for `aeo:invite-bots`.

---
name: rs-a2a-wow-2026
description: >
    Agent-to-Agent (A2A) protocol for RS Performance — agent card, JSON-RPC + REST,
    gateway vs canonical hosting, VPS ai-gateway sync after deploy, DTC SQLite artifacts,
    Overture certification, coordination with discovery/MCP. Use when extending A2A surfaces,
    debugging task routing, certifying agent cards, or wiring MCP bridge flows.
license: Proprietary — RS Performance internal
metadata:
    version: 1.2.9
    updated: 2026-04-22
    stack: Laravel 13 + PHP 8.5 (hosting canonical, VPS support-plane)
---

# RS A2A — WOW 2026 (operational playbook)

## When to use this skill

- Implementing or changing **A2A** endpoints, **agent card** (`/.well-known/agent-card.json`), or **A2A JSON** (`/.well-known/a2a.json`).
- Debugging **JSON-RPC** (`POST /`, `POST /message:send`) vs **REST** task APIs (`GET /tasks`, streaming).
- Aligning **skill IDs** in the agent card with **routing** in `A2aTaskController::routeToSkill()`.
- Running **Overture** certification against production or staging.
- Coordinating **canonical hosting** (Cyber-Folks) with **VPS / AI gateway** URLs in `RsUri` and Filament ops.

## Onboarding

- Główny indeks: **`AGENTS.md`** (sekcja _A2A / Overture / bramka VPS_); mapa repo: **`RELAY.md`** §6 (wiersz _A2A / Overture + bramka VPS_) i §11 (skrót).
- Stos Cursor MCP: pelna instalacja (`npm run mcp:install` / `composer mcp-install`), audyt (`npm run mcp-verify`) — w `RELAY.md` §6.

## Primary sources (read before changing behavior)

| Source                   | Path / URL                                                                                                  |
| ------------------------ | ----------------------------------------------------------------------------------------------------------- |
| A2A spec & patterns      | `research/docs-sources/a2a/`                                                                                |
| JS / runtime refs        | `research/docs-sources/a2a-js/`, `a2a-overture/`                                                            |
| Mesh / gateway runtimes  | `tools/a2a-mesh-runtime/`, `a2a-overture-runtime/`, `truss-mcp-a2a-gateway-runtime/`                        |
| Laravel routes           | `routes/web.php` (A2A block + `Route::post('/', ...)`)                                                      |
| Controller               | `app/Http/Controllers/A2aTaskController.php`                                                                |
| Agent card + skills JSON | `app/Support/Search/SearchArtifactFactory.php` — `a2aAgentCard()`, `a2aAgentSkills()`, `a2aAgentCardJson()` |
| CSRF exclusions          | `bootstrap/app.php` — A2A paths must stay excluded                                                          |
| Canonical URIs           | `app/Support/RsUri.php` — `a2aJson()`, `a2aAgentCard()`, `aiGateway*()`                                     |

## Architecture (non-negotiable)

- **Hosting** = canonical site + A2A surface consumers hit first.
- **VPS** = async producer, DTC, heavy AI; **must not** replace canonical URLs in the public agent card without an explicit product decision.
- **Stack lock:** Laravel 13.x, PHP 8.5.x on live; new A2A code must stay compatible.

## A2A vs Qdrant (VPS) — do not conflate

- **A2A** = Overture-facing contract: well-known, JSON-RPC/REST **on the canonical host** — not “another database”.
- **Qdrant** on the VPS = **vector memory** (`rs_static_knowledge`, `rs_dynamic_knowledge`, …) for RAG and semantic tools; updated by jobs and gateway-side flows, not by “replacing” `/.well-known/a2a.json`.
- **Skill `gateway-semantic-routing`** in the agent card points users toward **search / routing** behaviors; the **MCP** tool `qdrant-vps-wow` (optional, tunneled) is for **operator inspection** of Qdrant — orthogonal to Overture certify / `composer a2a:certify`.
- Canonical write-up: **`RELAY.md`** (MySQL → Qdrant) + **`AGENTS.md`** (A2A section).

## VPS — bramka `ai.*` (realne wsparcie, nie publiczny zamiennik hostingu)

- **Bramka** (`RsUri::aiGateway()`, npx: `https://ai.rsperformance.online/`) trzyma **lustro** manifestów: `agent.json`, `openapi.json` / `openapi.yaml`, `ai-resources.json`, itd. — zsynchronizowane ze **źródłem prawdy na hostingu** (skrypt na VPS: `/srv/ai-gateway/sync.sh`).
- **Po deploy** zmian A2A, discovery, OpenAPI lub artefaktów katalogowych na **produkcji hostingu** uruchom sync na VPS, żeby fast-lane / routing WOW nie oglądały starej kopii.
- **Polecenia (laptop z `vps_exec.py` + klucz SSH z `RELAY.md` / `vps.md`):**

| Sposób         | Polecenie                                                                                          |
| -------------- | -------------------------------------------------------------------------------------------------- |
| **Composer**   | `composer a2a:sync-gateway`                                                                        |
| **npm**        | `npm run a2a:sync-gateway`                                                                         |
| **Python**     | `python scripts/vps_ai_gateway_sync_apr2026.py` (opcja `--dry-run` = tylko `bash -n` na `sync.sh`) |
| **PowerShell** | `.\scripts\vps-ai-gateway-sync.ps1`                                                                |

- **Kiedy edytujesz sam `sync.sh` na VPS:** najpierw `python vps_exec.py --backup /srv/ai-gateway/sync.sh <suffix>` (dyrektywa backupu), potem upload / edycja.
- **N8n, Qdrant, Vertex** — osobne ścieżki; skill **`rs-n8n-wow-2026`**, `vps.md`. Bramka sync **nie** zastępuje pipeline’ów n8n.

## Public endpoints (production contract)

Declared in `routes/web.php` (verify live with `php artisan route:list --path=message`):

| Method   | Path                                 | Role                                                                        |
| -------- | ------------------------------------ | --------------------------------------------------------------------------- |
| GET      | `/.well-known/a2a.json`              | A2A discovery document                                                      |
| GET      | `/.well-known/agent-card.json`       | Agent card (JSON)                                                           |
| POST     | `/`                                  | JSON-RPC envelope (primary)                                                 |
| POST     | `/message:send`, `/message/send`     | Alias JSON-RPC                                                              |
| POST     | `/message:stream`, `/message/stream` | Streaming JSON-RPC                                                          |
| GET      | `/tasks`, `/tasks/{taskId}`          | REST list + task poll                                                       |
| JSON-RPC | `ListTasks` / `tasks/list`           | List tasks (required for Overture `list-tasks` when using JSON-RPC binding) |
| POST     | `/tasks/{taskId}:cancel`             | Cancel                                                                      |
| POST     | `/tasks/{taskId}:subscribe`          | SSE subscribe (JSON-RPC: `SubscribeToTask`)                                 |

**CSRF:** A2A POST routes are excluded in `bootstrap/app.php`. If you add a new POST under a different path, add it to the CSRF exception list or clients will get419.

## Transport matrix

| Client style | Entry                                       | Notes                                                      |
| ------------ | ------------------------------------------- | ---------------------------------------------------------- |
| JSON-RPC     | `POST /` with `jsonrpc`, `method`, `params` | Handled by `handleJsonRpc` → `handleTasksSend` / streaming |
| Task REST    | `GET /tasks/{id}` after create              | Same task store as JSON-RPC                                |
| SSE          | `GET .../subscribe`                         | Long-lived; check proxy timeouts on hosting                |

## Agent card — skill IDs vs routing

`SearchArtifactFactory::a2aAgentSkills()` exposes skill entries (e.g. `dtc-lookup`, `vehicle-diagnostics`, `repair-services`, `booking`, `gateway-semantic-routing`).`A2aTaskController::routeToSkill()` maps **user text** (regex/heuristics) to behaviors — not necessarily 1:1 with card IDs. **WOW hygiene:** when adding a card skill, add or document the routing path and prefer **structured artifacts** (see below) for machine-readable handoff.

## Structured artifacts (WOW)

- **DTC** — JSON artifact for: DB missing, code not found, row without Trinity enrichment, and enriched Trinity payload (all include `discovery` + canonical DTC URLs where applicable).
- **Diagnostics / booking / repair** — each response includes one `application/json` part with `rs_skill` matching the agent card (`vehicle-diagnostics`, `booking`, `repair-services`), `summary`, `canonical_paths` via `RsUri`, and `discovery` (a2a + agent-card + ai-resources).
- **General fallback** — artifact `intent: general_capabilities`, `card_skill_ids`, and `support_plane` (gateway semantic search + routing URLs).

## Overture certification

Z repo root (Node + npx; runtime vendored w `tools/a2a-overture-runtime`):

| Sposób                   | Polecenie                                                                                      |
| ------------------------ | ---------------------------------------------------------------------------------------------- |
| **Composer (canonical)** | `composer a2a:certify`                                                                         |
| Szybki test Pest A2A     | `composer a2a:test`                                                                            |
| **npm**                  | `npm run a2a:certify` (prod); inny host: `npm run a2a:certify:agent -- https://...`            |
| **PowerShell**           | `.\scripts\a2a-overture-certify.ps1` lub `-AgentUrl https://...`                               |
| npx (jak wcześniej)      | `npx --prefix tools/a2a-overture-runtime overture certify https://rsperformance.online --json` |

**ListTasks:** JSON-RPC `ListTasks` / `tasks/list` musi zwracać `result.tasks[]` (Overture `list-tasks`); GET `/tasks` to REST-parallel.

**Cursor hooks (repo — 2026-04+):** **`.cursor/hooks.json`**: `afterFileEdit` + **matcher `Write`** (nie Tab) → `python .cursor/hooks/rs_a2a_after_edit.py` — `php -l` dla m.in. `A2aTaskController`, `routes/web`, `AiDiscovery*`, `SearchArtifactFactory`, `bootstrap/app`, **`config/ai_agents.php`** (parity UA/robots). Pełne testy: `composer a2a:test`. [Cursor Hooks](https://cursor.com/docs/hooks).

Interpret failures: **agent card**, **CORS** (gateway), **zły `url` w karcie**, **500 na POST /**, brak metody `ListTasks` w JSON-RPC. Po poprawkach: ponowna certyfikacja + wpis w `SESSION_LOG.md` / relay.

## Coordination with other skills

- **`rs-discovery-wow-2026`** — sitemap, llms.txt, IndexNow, headers: run discovery checks **after** agent card URLs are stable.
- **`rs-schema-wow-2026`** — JSON-LD on HTML pages; A2A artifacts are **complementary**, not a replacement.
- **MCP bridge / Filament ops** — same entity facts should match agent card `description` and skill metadata to avoid drift.

## Pre-ship checklist (A2A)

1. `GET /.well-known/agent-card.json` — 200, valid JSON, `url` matches canonical HTTPS.
2. `POST /` — sample `tasks/send` completes; task reaches `TASK_STATE_COMPLETED`.
3. DTC sample code returns **artifacts** with JSON part.
4. No 419 on POST (CSRF exclusions).
5. Overture certify (or equivalent) passes on target host.
6. `RsUri` gateway helpers still point at live VPS/gateway DNS (no stale IP in card).

## Anti-patterns

- Putting **VPS-only** URLs in the public agent card without TLS and monitoring.
- Adding A2A POST routes **without** CSRF exclusion.
- **Text-only** responses for intents that peers need to parse — prefer JSON `data` parts.
- Duplicating skill definitions in Blade or Filament without updating `SearchArtifactFactory`.

## CI (GitHub Actions)

- `.github/workflows/laravel-quality.yml` wyzwala też zmiany w **`.cursor/**`, skryptach `scripts/vps_ai_gateway_sync_apr2026.py`, `vps-ai-gateway-sync.ps1`, `a2a-overture-certify.ps1`— pełny`artisan test`obejmuje`A2a\*Test`(brak dublikatu osobnego kroku`composer a2a:test`).

## Version history

- **1.2.9** (2026-04-22) — Sekcja **A2A vs Qdrant (VPS)**; Cursor MCP `qdrant-vps-wow` vs kontrakt Overture.
- **1.2.7** (2026-04-22) — **`RELAY.md`** §6 + §11: skrót A2A / Overture / VPS; onboarding z odsyłaczem do RELAY.
- **1.2.6** (2026-04-22) — Odsyłacz z **`AGENTS.md`** (onboarding); sekcja _Onboarding_ w skille.
- **1.2.5** (2026-04-22) — Rozszerzone **path** w `laravel-quality.yml` (hooki + skrypty A2A/VPS); sekcja **CI** w skille.
- **1.2.4** (2026-04-22) — Hook: `config/ai_agents.php` + matcher **`Write`** (mniej szumu); krzyżówka z `rs-robots-wow-2026` (katalog UA).
- **1.2.3** (2026-04-22) — **`.cursor/hooks.json`** + `.cursor/hooks/rs_a2a_after_edit.py` (lint PHP po edycji kluczowych plików A2A/discovery).
- **1.2.2** (2026-04-22) — Sekcja **VPS bramka**: `composer a2a:sync-gateway`, `scripts/vps_ai_gateway_sync_apr2026.py`, `vps-ai-gateway-sync.ps1` (odświeżanie lustra `ai.*` po zmianach na hostingu).
- **1.2.1** (2026-04-22) — JSON-RPC `ListTasks` / `tasks/list` (Overture `list-tasks`); `composer a2a:certify` / `a2a:test`, `scripts/a2a-overture-certify.ps1`; kolejność tras `GET /tasks` przed `GET /tasks/{id}`.
- **1.2.0** (2026-04-11) — Artifacts implemented in `A2aTaskController` for all intents + DTC edge cases; see `tests/Feature/A2aStructuredArtifactsTest.php`.
- **1.1.0** (2026-04-11) — Expanded playbook: endpoints, transport matrix, artifacts policy, Overture, checklists, cross-skills.
- **1.0.0** (2026-04-09) — Initial WOW coordination doc.

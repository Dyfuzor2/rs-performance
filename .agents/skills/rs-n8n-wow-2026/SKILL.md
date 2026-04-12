---
name: rs-n8n-wow-2026
description: Use when building, debugging, auditing, or hardening n8n workflows for RS Performance, especially when the task touches editorial automation, Telegram ops, IndexNow, invitation hub, workflow quality gates, or MCP-assisted workflow validation.
version: 1.1.0
updated: 2026-04-12
author: codex
tags: [n8n, workflow, automation, editorial, telegram, vertex-ai, april-2026]
---

# RS n8n WOW 2026+

Project coordination skill for `n8n` work in `G:\gravity`.

## Primary sources

- local MCP runtime (Cursor): `run-n8n-mcp.ps1` loads `.cursor/mcp.env` → pinned `n8n-mcp@2.47.5` stdio; verify with `verify-n8n-api.ps1`; webhook matrix: `smoke-critical-webhooks.ps1`. **`N8N_API_URL` domyślnie:** `https://auto.rs3d.pl` (produkcyjny n8n na VPS), nie SOCmid — patrz `mcp.env.example`. Python: `scripts/gravity_cursor_env` — `require_n8n_api` plus OpenRouter / Telegram / Meta / `RS_X_API_TOKEN` / `RS_BLOG_PIPELINE_KEY` (see `mcp.env.example`); no literals in `deploy_*.py`.
- local MCP runtime:
    - `G:\gravity\tools\n8n-mcp-runtime`
- local source repo:
    - `G:\gravity\mcp-servers\n8n-mcp`
- local docs mirror:
    - `G:\gravity\research\docs-sources\n8n-docs`
- local expert skill pack:
    - `G:\gravity\n8n-skills`

## Use this skill for

- workflow design and debugging
- execution failures and scheduler drift
- editorial / blog workflow quality gates
- Telegram alerting and ops flows
- AI invitation and freshness automations
- validating whether a workflow should live on hosting or VPS

## Mandatory stack

1. `rs-aeo-skill`
2. `laravel-13-php-85`
3. `observability-engineer`
4. `systematic-debugging`
5. n8n local assets:
    - `G:\gravity\n8n-skills\skills\n8n-mcp-tools-expert`
    - `G:\gravity\n8n-skills\skills\n8n-validation-expert`
    - `G:\gravity\n8n-skills\skills\n8n-workflow-patterns`

## Runtime rules

- prefer VPS for recurring automation
- keep canonical hosting as source of truth for public artifacts
- use native Laravel endpoints for persistence, validation, and publishing
- treat `n8n` as orchestrator, not business-logic replacement
- every editorial flow must have a quality gate and observable failure lane

### Instancja hostingu się zmieni (migracja / nowa domena)

- **n8n ≠ hosting:** URL `N8N_API_*` może zostawać na VPS/SOCmid; zmienia się **kanoniczny front** (`APP_URL`, domena publiczna).
- Po zmianie instancji hostingu **obowiązkowo:** przeskanować workflowy pod hardcoded `https://…/` do API Laravel (blog pipeline, Diagnosta edge, trending, SEO), zaktualizować eksporty w repo, ponownie **PUT** przez API lub import w edytorze.
- W repozytorium wiele skryptów smoke / `n8n_apr2026_fleet_http_jsonfix` nadal zawiera host — przy migracji: podmiana lub `RS_CANONICAL_BASE_URL` z `.cursor/mcp.env` (szablon: `mcp.env.example`).
- Zsynchronizuj też **Laravel** (`config/n8n.php`, Filament hub) jeśli tam zapisano stary host n8n lub canonical.

## Quick checks

- confirm the workflow is active
- confirm the latest execution is fresh and green
- confirm cron timezone assumptions
- confirm Telegram / alert sinks are not silently failing
- confirm the workflow writes only through stable hosting APIs or verified VPS stores

## Vertex AI Studio × n8n (proxy na hostingu — kwiecień 2026+)

**Nie** osadzaj service account JSON w node’ach n8n. Produkcja: **Laravel** trzyma OAuth do Vertex (`VertexAccessTokenFactory`) i udaje endpointy dla workflowów:

- `POST /api/n8n/vertex/chat` — chat w stylu OpenAI (wygodne pod migrację z OpenRouter).
- `POST /api/n8n/vertex/generate-content` — surowe `generateContent` (Gemini na Vertex).

Autoryzacja: `X-RS-Blog-Pipeline-Key` lub `X-API-Token` / Bearer (`N8nInternalAuth`). Pełna mapa, modele i checklista: **`references/n8n_vertex_ai_studio_2026.md`**.

Patch eksportów workflowów (stare URL-e → proxy): `scripts/patch_n8n_vertex_workflows.py`. Testy proxy: `tests/Feature/N8nVertexProxyTest.php`.

**Blog (pełna redakcja + Imagen / Gemini image):** osobny tor — `BlogVertexPipelineService` przez `/api/blog/pipeline/*`, nie przez surowy chat proxy.

## Blog cadence workflow

- Export / repo template: `n8n_workflow_blog_draft_cadence.json` (**RS Daily Automotive News Drafts**).
- Node **Select Fresh Story** wstrzykuje **wirtualną redakcję (7 ról)** po polsku (poprawna polszczyzna) do `editorial_notes` dla `POST https://rsperformance.online/api/blog/pipeline/run` (zgodnie z `references/blog_newsroom_crew_2026.md`).
- After editing JSON locally: re-import on VPS n8n or sync via your deployment process; confirm workflow **active** and schedule `15 8,13,18 * * *` (Warsaw intent).

## Operator WOW surface (Telegram + fleet)

- **`scripts/vps_n8n_telegram_wow_digest.py`** (run on VPS): health matrix + invitation webhook + **per-workflow last execution status** for all active workflows, one HTML message to the same Telegram chat as **RS AI Agent Monitor** (parsed from workflow JSON — no token echo). Flags: **`--quiet`** for cron (stderr only on failure).
- **Daily automation:** host cron (not inside n8n container — no Python there): **`0 8 * * *`** with **`CRON_TZ=Europe/Warsaw`**, script path **`/srv/ops-stack/scripts/vps_n8n_telegram_wow_digest.py`**, install via **`scripts/vps_install_wow_digest_cron.sh`**.
- Pair with **`scripts/vps_n8n_wow_smoke.py`** for CLI matrix + minimal ping when you do not need the full fleet table.

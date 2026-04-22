---
name: rs-discovery-wow-2026
description: Ultra-modern April 2026+ discovery skill for RS Performance. Orchestrates AI discovery, technical SEO, response headers, llms surfaces, A2A + MCP cards, IndexNow, markdown mirrors, schema graph quality, and bot invitation verification under one production-safe workflow.
version: 1.0.3
updated: 2026-04-22
author: codex
tags:
    [
        aeo,
        seo,
        geo,
        discovery,
        llms,
        indexnow,
        headers,
        schema,
        agent-cards,
        april-2026,
    ]
---

# RS Discovery WOW 2026+

**Mapa GEO vs AEO i wybór modułów `geo-*`:** `G:\gravity\GEO.md` (reguła: preferuj `geo-*`, nie utrzymuj treści równolegle w `geo-geo-*`).

This is the top-level operational skill for the RS public discovery layer.

**MCP (Cursor, kwiecien 2026+):** `playwright-wow` — Microsoft **[@playwright/mcp](https://github.com/microsoft/playwright-mcp)**; `postgres-vps-wow` — **[mcp-postgres](https://github.com/kristofer84/mcp-postgres)** (preferuj `node_modules` po `npm install` w korzeniu; pelny stos: `tools/install-cursor-mcp-deps.ps1`). Tunel SSH + `POSTGRES_MCP_ENABLED=1`. Szczegoly: `RELAY.md` §6, `vps.md` (Postgres Docker).

Use it when auditing, hardening, or extending:

- AI Discovery
- Technical SEO
- response headers
- bot invitation / gateway invitation surfaces
- A2A and MCP agent cards
- `SpeakableSpecification`
- RFC 8288 Link headers
- IndexNow
- markdown endpoints
- `X-AI-Training-Policy`
- `llms.txt` / `llms-full.txt`
- discovery manifests
- AI-readable schema graph quality

It is a **coordination skill**.
It tells you which lower-level skills to use and in what order.

---

## 1. Primary Goal

Make RS one of the most machine-readable local automotive domains in the stack:

- fast to discover
- easy to trust
- easy to quote
- easy to route
- easy to keep fresh

The discovery layer must help:

- Google / Bing / classic search
- AI browsers
- training crawlers
- answer engines
- agentic fetchers
- the VPS support plane

---

## 2. Mandatory Skill Stack

Use these skills together for discovery work:

1. `rs-schema-wow-2026`
    - schema graph, entity graph, speakable, no-price policy
2. `rs-aeo-skill`
    - project AEO conventions and canonical behavior
3. `geo-llmstxt`
    - `llms.txt` and `llms-full.txt`
4. `geo-crawlers`
    - crawler allow/deny behavior and access mapping
5. `geo-technical`
    - headers, crawlability, indexability, AI-readable technical layer
6. `schema-markup`
    - rich-result safety and JSON-LD correctness
7. `technical-seo-checker`
    - production-grade technical checks
8. `laravel-13-php-85`
    - implementation quality for Laravel 13 / PHP 8.5

Optional but high-value:

- `rs-a2a-wow-2026` — A2A-only work: agent card, `/.well-known/a2a.json`, JSON-RPC + REST task lifecycle, `A2aTaskController` routing, structured artifacts, Overture `certify` (pair with this skill when checklist items mention A2A cards)
- `geo-platform-optimizer`
- `sgeo-schema-markup-generator`
- `entity-optimizer`
- `observability-engineer`
- `qdrant-rest-api-market`

---

## 3. Mandatory MCP / Tool Surfaces

For real verification, prefer this stack:

### Repo / runtime intelligence

- `laravel-boost`
- `context7`

### Public-surface verification

- `fetch`
- `chrome-devtools-mcp`
- `playwright-mcp`

### Edge / WAF / header context

- `cloudflare-docs`

### Retrieval / support-plane context

- `qdrant-rs-answer-routing`
- `qdrant-rs-knowledge`
- `qdrant-rs-dynamic`

### External tooling

- `seo-mcp` when available
- `firecrawl` as CLI sidecar for wide crawl/map/scrape passes

---

## 4. Discovery WOW Checklist

Treat this as the required audit matrix.

### AI Discovery

- `llms.txt` curated, not noisy
- `llms-full.txt` rich but consistent with canonical truth
- `.well-known/ai-resources.json` aligned with live surfaces
- `.well-known/ai-resources.json` exposes `knowledge_plane`: canonical **MySQL on hosting** = source of truth; **Qdrant on VPS** = vector/RAG lane synced from production (agents use gateway semantic search + MCP; no bulk MySQL scraping)
- `.well-known/agents.json`
- `.well-known/agent-card.json`
- `.well-known/mcp-agent-card.json`
- `.well-known/a2a.json`
- `.well-known/openapi.json|yaml`
- gateway manifests aligned with canonical truth

### Technical SEO

- sitemap stack healthy
- robots.txt healthy
- canonical consistency
- no broken discovery aliases
- live freshness surfaces
- markdown mirrors healthy

### Response headers

- AI headers intentional, not decorative
- RFC 8288 `Link` headers present and useful
- `X-AI-Training-Policy` explicit
- `X-Content-Provenance` / freshness / entity hints aligned
- no contradictory caching behavior between hosting and VPS

### Bot invitation / support-plane

- bot invitation hub operational
- gateway manifests healthy
- discovery fetch order coherent
- VPS sync never serves stale artifacts

### Schema / GEO

- homepage entity graph coherent
- service / problem / DTC / report / blog graph coherent
- `SpeakableSpecification` only on real visible summary blocks
- no explicit price leakage
- no spammy over-markup

### Distribution quality

- bots do not end only on `/`
- exact pages are exposed and routable
- answer-path share is measured
- Qdrant support plane helps exact routing, not just vector vagueness

---

## 5. Default Audit Order

When auditing the discovery stack, use this order:

1. Public discovery manifests
2. Response headers
3. Schema graph
4. Markdown mirrors
5. IndexNow / freshness / bot invitation outputs
6. Canonical vs gateway alignment
7. Exact-routing / Qdrant support layer

---

## 6. April 2026+ WOW Definition

The discovery layer is WOW only if:

- all major surfaces say the same thing
- headers, schema, llms, manifests and gateway are aligned
- exact routes exist for high-intent queries
- VPS strengthens canonical hosting instead of competing with it
- nothing leaks stale or contradictory data
- the whole stack is machine-readable without rendering-heavy assumptions

If it is merely “many files return 200”, it is not WOW yet.

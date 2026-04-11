---
name: rs-n8n-wow-2026
description: Use when building, debugging, auditing, or hardening n8n workflows for RS Performance, especially when the task touches editorial automation, Telegram ops, IndexNow, invitation hub, workflow quality gates, or MCP-assisted workflow validation.
version: 1.0.0
updated: 2026-04-09
author: codex
tags: [n8n, workflow, automation, editorial, telegram, april-2026]
---

# RS n8n WOW 2026+

Project coordination skill for `n8n` work in `G:\gravity`.

## Primary sources

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

## Quick checks

- confirm the workflow is active
- confirm the latest execution is fresh and green
- confirm cron timezone assumptions
- confirm Telegram / alert sinks are not silently failing
- confirm the workflow writes only through stable hosting APIs or verified VPS stores

## Operator WOW surface (Telegram + fleet)

- **`scripts/vps_n8n_telegram_wow_digest.py`** (run on VPS): health matrix + invitation webhook + **per-workflow last execution status** for all active workflows, one HTML message to the same Telegram chat as **RS AI Agent Monitor** (parsed from workflow JSON — no token echo).
- Pair with **`scripts/vps_n8n_wow_smoke.py`** for CLI matrix + minimal ping when you do not need the full fleet table.


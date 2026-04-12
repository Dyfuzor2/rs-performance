# Active Context — RS Performance 🪐

_Ostatnia aktualizacja: 2026-04-12_

## n8n — API (pamięć dla agentów)

- **Public REST API v1:** `{N8N_API_URL}/api/v1/*`, nagłówek **`X-N8N-API-KEY`** (JWT z Settings → API w danej instancji).
- **Kontrakt API:** `N8N_API_URL` + `N8N_API_KEY`, ścieżki `/api/v1/*`, nagłówek `X-N8N-API-KEY` — wspólny dla Filament, skryptów i **n8n-mcp**.
- **n8n-mcp:** **serwer na VPS** (`vps.md`), nie zakładamy że MCP działa tylko lokalnie; `run-n8n-mcp.ps1` = opcjonalny launcher dev.
- **Instancja produkcyjna RS:** `https://auto.rs3d.pl` (n8n na VPS) — tu flota workflowów i domyślne `N8N_API_*` w `mcp.env` / skryptach VPS.
- **Opcjonalnie druga instancja:** SOCmid `n8n-s2.socmid.cloud` — **osobna** baza workflowów i **osobny** klucz; nie mylić z `auto.rs3d.pl`.

## Bieżący Fokus

Wdrażanie **Antigravity 2026+ Workspace Upgrade**. Przejście z luźnych plików `.agents` na ustrukturyzowany Memory Bank (Cortex Pattern).

## Ostatnie Operacje

1. Zakończono ręczne optymalizacje AEO dla Schema.org i BLUF na `service-show.blade.php`.
2. Zainicjowano strukturę `.agents/memory-bank/`.
3. Opracowano Master Plan dla "200% Upgrade".

## Następne Kroki

- Dokończyć inicjalizację plików `core/` (techContext, systemPatterns).
- Skonfigurować zasady `always_on`.
- Wygenerować pierwszą pełną dokumentację `/project-documentation`.

# Audyt operacyjny: hosting (Cyber-Folks) + VPS (support plane) — 2026-04-22

Źródła: `RELAY.md`, `vps.md`, `AGENTS.md`, sondy SSH (tego dnia) oraz HTTP z operatora. **Bez sekretów** — tylko ścieżki i fakty publiczne / techniczne.

## 1. Ról architektury (niezachwiane)

| Warstwa | Rola |
|--------|------|
| **Hosting** `rsperformance.online` | Aplikacja Laravel (canonical), MySQL, Filament, A2A Overture, `/.well-known/*`, publiczne artefakty. |
| **VPS** `185.180.207.211` | n8n, bramka `ai.*`, Qdrant, Postgres (compose), Diagnosta, nightwatch, cięższe joby — **wsparcie** produkcji, nie główny WWW. |
| **Laptop / Cursor** | `ssh_exec` / `vps_exec`, MCP (pin w `.cursor/mcp.json`), edycja `G:\gravity` — **nie** zastępuje SSH ani deployu. |

## 2. Hosting — stan zweryfikowany (SSH 2026-04-22)

| Fakt | Wartość |
|------|---------|
| Użytkownik SSH | `tyurjydtpw` |
| Host / port (domyślne) | `s181.cyber-folks.pl:222` (w `ssh_exec.py`) |
| Katalog aplikacji | `/home/tyurjydtpw/domains/rsperformance.online/laravel` |
| PHP (CLI) | **8.5.3** |
| Laravel | **13.1.1** |
| Hasło / klucz | `CYBERFOLKS_SSH_PASSWORD` / `cs.txt` / `mcp.env` — patrz `RELAY` §5; upload: `python ssh_exec.py --upload local remote` |
| Regeneracja artefaktów | `php85 artisan search:artifacts-generate`, potem `optimize:clear` (po zmianach w discovery) |
| **Backup** przed plikiem | `cp plik plik.bak_<agent>_<cel>` — dyrektywa w `gravity-directives.mdc` |

## 3. VPS — stan zweryfikowany (SSH 2026-04-22)

| Fakt | Wartość |
|------|---------|
| Hostname / IP | `vps72785462` / `185.180.207.211` |
| Użytkownik | `rsops` (klucz `C:\Users\oli22\.ssh\cyberfolks_rsa`) |
| Kernel / OS | `6.8.0-106-generic` / Ubuntu (24.04 z `vps.md`) |
| Dysk `/` | **~98G całość, ~57G użyte, ~36G wolne (~62% use)** — miejsce OK na krótką perspektywę |
| Caddy | **active** |
| n8n (publiczny front) | `https://auto.rs3d.pl` — z VPS: `healthz` → **200** |
| **Kontenery Docker (nazwa → status, skrót)** | `n8n` Up; `n8n-mcp` Up (healthy); `umami` + `umami-db` Up; `compose-postgres-1` Up; `compose-qdrant-1` Up; `rs-support-plane-app/horizon` Up; nightwatch agenty Up; `compose-uptime-kuma-1` Up; `compose-searxng-1` Up |
| AI gateway (lustro plików) | `/srv/ai-gateway/.well-known/` — m.in. `openapi.json` / `a2a.json` / `agent-card.json` (sync z kanonu przez `sync.sh`; YAML mirror wg `vps.md`) |
| Qdrant / Postgres | W compose — **dostęp z laptopa tylko tunel SSH**; MCP `postgres-vps-wow` / `qdrant-vps-wow` w Cursowie z `mcp.env` |

## 4. Sondy HTTP (z operatora, 2026-04-22)

Wszystkie poniższe zwróciły **200** (HEAD/GET w zależności od sondy):

- `https://rsperformance.online/`
- `https://rsperformance.online/health`
- `https://rsperformance.online/.well-known/a2a.json`
- `https://rsperformance.online/.well-known/ai-resources.json`
- `https://ai.rsperformance.online/.well-known/answer-routing.json`
- `https://ai.rsperformance.online/.well-known/openapi.json`
- `https://ai.rsperformance.online/.well-known/openapi.yaml`
- `https://auto.rs3d.pl/healthz`

## 5. Narzędzia pracy (kwiecień 2026+)

- **Skille:** `rs-a2a-wow-2026`, `rs-n8n-wow-2026`, `rs-discovery-wow-2026`, `rs-robots-wow-2026`, `rs-schema-wow-2026` + indeks w `RELAY` §7.
- **MCP (repo):** `.cursor/mcp.json` — audyt: `npm run mcp-verify` / `tools/mcp-verify.cmd`; install: `npm run mcp:install` / `composer mcp-install`.
- **Bramka po deploy na hostingu:** `composer a2a:sync-gateway` (Python `vps_ai_gateway_sync_apr2026.py`).

## 6. Znane ograniczenia / uwagi (nie blokery)

- Lokalny skrypt `scripts/smoke_aeo_gateway_openapi_canonical.py` na tym Windows zwrócił `PermissionError` (SSL / keylog env) — zamiast tego użyto PowerShell / ręcznych HEAD; logika sondy nadal wiarygodna na czystym stdlib w CI.
- `qdrant-vps-wow` w Cursorze wymaga `uv` + `QDRANT_*` (patrz `RELAY` §6) — to warstwa operatora, nie serwera WWW.

## 7. Zielone światło (jak pracować dalej)

1. Zmiany **produkcyjne w PHP** → backup na hostingu → `upload` albo `git pull` (wg procedury) → `search:artifacts-generate` gdy dotyczy discovery.  
2. Zmiany w **katalogu publicznym / gateway** → po hostingu **sync bramki** na VPS.  
3. Zmiany tylko w **docs / Cursor / skryptach lokalnych** — bez SSH, chyba że skrypt to orchestruje.  
4. Zawsze: `RELAY` → `vps` → `AGENTS` przed głębokim refaktorem.

---
*Artefakt generowany w sesji audytu; można go aktualizować przy większych migracjach infrastruktury.*

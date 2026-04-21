> LIVE VPS NOTE (2026-03-29)
> Current verified VPS support-plane runtime is Laravel 13.1.1 / PHP 8.5.3.
> Older Laravel 12 references in this file are historical.

# VPS Cyber_Folks — stan roboczy

## Rola VPS (jedna linia)

- **Hosting** (`rsperformance.online`) = **canonical** — publiczna strona, Filament, MySQL, treści.
- **VPS** = **główna płaszczyzna wsparcia** dla hostingu: async, n8n, **n8n-mcp**, kolejki, AI gateway, Qdrant, backupy/artefakty, monitoring. To jest **realne wsparcie produkcji**, nie „drugi hosting strony”.
- Nie zamieniać ról: publiczny runtime aplikacji pozostaje na hostingu; VPS nie jest głównym serwerem WWW marki.

## Serwer

- **Hostname**: `vps72785462`
- **Adres IP**: `185.180.207.211`
- **Panel zarządzania**: [https://vps.cyberfolks.pl/](https://vps.cyberfolks.pl/)
- **System**: `Ubuntu Server 24.04.4 LTS`

## SSH

- **Klucz aktywny lokalnie**: `C:\Users\oli22\.ssh\cyberfolks_rsa`
- **SSH bez hasła**: działa
- **Host key**: dodany do `known_hosts`
- **Stan SSHD**:
    - `PermitRootLogin prohibit-password`
    - `PasswordAuthentication no`
    - `PubkeyAuthentication yes`
- **Użytkownicy administracyjni**:
    - `root` — tylko po kluczu
    - `rsops` — po kluczu + `sudo`

## Sekrety

- **Hasło root**: nie trzymać w repo / rotate jeśli było gdziekolwiek wklejane
- **API key / hash panelu VPS**: nie trzymać w repo / rotate jeśli były gdziekolwiek wklejane

## Docelowy układ logiczny VPS

To jest podział logiczny na jednej partycji `98G`, nie sztywny podział partycjami.

### Budżet przestrzeni

- **20–25 GB** — system i narzędzia
- **35–45 GB** — Diagnosta RS
- **20–25 GB** — backupy
- **reszta** — bufor operacyjny

### Utworzone katalogi

- `/srv/diagnosta/app`
- `/srv/diagnosta/data`
- `/srv/diagnosta/exports`
- `/srv/diagnosta/uploads`
- `/srv/diagnosta/logs`
- `/srv/diagnosta/tmp`
- `/srv/backups/site`
- `/srv/backups/vps`
- `/srv/backups/db`
- `/srv/backups/artifacts`
- `/srv/backups/logs`
- `/opt/rs-tools`
- `/srv/workspaces`

## Hardening wykonany

- `ufw` aktywny:
    - `22/tcp`
    - `80/tcp`
    - `443/tcp`
- `fail2ban` aktywny
- `root` loguje się tylko kluczem
- `rsops` ma `sudo`

## MCP / Caddy

### n8n-mcp (pakiet npm `n8n-mcp`) — **serwer na VPS, nie na laptopie**

- **Źródło prawdy operacyjnej:** proces MCP (Node) działa na **VPS** `185.180.207.211` — w tym samym „support plane” co n8n w Dockerze (`127.0.0.1:5678` → publicznie `https://auto.rs3d.pl` przez Caddy). Cursor / IDE **nie musi** hostować serwera MCP lokalnie.
- **Lokalny** `G:\gravity\tools\n8n-mcp-runtime\run-n8n-mcp.ps1` to **opcjonalny** launcher deweloperski (stdio na Windows), gdy chcesz MCP bez SSH; produkcyjnie agent ma patrzeć na **VPS**.
- **API n8n** nadal: Public REST **v1**, `{N8N_API_URL}/api/v1/*`, nagłówek `X-N8N-API-KEY` — ten sam kontrakt, niezależnie czy wołasz z MCP na VPS, z Filament na hostingu, czy ze skryptu.
- Konkretny publiczny URL / port HTTP MCP na VPS (jeśli wystawiony za Caddy): utrzymuj w **sekretach operatora** / `HANDOFF`, nie w commicie; w repo jest kod w `mcp-servers/n8n-mcp/` i wzorce nginx w `scripts/` (dostosuj do swojej domeny).

### Diagnosta / inne MCP

- **Docelowa domena MCP**: `mcp.rs3d.pl`
- **Aktualny blocker**: DNS dla `mcp.rs3d.pl` jeszcze nie wskazuje na `185.180.207.211`
- **Fallback domena gotowa po stronie VPS**: `cp.rs3d.pl`
- **Caddy**: zainstalowany i aktywny
- **FastMCP**: zainstalowany w `/opt/rs-tools/venvs/mcp`
- **Usługa systemd**: `diagnosta-mcp.service` aktywna
- **Aplikacja MCP**: `/srv/diagnosta/app/server.py`
- **Aktywne site files Caddy**:
    - `/etc/caddy/sites-enabled/mcp.rs3d.pl.Caddyfile`
    - `/etc/caddy/sites-enabled/cp.rs3d.pl.Caddyfile`
- **Lokalny endpoint MCP**:
    - `http://127.0.0.1:8001/`
    - transport: `streamable-http`
- **Startowe narzędzia MCP**:
    - `health`
    - `list_sources`
    - `search_notes`
    - `read_note`
    - `write_intake_note`

## Rola VPS w projekcie

- **nie przenosimy** publicznej strony z Cyber_Folks `wp_SPRINT!`
- VPS ma być:
    - runnerem automatyzacji i testów
    - zapleczem backupów i archiwizacji
    - miejscem pod prywatną aplikację `Diagnosta RS`
    - miejscem dla cięższych cronów / generatorów artefaktów / log processingu
- **Qdrant na VPS**: wektorowa pamięć warsztatu (`rs_static_knowledge`, `rs_dynamic_knowledge`, itd.) — dane produkcyjne z **MySQL na hostingu** `rsperformance.online` mają być synchronizowane do Qdrant przez joby support-plane (RAG), zamiast kazać agentom czytać produkcyjną bazę wprost. Publiczny opis polityki: `/.well-known/ai-resources.json` → `knowledge_plane` oraz `RELAY.md`.
- **AI gateway (`/srv/ai-gateway`)**: `sync.sh` ciągnie artefakty z kanonu; kontrakt `/.well-known/openapi.json` na bramce jest utrzymywany na VPS. Od **2026-04-21** na końcu `sync.sh` wywoływane jest `python3 /srv/ai-gateway/bin/mirror_openapi_yaml.py` (źródło w repo: `scripts/vps_ai_gateway_mirror_openapi_yaml.py`) — generuje **`openapi.yaml`** z JSON (PyYAML), żeby narzędzia wymagające YAML nie dostawały 404 przy każdym pełnym syncu.
- **Diagnosta API** (`/home/rsops/rs-knowledge/app`, `uvicorn` `127.0.0.1:8081`, unit `diagnosta-api.service`): `POST /internal/content-sync` — upsert do tabeli Postgres `documents` + kolekcji `rs_dynamic_knowledge`; od **2026-04-12** pole `deleted: true` na elemencie usuwa wiersz po `external_id` i punkt Qdrant (punkt ID = `documents.id`). Laravel wywołuje `removeModel()` z observerów przy cofnięciu publikacji / `deleted`.

## Stan na 2026-03-09 wieczor

- `mcp.rs3d.pl` nadal ma niespojny DNS i przez to Caddy nie moze uzyskac certyfikatu.
- `cp.rs3d.pl` jest przygotowane po stronie reverse proxy i moze zostac odpalone od razu po dodaniu rekordu `A -> 185.180.207.211`.
- Nie ma potrzeby zakladania osobnej strefy DNS w Solus, jesli autorytatywny DNS zostaje w Cyber_Folks dla `rs3d.pl`.

## Stan na 2026-03-10 po polnocy

- `mcp.rs3d.pl` ma poprawny publiczny DNS na `185.180.207.211` i uzyskany certyfikat TLS przez Caddy.
- Publiczne testy:
    - `GET https://mcp.rs3d.pl/healthz` -> `200 OK`
    - `GET https://mcp.rs3d.pl/` -> `405` z `Allow: GET, POST, DELETE` (prawidlowe dla endpointu MCP streamable-http)
- `diagnosta-mcp.service` aktywny, `fastmcp` slucha na `127.0.0.1:8001`.
- `cp.rs3d.pl` nadal nie jest potrzebne operacyjnie; host docelowy to `mcp.rs3d.pl`.

## Stan na 2026-03-10 21:xx - Redis + Ops stack

- Shared hosting korzysta juz z dedykowanego Redis na VPS zamiast limitowanego Redis Cyber_Folks.
- Redis VPS:
    - `185.180.207.211:6380`
    - ACL user `rsprod`
    - `appendonly yes`
    - `maxmemory 768mb`
    - `allkeys-lru`
- Potwierdzenie z produkcji:
    - cache write/read -> OK
    - Laravel widzi host `185.180.207.211`, port `6380`, user `rsprod`
- Publicznie live:
    - `https://status.rs3d.pl`
    - `https://analytics.rs3d.pl`
- `n8n` uruchomione lokalnie na VPS (`127.0.0.1:5678`) i podpiete pod Caddy dla `auto.rs3d.pl`, ale publiczny DNS dla `auto` nadal nie jest domkniety.

## Stan na 2026-03-10 21:xx - Filament / n8n handoff

- Shared hosting ma ju� prywatny panel operacyjny pod dalsze spi�cie z VPS:
    - `admin/automation-center`
    - `admin/diagnosta-center`
- `AutomationCenter` pokazuje status `n8n`, `status.rs3d.pl`, `analytics.rs3d.pl`, `mcp.rs3d.pl`, AI artifacts i aktywnego Redis na VPS.
- `DiagnostaCenter` ustawia jeden `n8n` z dwoma strumieniami `OPS` i `DIAGNOSTA` jako kierunek dalszego rollout'u.
- Publiczny DNS dla `auto/status/analytics` nadal bywa niesp�jny lokalnie; po stronie VPS reverse proxy i kontenery s� gotowe.

## Stan na 2026-03-19 03:43 CET - Support-plane Laravel

- Faktyczny Laravel na VPS znajduje sie w /srv/workspaces/rs-support-plane.
- Stack uruchomiony w Docker:
    - s-support-plane-app
    - s-support-plane-horizon
    - s-support-plane-pulse-worker
- Po audycie i bezpiecznym update:
    - Laravel 12.55.1
    - PHP 8.5.3
    - Horizon 5.45.4
    - Pulse 1.7.1
    - MCP 0.6.3
    - AI 0.3.2
- Modele Vertex support-plane:
    - analysis: gemini-2.5-pro
    - publish: gemini-2.5-flash
- mcp.rs3d.pl/healthz -> HTTP 200 po restarcie kontenerow.
- Pelny Laravel 13 upgrade support-plane nie zostal wdrozony, bo obecna oficjalna sciezka zaleznosci w tym stacku nadal blokuje bezpieczny rollout bez wchodzenia w niestabilne warianty.

## 2026-03-19 04:21 CET - VPS L13 / NIGHTWATCH READY

- VPS `rs-support-plane` podniesiony do `Laravel 13.1.1`.
- Usunieto `Pulse`, dodano `Nightwatch`, podbito `Tinker` do `3.0.0`.
- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, scaffold `NIGHTWATCH_*` dodany.
- Compose: `rs-support-plane-pulse-worker` usuniety, `rs-support-plane-nightwatch-agent` dodany jako profil `nightwatch`.
- Support-plane po migracji: HTTP OK, `mcp.rs3d.pl/healthz` OK, Horizon running.
- Brakujacy element do pelnej aktywacji Nightwatch: `NIGHTWATCH_TOKEN`.

## 2026-03-21 03:55 CET - Support-plane intake lane

- `auto.rs3d.pl/support-plane/*` byl podpiety w Caddy do martwego `127.0.0.1:8091`, co powodowalo `HTTP 502` dla signed intake z hostingu.
- Ingress zostal naprawiony: `/etc/caddy/sites-enabled/auto.rs3d.pl.Caddyfile` proxyuje teraz `/support-plane/*` do dzialajacego Laravel support-plane na `127.0.0.1:8000`.
- Backup ingress: `/etc/caddy/sites-enabled/auto.rs3d.pl.Caddyfile.bak_codex_supportplane_ingress_20260321`.
- Po reloadzie Caddy: `https://auto.rs3d.pl/support-plane/api/internal/support-events` zwraca `405 Allow: POST`, co potwierdza zywy endpoint intake.
- Support-plane przyjmuje teraz signed event `search_ops.packet_ready`, kolejkuje `search_ops.packet_ingest` i zapisuje `search_ops.priority_brief` w `support_artifacts`.
- `SupportArtifactRunner` ma trwaly backoff dla retryable analysis failures (`Vertex 429`, `RESOURCE_EXHAUSTED`, `5xx`, timeouty) przez metadata `analysis_retry_not_before`.

## 2026-03-21 04:25 CET - BLOG SUPPORT-PLANE (GATED)

- Dodano na VPS obsluge eventu `blog.generation_requested`, jobu `blog.generation`, artifactu `blog.draft_input` oraz callback sender do hostingu `/api/blog/draft`.
- Dodano tez fallback HTTP client do hostingu `/api/blog/pipeline/run`, ale aktualnie ten fallback zwraca `HTTP 500`, a bezposredni structured JSON z Vertex nadal bywa niepoprawny dla blog flow.
- Aktualne env na VPS: `SUPPORT_PLANE_BLOG_CALLBACK_*` i `SUPPORT_PLANE_BLOG_PIPELINE_*` sa ustawione.
- Sciezka zostala swiadomie zostawiona jako gated/off-default do dalszego debugowania. Nie traktowac jej jeszcze jako green production lane.

## 2026-03-21 05:15 CET - BLOG SUPPORT-PLANE DISPATCH FALLBACK

- `SUPPORT_PLANE_BLOG_PIPELINE_URL` wskazuje juz na `https://rsperformance.online/api/blog/pipeline/dispatch`, nie na synchroniczne `run`.
- Artifact runner na VPS traktuje ten fallback jako delegacje do hostingu i moze domknac `blog.draft_input` bez wpadania w HTTP timeout/500 po stronie heavy request-response.
- Default lane nadal OFF do czasu finalnej weryfikacji draft creation na hostingu i strategii dla Telegram ack.

## 2026-03-21 05:35 CET - BLOG SUPPORT-PLANE ACTIVE PARTIALLY

- Na VPS lane bloga jest juz aktywny operacyjnie dla ruchu inicjowanego z Filament i crona po stronie hostingu.
- `blog.draft_output` konczy sie jako `provider=hosting-pipeline-fallback`, `delegated=true`, a fallback uderza w `https://rsperformance.online/api/blog/pipeline/dispatch`.
- Telegram nadal nie jest ustawiony jako default consumer tego lane i pozostaje po stronie hostingu.

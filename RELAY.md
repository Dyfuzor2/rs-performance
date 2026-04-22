# RELAY - Nadrzedna Mapa Projektu RS Performance

> Ten plik jest pierwszym punktem wejscia dla kazdego agenta.
> Po przeczytaniu `RELAY.md` agent ma obowiazek wczytac wszystkie dalsze dyrektywy projektu.
> `RELAY.md` ma byc indeksem calego systemu: dyrektywy, architektura, credentials map, narzedzia, workflow i pliki zrodlowe.
> Nie przechowuj tutaj samych sekretow. Trzymaj tutaj tylko ich lokalizacje.

## 1. Kolejnosc Ladowania Dyrektyw

**Cursor IDE:** przy starcie sesji wczytywane są automatycznie m.in. `.cursor/rules/agent-start-inventory.mdc` oraz `gravity-directives.mdc` (`alwaysApply: true`) — krótki **inventory narzędzi** i sposób użycia; pełna mapa nadal zaczyna się od tego pliku.

Kazdy agent ma ladowac projekt w tej kolejnosci:

1. `G:\gravity\RELAY.md`
2. `G:\gravity\HANDOFF.md`
3. `G:\gravity\plan.md`
4. `G:\gravity\GEO.md` — mapa **GEO vs AEO**, kanoniczny stos skilli, regula deduplikacji `geo-*` vs `geo-geo-*` (czytac przed audytem GEO / cytowalnoscia)
5. `G:\gravity\AGENTS.md`
6. `G:\gravity\CLAUDE.md` jesli agent z tego korzysta
7. dopiero potem pozostale pliki specjalistyczne

To jest dyrektywa nadrzedna:

- `RELAY.md` zawsze czytane pierwsze
- wszystkie pozostale dyrektywy zawsze ladowane po `RELAY.md`

## 2. Najwazniejsze Dyrektywy Projektu

To jest skrot najwazniejszych zasad. Pelne brzmienie siedzi w plikach dyrektyw wskazanych nizej.

- **Priorytet pracy (2026+):** domyślnie **hosting + VPS** (zmiany produkcyjne, bramka `ai.*`, n8n, bazy, tunele, sync); repo lokalne i tooling MCP to **środek** do tego celu, chyba że zadanie wyraźnie jest „tylko lokalnie”. Pełna dyrektywa: **`AGENTS.md` — _Dyrektywa priorytetu pracy_**.
- **Audyt operacyjny hosting + VPS (snapshot, kwiecień 2026):** `docs/ops/HOSTING-VPS-AUDIT-2026-04-22.md` — sciezki SSH, wersje PHP/Laravel, kontenery, sondy HTTP, zasady pracy.
- Produkcja musi dzialac.
- Shared hosting `rsperformance.online` jest zawsze canonical production runtime.
- VPS jest realnym wsparciem produkcji:
    - async execution
    - AI control plane
    - queue workers
    - artifacts
    - knowledge/search/enrichment
    - monitoring / backup / restore support
- Nie wolno robic z VPS glownego publicznego runtime.
- Wszystkie nowe decyzje technologiczne maja byc oparte tylko o standard `2026+`.
- Zrodla decyzji:
    - top-tier GitHub
    - oficjalna dokumentacja
    - zywe fora i dyskusje tematyczne
- AEO jest najwazniejsze.
- AEO ma byc zawsze `top of the top 2026+`.
- SEO jest wtornym priorytetem po AEO.
- Kazdy agent ma sprawdzac, czy zmiana poprawia:
    - AI discovery
    - answer-first structure
    - provenance
    - freshness
    - entity clarity
    - MCP / AI-readable surfaces
- Wszystkie istotne operacje wykonawcze maja byc robione przez SSH na serwerach, nie lokalnie.
- Backup przed kazda zmiana na hostingu i na VPS jest obowiazkowy.

## 3. Architektura Nadrzedna

### Hosting canonical

Na hostingu zostaje:

- publiczny frontend
- publiczny blog
- admin Filament
- canonical database writes
- operator review / approve / publish
- source of truth dla publicznych tresci i stanu aplikacji

### VPS support plane

**VPS jest głównym hubem wsparcia** dla hostingu canonical (ten sam sens co w `vps.md`: realne wsparcie operacyjne, nie konkurencja publicznego URL).

Na VPS sa tylko rzeczy wspierajace:

- async jobs
- AI generation
- image generation
- DTC / knowledge / enrichment
- heavy cron and artifacts
- search plane
- monitoring
- restore / backup support

### MySQL (hosting) -> Qdrant (VPS) — plaszczyzna wiedzy dla AI

- **Canonical**: `rsperformance.online` na Cyber-Folks — **MySQL** to zrodlo prawdy dla zapisow operatora i stanu biznesowego publicznej aplikacji.
- **VPS**: ten sam support plane co bramka `ai.rsperformance.online` — **Qdrant** (`rs_static_knowledge`, `rs_dynamic_knowledge` i pokrewne) przechowuje **embeddingi / wektorowa pamiec** zsynchronizowana z danymi produkcyjnymi (joby na VPS, nie bezposrednie crawlowanie MySQL przez boty).
- **A2A (Agent-to-Agent) vs Qdrant:** to **dwie rozne role**. **A2A** to publiczny kontrakt dla agentow (Overture): `/.well-known/a2a.json`, JSON-RPC/REST na **kanonicznym hostingu** (`rsperformance.online`) — discovery, taski, artefakty. **Qdrant na VPS** to **magazyn wektorowy** dla RAG / semantic search, za **bramka** `ai.*` i workerami, a nie “nowy A2A”. Agent card moze wystawiac skill `gateway-semantic-routing` (mapowanie intencji), ale **nie zastepuje** indeksu Qdrant; Cursor MCP `qdrant-vps-wow` sluzy operatorowi do bezpiecznego podgladu pamieci (tunel), nie do konfuzji z punktem Overture.
- **Lokalnie (Cursor / dev)**: `G:\gravity\tools\qdrant-local-runtime\` — Docker **Qdrant** na `127.0.0.1:6333`, kolekcje `rs_local_*`, MCP w `.mcp.json` jako `qdrant-rs-*-local` (ten sam `mcp-server-qdrant.exe` co `tools/qdrant-mcp-runtime`). Nie zastepuje VPS; sluzy eksperymentom RAG bez ryzyka dla wektorow produkcyjnych.
- **Hub WOW (jeden entry point)**: `tools/gravity-studio-wow-runtime/setup-gravity-studio-wow.ps1` — **PHP WOW gate** + GitHub MCP + Laravel Boost + Qdrant + **RAG readyz**; `wow.html` (quick actions, **paleta Ctrl+K**, motyw auto/jasny/ciemny + `localStorage`, OG meta, Speculation Rules, względne decki); **`serve-wow-hub.ps1 -Open`**; **`launch-gravity-studio-wow.ps1`**; **`-NoBrowser`** dla agentów.
- **Pint (agent / Cursor)**: `G:\gravity\tools\laravel-pint-wow-runtime\run-pint.ps1` — ten sam resolver PHP co Boost MCP (`Resolve-GravityPhp` + WinGet) oraz **auto `Ensure-GravityPhpIni.ps1`** (WinGet bez `php.ini` → `mbstring` + Laravel exts); `GRAVITY_SKIP_PHP_INI=1` pomija. Np. `run-pint.ps1 --dirty` przed commitem.
- **RAG lokalny (sanity)**: `G:\gravity\tools\qdrant-local-runtime\verify-rag-ready.ps1` — `readyz` + podpowiedz MCP `qdrant-rs-*-local` po starcie Dockera.
- **Dla agentow AI**: do szerokiego RAG i semantyki uzywaj **gateway semantic search** i narzedzi MCP / Qdrant wskazanych w tym indeksie; **nie** obciazaj shared-host MySQL masowym odczytem. Fakty cytowalne dla uzytkownika koncowego nadal weryfikuj na **kanonicznych URL i eksportach JSON** (np. `ai-resources.json`, freshness, priority paths).
- **Publiczny kontrakt** dla modeli: pole `knowledge_plane` w `/.well-known/ai-resources.json` (generowane przez `SearchArtifactFactory`).
- **Gateway OpenAPI (YAML + JSON):** kanon serwuje m.in. `/.well-known/openapi.yaml` (YAML) obok JSON; na bramce `ai.rsperformance.online` utrzymuj ten sam kontrakt jako `/.well-known/openapi.yaml` na VPS (skrypt / upload z `openapi.json`), żeby klienci wymagający sufiksu `.yaml` nie dostawali 404 przy pełnym równoległym `openapi.json`.

### Czego nie wolno robic

- nie przenosic publicznej strony na VPS
- nie przenosic canonical admin runtime na VPS
- nie budowac drugiego publicznego app servera jako konkurencji dla hostingu
- nie dodawac infrastruktury tylko dlatego, ze jest modna

## 4. Gdzie Sa Pelne Dyrektywy

| Plik                        | Rola                                                                                               | Status                                                |
| --------------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| `G:\gravity\RELAY.md`       | pierwszy punkt wejscia i indeks                                                                    | nadrzedny                                             |
| `G:\gravity\HANDOFF.md`     | biezacy stan, ostatni agent, next steps, aktywne zasady sesji                                      | obowiazkowy po RELAY                                  |
| `G:\gravity\plan.md`        | master plan, architektura, priorytety, blueprint VPS/support-plane/AEO                             | obowiazkowy po HANDOFF                                |
| `G:\gravity\GEO.md`         | GEO (Generative Engine Optimization): relacja do AEO, indeks skilli, regula `geo-*` vs `geo-geo-*` | obowiazkowy przy pracy nad GEO / audytem AI citations |
| `G:\gravity\AGENTS.md`      | glowne dyrektywy operacyjne dla agentow                                                            | obowiazkowy                                           |
| `G:\gravity\CLAUDE.md`      | kopia / wariant dyrektyw dla agentow Claude                                                        | obowiazkowy dla Claude                                |
| `G:\gravity\handoff-log.md` | append-only log zmian produkcyjnych                                                                | obowiazkowy po zmianie prod                           |
| `G:\gravity\SESSION_LOG.md` | append-only log sesji agentow                                                                      | obowiazkowy po sesji                                  |
| `G:\gravity\vps.md`         | stan VPS, role, hardening, uslugi, support-plane                                                   | czytac przy pracy na VPS                              |
| `G:\gravity\vertex.md`      | Vertex / GCP / modele / service accounts                                                           | czytac przy AI                                        |
| `G:\gravity\start.md`       | plik legacy / zdekapitowany, tylko pomocniczo                                                      | nie traktowac jako glowny source of truth             |

## 5. Credentials Map - Lokalizacje Sekretow, Loginow, API, JSON

W `RELAY.md` trzymamy tylko lokalizacje i opis. Nie kopiujemy tu samych hasel i tokenow.

### Hosting

| Typ                                 | Lokalizacja                                                                                     |
| ----------------------------------- | ----------------------------------------------------------------------------------------------- |
| SSH hosting - instrukcje i kontekst | `G:\gravity\AGENTS.md`, `G:\gravity\HANDOFF.md`, `G:\gravity\start.md`                          |
| Host / user / port dla hostingu     | `G:\gravity\AGENTS.md`                                                                          |
| Windows SSH executable              | `C:\Windows\System32\OpenSSH\ssh.exe`                                                           |
| Helper command                      | `G:\gravity\ssh_exec.py`                                                                        |
| Hasło SSH (opcjonalnie plik)        | `G:\gravity\cs.txt` — pierwsza niepusta linia (gitignored), albo `CYBERFOLKS_SSH_PASSWORD_FILE` |

**Deploy slice — AEO discovery (nagłówki Link, `ai-resources.json`, `robots.txt` komentarze):** `ssh_exec.py` ładuje `.cursor/mcp.env` (`gravity_cursor_env`), potem hasło z `CYBERFOLKS_SSH_PASSWORD` **albo** z pliku: `cs.txt` w korzeniu repo **albo** ścieżka w `CYBERFOLKS_SSH_PASSWORD_FILE` (wartości nie commituj). Backupy per plik na serwerze (`cp plik plik.bak_<agent>_<cel>`), potem `python ssh_exec.py --upload <local> <remote>` dla `app/Support/RsUri.php`, `app/Http/Middleware/AiCitationHeaders.php`, `app/Support/Search/AiDiscoveryArtifactBuilder.php`, `app/Support/Search/SearchArtifactFactory.php` w katalogu Laravel na hostingu (ścieżka jak w `AGENTS.md`). Na końcu: `php85 artisan search:artifacts-generate` oraz `php85 artisan optimize:clear` (lub `config:clear` wg procedury), smoke `curl -I` na `/` pod kątem `Link` z `openapi.yaml` i `GET /.well-known/ai-resources.json` (pole `gateway.openapi_yaml`). **Lokalny / CI smoke (stdlib):** `python scripts/smoke_aeo_gateway_openapi_canonical.py` (opcjonalnie `--base` dla stagingu).

### VPS

| Typ                    | Lokalizacja                                  |
| ---------------------- | -------------------------------------------- |
| Stan VPS i role        | `G:\gravity\vps.md`                          |
| SSH key lokalny do VPS | `C:\Users\oli22\.ssh\cyberfolks_rsa`         |
| Helper command         | `G:\gravity\vps_exec.py`                     |
| Backup helper          | `python vps_exec.py --backup /path [suffix]` |

### Google Cloud / Vertex / JSON

| Typ                                | Lokalizacja                                                                       |
| ---------------------------------- | --------------------------------------------------------------------------------- |
| Glowne instrukcje Vertex           | `G:\gravity\vertex.md`                                                            |
| GCP service account JSON lokalnie  | `G:\gravity\diagnosta-489719-96def3352c52.json`                                   |
| Vertex / GCP config file           | `G:\gravity\vertex.md`                                                            |
| Cloud API / klucz plikowy          | `G:\gravity\cloud.md`                                                             |
| JSON i konfiguracja po stronie VPS | `G:\gravity\vps.md` oraz runtime paths zapisane w `HANDOFF.md` / `handoff-log.md` |

### Telegram / GitHub / inne API

| Typ                          | Lokalizacja                                                                                                                                                                                                                                   |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Telegram bot token / dane    | `G:\gravity\telegram.md`                                                                                                                                                                                                                      |
| GitHub token / dane          | `G:\gravity\git.md` jesli istnieje; jesli nie, sprawdz aktualny sekret store poza repo                                                                                                                                                        |
| GitHub MCP (public repos)    | `G:\gravity\tools\github-mcp-runtime\` — `setup-github-mcp-wow.ps1` (albo `install-github-mcp.ps1`) + `test-github-token.ps1`; MCP `github` w `.mcp.json` (read-only, token z env `GITHUB_PERSONAL_ACCESS_TOKEN` lub pierwsza linia `git.md`) |
| Laravel Boost MCP (lokalnie) | `G:\gravity\tools\laravel-boost-mcp-runtime\` — `run-laravel-boost-mcp.ps1` (PHP: PATH, `GRAVITY_PHP`, lub `php.path`); MCP `laravel-boost` w `.mcp.json`; projekt: `G:\gravity` (`php artisan boost:mcp`)                                    |
| Google Cloud API key / dane  | `G:\gravity\cloud.md`                                                                                                                                                                                                                         |

### Runtime secrets na serwerach

| Typ                                          | Lokalizacja                                             |
| -------------------------------------------- | ------------------------------------------------------- |
| Hosting app secrets / `.env`                 | runtime na hostingu, sciezki wg projektu i `HANDOFF.md` |
| VPS support-plane secrets / `.env`           | runtime na VPS, sciezki wg `vps.md` i `HANDOFF.md`      |
| Redis credentials / ACL references           | `G:\gravity\HANDOFF.md`, `G:\gravity\vps.md`            |
| Nightwatch token locations / runtime context | `G:\gravity\HANDOFF.md`, `G:\gravity\handoff-log.md`    |

### Zasada bezpieczenstwa

- `RELAY.md` ma indeksowac lokalizacje sekretow, nie same sekrety.
- Jesli sekret znajduje sie dzis w zwyklym `.md` pliku, traktuj ten plik jako secret-bearing file.
- Nie przenos sekretow do nowych miejsc bez potrzeby.
- Nie commituj sekretow.
- `n8n` jest aktywna czesc architektury VPS (przywrocone 2026-03-31 decyzja usera). Kontener w docker-compose, UI: auto.rs3d.pl, workflow: AI Bot Invitation Hub (cron 1h, IndexNow, Telegram).

### Operator env WOW (kwiecien 2026+) — jeden strzal

- **Zrodlo prawdy lokalnie:** `G:\gravity\cursor.md` (gitignored) + `HANDOFF.md` + skan wybranych `*.json` (OpenRouter, Meta) w korzeniu / `storage/app`.
- **Cel:** `G:\gravity\.cursor\mcp.env` (nigdy commitowac wartosci).
- **Mega-bootstrap (wszystko naraz):** `pwsh -File G:\gravity\tools\bootstrap-operator-wow-stack.ps1` — gcloud installer, env (`cursor.md` lub sam `--check`), lista MCP runtime; opcjonalnie `-InstallDeps` (pelny `npm` jak `install-cursor-mcp-deps.ps1`). Szybko tylko zaleznosci MCP: `npm run mcp:install` albo `composer mcp-install` (to samo co `tools\install-cursor-mcp-deps.ps1`).
- **Tylko env:** `pwsh -File G:\gravity\scripts\sync-operator-env-wow.ps1` — merge + `--check`.
- **Recznie:** `python scripts/hydrate_mcp_env_from_workspace.py` potem `python scripts/hydrate_mcp_env_from_workspace.py --check` (opcja `--json` dla agentow).
- **Walidacja n8n po sync:** `python -c "import sys; sys.path.insert(0,'scripts'); from gravity_cursor_env import require_n8n_api; require_n8n_api(); print('n8n OK')"`
- **n8n VPS JWT wrappers (2026-04+):** `scripts/run_n8n_fleet_verify_vps_auto.py` (fleet verify / definition gate) oraz **`scripts/run_n8n_telegram_wow_suite_vps_auto.py`** (suite 3× PUT Telegram WOW) — jeden `vps_exec` → `N8N_API_URL` + `N8N_API_KEY` w env docelowego procesu; **`--help`** pokazuje docstring wrappera i pelne `--help` skryptu docelowego **bez** drugiego SSH. Skill: `rs-n8n-wow-2026`.
- **n8n Public API (2026-04+):** ten sam kontrakt (`/api/v1/*`, `X-N8N-API-KEY`) dla **Filament** (hosting `.env`), **skryptów** (`mcp.env` / `n8n.txt`) oraz **n8n-mcp** — ale **serwer MCP n8n dziala na VPS** (`vps.md`), nie na laptopie; lokalny `run-n8n-mcp.ps1` to opcjonalny dev. **Domyslna instancja RS:** `https://auto.rs3d.pl` (VPS, flota WOW). **SOCmid** (`n8n-s2.socmid.cloud`) to osobna baza workflowow — osobny URL + klucz, tylko gdy celowo. Szczegoly: `n8n.md`.

### Google Cloud CLI lokalnie (kwiecien 2026+) + MCP statusu

- **Czy instalowac `gcloud` na laptopie?** Tak, jesli chcesz szybkie **ADC** (`gcloud auth application-default login`), testy Vertex / konfiguracja zgodnie z `vertex.md`. **Nie** zastepuje to VPS ani SSH — produkcyjne workloady support-plane zostaja na relay.
- **Instalacja (Windows):** `pwsh -File G:\gravity\tools\gcp-gcloud-wow-runtime\install-gcloud-wow.ps1` (winget: `Google.CloudSDK`). Po instalacji **nowe okno** terminala: `gcloud version`.
- **MCP (read-only, allowlist):** `G:\gravity\tools\gcp-gcloud-wow-runtime\` — `gcloud_wow_version`, `gcloud_wow_config_list`; `npm install` przez `install-gcloud-wow-runtime-deps.ps1`; launcher `run-gcloud-wow-mcp.ps1`. Pelny „GCP admin MCP” celowo **nie** jest tu promowany (wysoka powierzchnia ataku); dokumentacja API przez Context7 / oficjalne zrodla.
- **Telegram MCP (operator):** `G:\gravity\tools\telegram-mcp-runtime\` — Bot API `getMe` + `sendMessage`, ten sam wzorzec co n8n (`run-telegram-mcp.ps1` wczytuje `.cursor/mcp.env`). Wpis serwera: `telegram-rs` w `.mcp.json`.

## 6. Narzedzia i Punkty Wejscia

| Narzedzie                      | Lokalizacja / wejscie                                                                                                                                                            | Rola                                                                                                                                                                                                |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| SSH hosting helper             | `G:\gravity\ssh_exec.py`                                                                                                                                                         | zdalne komendy na hostingu                                                                                                                                                                          |
| SSH VPS helper                 | `G:\gravity\vps_exec.py`                                                                                                                                                         | zdalne komendy na VPS                                                                                                                                                                               |
| AGENTS directives              | `G:\gravity\AGENTS.md`                                                                                                                                                           | operacyjne zasady agentow; sekcja **A2A / Overture / bramka VPS** (polecenia + skille)                                                                                                              |
| A2A / Overture + bramka VPS    | `composer a2a:*`; `G:\gravity\tools\a2a-overture-runtime\`; `scripts\a2a-overture-certify.ps1`, `vps_ai_gateway_sync_apr2026.py`                                                 | Overture certify, Pest, sync lustra `ai.*` (VPS); skille `rs-a2a-wow-2026`, `rs-robots-wow-2026`; `.cursor\hooks`                                                                                   |
| CLAUDE directives              | `G:\gravity\CLAUDE.md`                                                                                                                                                           | wariant dyrektyw                                                                                                                                                                                    |
| Master plan                    | `G:\gravity\plan.md`                                                                                                                                                             | architektura i priorytety                                                                                                                                                                           |
| Current handoff                | `G:\gravity\HANDOFF.md`                                                                                                                                                          | stan biezacy                                                                                                                                                                                        |
| Production log history         | `G:\gravity\handoff-log.md`                                                                                                                                                      | historia zmian                                                                                                                                                                                      |
| Session log                    | `G:\gravity\SESSION_LOG.md`                                                                                                                                                      | historia sesji                                                                                                                                                                                      |
| Cursor MCP config              | `G:\gravity\.cursor\mcp.json`                                                                                                                                                    | Wpisane serwery — patrz notatka **ponizej tabeli** (2026-04+); pelna synchronizacja z tym plikiem, nie z pamieci czatu                                                                              |
| GitHub MCP (oficjalny, RO)     | `G:\gravity\tools\github-mcp-runtime\` — `run-github-mcp.ps1`                                                                                                                    | W Cursor: `github`; token `GITHUB_PERSONAL_ACCESS_TOKEN` lub `git.md` (pierwsza linia); weryfikacja: `test-github-token.ps1`                                                                        |
| Playwright MCP (MS, pin)       | `playwright-wow` w `.cursor/mcp.json` — `npx -y @playwright/mcp@0.0.70`; devDep w `package.json`                                                                                 | [microsoft/playwright-mcp](https://github.com/microsoft/playwright-mcp) — headless Chromium, snapshoty a11y; obok **cursor-ide-browser** w Cursor                                                   |
| Postgres VPS MCP (pin)         | `postgres-vps-wow` — `tools\postgres-vps-mcp-runtime\run-postgres-mcp-wow.ps1`; [mcp-postgres](https://github.com/kristofer84/mcp-postgres) `1.2.1`                              | `POSTGRES_MCP_ENABLED=1` + `DATABASE_URL` w `.cursor/mcp.env`; tunel SSH do `127.0.0.1:5432` na VPS; Docker: `infrastructure/vps/docker/docker-compose.yml`                                         |
| Qdrant VPS MCP (oficjalny)     | `qdrant-vps-wow` — `tools\qdrant-vps-mcp-runtime\run-qdrant-mcp-wow.ps1`; lustrzane: `mcp-servers\qdrant-mcp-official` ([upstream](https://github.com/qdrant/mcp-server-qdrant)) | `QDRANT_MCP_ENABLED=1` + `QDRANT_URL` w `.cursor/mcp.env`; `uv` + `uv sync` w `mcp-servers\qdrant-mcp-official`; tunel SSH do `6333` (Qdrant w compose) — **nie** mylic z A2A / Overture (patrz §3) |
| Audyt MCP (WOW)                | `composer mcp-verify` / `npm run mcp-verify` / `tools\mcp-verify.cmd` (= `tools\verify-cursor-mcp-wow.ps1`)                                                                      | Gdy `composer` w PATH to nie PHP Composer, uzyj `.cmd` lub `powershell -File` (patrz nizej). Klucze `mcp.json` + `node_modules` + `github-mcp-server.exe`                                           |
| MCP env template               | `G:\gravity\.cursor\mcp.env.example`                                                                                                                                             | nazwy zmiennych dla n8n / opcjonalnie Qdrant / MySQL RO                                                                                                                                             |
| n8n MCP zrodlo (repo)          | `G:\gravity\mcp-servers\n8n-mcp`                                                                                                                                                 | pelny kod + docs; wersja npm pin w runtime                                                                                                                                                          |
| n8n MCP runtime (pin)          | `G:\gravity\tools\n8n-mcp-runtime`                                                                                                                                               | `n8n-mcp@^2.47.5` dla spojnosci z `npx`                                                                                                                                                             |
| n8n JWT wrappers (Windows)     | `G:\gravity\scripts\run_n8n_fleet_verify_vps_auto.py`, `G:\gravity\scripts\run_n8n_telegram_wow_suite_vps_auto.py`                                                               | jeden odczyt API key z VPS SQLite (`vps_exec`), env `N8N_*`, `--help` bez drugiego SSH; skill `rs-n8n-wow-2026`                                                                                     |
| Telegram MCP (operator)        | `G:\gravity\tools\telegram-mcp-runtime`                                                                                                                                          | Bot API stdio MCP; `install-telegram-mcp.ps1`, `run-telegram-mcp.ps1`, env z `.cursor/mcp.env`                                                                                                      |
| gcloud WOW (CLI + status MCP)  | `G:\gravity\tools\gcp-gcloud-wow-runtime`                                                                                                                                        | `install-gcloud-wow.ps1` (winget), read-only MCP `gcloud_wow_*`; pelny GCP nadal VPS/SSH                                                                                                            |
| Operator bootstrap WOW         | `G:\gravity\tools\bootstrap-operator-wow-stack.ps1`                                                                                                                              | jeden strzal: gcloud + env + check + MCP foldery; `-InstallDeps` = pelny npm                                                                                                                        |
| Instalacja deps MCP (Windows)  | `npm run mcp:install` / `composer mcp-install` / `G:\gravity\tools\install-cursor-mcp-deps.ps1`                                                                                  | Najpierw `npm install` w **korzeniu** (mcp-postgres, @playwright/mcp + lockfile), potem n8n-mcp + runtimy telegram/gcloud                                                                           |
| n8n docs mirror                | `G:\gravity\research\docs-sources\n8n-docs`                                                                                                                                      | lokalna dokumentacja workflow                                                                                                                                                                       |
| n8n skill pack                 | `G:\gravity\n8n-skills`                                                                                                                                                          | workflow patterns, validation, MCP tools expert                                                                                                                                                     |
| Qdrant MCP (oficjalny, zrodlo) | `G:\gravity\mcp-servers\qdrant-mcp-official`                                                                                                                                     | https://github.com/qdrant/mcp-server-qdrant — uruchomienie wg README (Python/uv); nie wlaczaj w Cursor bez tunelu i klucza                                                                          |

> **Wpis `mcpServers` w `G:\gravity\.cursor\mcp.json` (kwiecien 2026+, jedyne zrodlo prawdy w Cursor):** `laravel-boost` (stdio Boost), `filesystem-gravity` — pakiet **@modelcontextprotocol/server-filesystem**, `fetch` — **@modelcontextprotocol/server-fetch**, `n8n-mcp`, `telegram-rs`, `gcloud-wow` (read-only CLI status), `github` (read-only, oficjalny serwer GitHuba), **`playwright-wow`** — **[@playwright/mcp](https://github.com/microsoft/playwright-mcp)** `0.0.70`, **`postgres-vps-wow`** — [mcp-postgres](https://github.com/kristofer84/mcp-postgres) `1.2.1` (Postgres na VPS: tunel SSH + `DATABASE_URL` w `.cursor/mcp.env`; wylacz: nie ustawiaj `POSTGRES_MCP_ENABLED=1`). **`qdrant-vps-wow`** — [mcp-server-qdrant](https://github.com/qdrant/mcp-server-qdrant) `0.8.1` (`uv run` w `mcp-servers/qdrant-mcp-official`; `QDRANT_MCP_ENABLED=1` + `QDRANT_URL`; tunel do `6333`) — **pamiec wektorowa na VPS**, osobno od **A2A/Overture** na hostingu. Klasyk `@modelcontextprotocol/server-postgres` **nie** uzywamy (deprecated / znane problemy). Zestaw dobrany pod RS zamiast losowych „top GitHub” bez audytu. Sekrety: **`.cursor/mcp.env`** (nigdy commitowac) + `scripts/hydrate_mcp_env_from_workspace.py` / `sync-operator-env-wow.ps1`. Pelny `npm` w runtimach: `npm run mcp:install` / `composer mcp-install` / `tools/install-cursor-mcp-deps.ps1` albo `bootstrap-operator-wow-stack.ps1 -InstallDeps`. Jeden strzal: `tools/bootstrap-operator-wow-stack.ps1` (n8n/telegram/gcloud-wow + check env + **audyt MCP**). **Szybki audyt tylko MCP:** `composer mcp-verify` / `npm run mcp-verify` / `G:\gravity\tools\mcp-verify.cmd` / `tools/verify-cursor-mcp-wow.ps1` (opcjonalnie `-Json` dla agentow).

## 7. Skills i Workflowy

### Skills

| Skill                   | Lokalizacja                                                                                          |
| ----------------------- | ---------------------------------------------------------------------------------------------------- |
| pest-testing            | `G:\gravity\.agents\skills\pest-testing\SKILL.md`                                                    |
| pulse-development       | `G:\gravity\.agents\skills\pulse-development\SKILL.md`                                               |
| tailwindcss-development | `G:\gravity\.agents\skills\tailwindcss-development\SKILL.md`                                         |
| rs-n8n-wow-2026         | `G:\gravity\.agents\skills\rs-n8n-wow-2026\SKILL.md`                                                 |
| rs-aeo-skill            | `G:\gravity\.agents\skills\rs-aeo-skill\SKILL.md`                                                    |
| rs-discovery-wow-2026   | `G:\gravity\.agents\skills\rs-discovery-wow-2026\SKILL.md`                                           |
| rs-a2a-wow-2026         | `G:\gravity\.agents\skills\rs-a2a-wow-2026\SKILL.md`                                                 |
| rs-robots-wow-2026      | `G:\gravity\.agents\skills\rs-robots-wow-2026\SKILL.md` — katalog UA / `config/ai_agents.php` / hook |
| rs-schema-wow-2026      | `G:\gravity\.agents\skills\rs-schema-wow-2026\SKILL.md`                                              |
| GEO (mapa + moduly)     | `G:\gravity\GEO.md` + `G:\gravity\.agents\skills\geo-*\SKILL.md` (nie `geo-geo-*`, patrz GEO.md)     |
| qdrant-memory-market    | `G:\gravity\.agents\skills\qdrant-memory-market\SKILL.md`                                            |
| qdrant-rest-api-market  | `G:\gravity\.agents\skills\qdrant-rest-api-market\SKILL.md`                                          |
| laravel-13-php-85       | `G:\gravity\.agents\skills\laravel-13-php-85\SKILL.md`                                               |
| design-extractor        | `G:\gravity\skills\analysis\`                                                                        |
| realtime-translator     | `G:\gravity\skills\python\`                                                                          |

### Workflows

| Workflow             | Lokalizacja                                            |
| -------------------- | ------------------------------------------------------ |
| Executing Plans      | `G:\gravity\.agents\workflows\executing-plans.md`      |
| Systematic Debugging | `G:\gravity\.agents\workflows\systematic-debugging.md` |

## 8. Relay Protocol - Pre-Flight

Kazdy agent przed praca:

1. Czyta `RELAY.md`
2. Czyta `HANDOFF.md`
3. Czyta `plan.md`
4. Czyta swoje dyrektywy operacyjne:
    - `AGENTS.md`
    - `CLAUDE.md` jesli dotyczy
5. Sprawdza stan drzewa roboczego
6. Sprawdza, czy nie ma aktywnej pracy innego agenta
7. Dopiero potem zaczyna prace

## 9. Relay Protocol - Post-Flight

Po pracy agent:

1. aktualizuje `plan.md`, jesli zmienil plan lub priorytety
2. aktualizuje `HANDOFF.md`
3. dopisuje do `SESSION_LOG.md`
4. jesli zmienil produkcje, dopisuje do `handoff-log.md`
5. jesli zmienil VPS, zostawia jasny slad stanu i lokalizacji zmian

## 10. Aktualny Strategiczny Priorytet

Priorytet nr 1:

- AEO hardening do poziomu `top of the top 2026+`

Priorytet nr 2:

- hosting canonical + VPS support plane wedlug blueprintu z `plan.md`

Priorytet nr 3:

- utrzymanie stabilnej produkcji i bezpiecznych rolloutow

## 11. Szybki Skrót Dla Nowego Agenta

Jesli wchodzisz na projekt pierwszy raz:

- przeczytaj `RELAY.md`
- przeczytaj `HANDOFF.md`
- przeczytaj `plan.md`
- zapamietaj:
    - hosting jest canonical
    - VPS jest wsparciem
    - AEO jest najwazniejsze
    - tylko 2026+
    - A2A: `AGENTS.md` (sekcja A2A) + skill `rs-a2a-wow-2026` + `composer a2a:certify` po deploy
    - sekrety bierz z ich plikow zrodlowych, nie z pamieci czatu

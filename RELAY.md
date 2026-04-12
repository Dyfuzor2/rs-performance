# RELAY - Nadrzedna Mapa Projektu RS Performance

> Ten plik jest pierwszym punktem wejscia dla kazdego agenta.
> Po przeczytaniu `RELAY.md` agent ma obowiazek wczytac wszystkie dalsze dyrektywy projektu.
> `RELAY.md` ma byc indeksem calego systemu: dyrektywy, architektura, credentials map, narzedzia, workflow i pliki zrodlowe.
> Nie przechowuj tutaj samych sekretow. Trzymaj tutaj tylko ich lokalizacje.

## 1. Kolejnosc Ladowania Dyrektyw

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
- **Lokalnie (Cursor / dev)**: `G:\gravity\tools\qdrant-local-runtime\` — Docker **Qdrant** na `127.0.0.1:6333`, kolekcje `rs_local_*`, MCP w `.mcp.json` jako `qdrant-rs-*-local` (ten sam `mcp-server-qdrant.exe` co `tools/qdrant-mcp-runtime`). Nie zastepuje VPS; sluzy eksperymentom RAG bez ryzyka dla wektorow produkcyjnych.
- **Hub WOW (jeden entry point)**: `G:\gravity\tools\gravity-studio-wow-runtime\setup-gravity-studio-wow.ps1` — zbiorcza weryfikacja GitHub MCP + Laravel Boost + (opcjonalnie) Qdrant local, jeden dashboard `wow.html` zamiast wielu okien przegladarki.
- **Pint (agent / Cursor)**: `G:\gravity\tools\laravel-pint-wow-runtime\run-pint.ps1` — ten sam resolver PHP co Boost MCP (`Resolve-GravityPhp` + WinGet); np. `run-pint.ps1 --dirty` przed commitem.
- **RAG lokalny (sanity)**: `G:\gravity\tools\qdrant-local-runtime\verify-rag-ready.ps1` — `readyz` + podpowiedz MCP `qdrant-rs-*-local` po starcie Dockera.
- **Dla agentow AI**: do szerokiego RAG i semantyki uzywaj **gateway semantic search** i narzedzi MCP / Qdrant wskazanych w tym indeksie; **nie** obciazaj shared-host MySQL masowym odczytem. Fakty cytowalne dla uzytkownika koncowego nadal weryfikuj na **kanonicznych URL i eksportach JSON** (np. `ai-resources.json`, freshness, priority paths).
- **Publiczny kontrakt** dla modeli: pole `knowledge_plane` w `/.well-known/ai-resources.json` (generowane przez `SearchArtifactFactory`).

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

| Typ                                 | Lokalizacja                                                            |
| ----------------------------------- | ---------------------------------------------------------------------- |
| SSH hosting - instrukcje i kontekst | `G:\gravity\AGENTS.md`, `G:\gravity\HANDOFF.md`, `G:\gravity\start.md` |
| Host / user / port dla hostingu     | `G:\gravity\AGENTS.md`                                                 |
| Windows SSH executable              | `C:\Windows\System32\OpenSSH\ssh.exe`                                  |
| Helper command                      | `G:\gravity\ssh_exec.py`                                               |

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

## 6. Narzedzia i Punkty Wejscia

| Narzedzie                      | Lokalizacja / wejscie                          | Rola                                                                                                                       |
| ------------------------------ | ---------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| SSH hosting helper             | `G:\gravity\ssh_exec.py`                       | zdalne komendy na hostingu                                                                                                 |
| SSH VPS helper                 | `G:\gravity\vps_exec.py`                       | zdalne komendy na VPS                                                                                                      |
| AGENTS directives              | `G:\gravity\AGENTS.md`                         | operacyjne zasady agentow                                                                                                  |
| CLAUDE directives              | `G:\gravity\CLAUDE.md`                         | wariant dyrektyw                                                                                                           |
| Master plan                    | `G:\gravity\plan.md`                           | architektura i priorytety                                                                                                  |
| Current handoff                | `G:\gravity\HANDOFF.md`                        | stan biezacy                                                                                                               |
| Production log history         | `G:\gravity\handoff-log.md`                    | historia zmian                                                                                                             |
| Session log                    | `G:\gravity\SESSION_LOG.md`                    | historia sesji                                                                                                             |
| Cursor MCP config              | `G:\gravity\.cursor\mcp.json`                  | Laravel Boost, filesystem workspace, fetch, n8n-mcp (env z OS)                                                             |
| MCP env template               | `G:\gravity\.cursor\mcp.env.example`           | nazwy zmiennych dla n8n / opcjonalnie Qdrant / MySQL RO                                                                    |
| n8n MCP zrodlo (repo)          | `G:\gravity\mcp-servers\n8n-mcp`               | pelny kod + docs; wersja npm pin w runtime                                                                                 |
| n8n MCP runtime (pin)          | `G:\gravity\tools\n8n-mcp-runtime`             | `n8n-mcp@^2.47.5` dla spojnosci z `npx`                                                                                    |
| Instalacja deps MCP (Windows)  | `G:\gravity\tools\install-cursor-mcp-deps.ps1` | `npm install` w repo n8n-mcp + runtime                                                                                     |
| n8n docs mirror                | `G:\gravity\research\docs-sources\n8n-docs`    | lokalna dokumentacja workflow                                                                                              |
| n8n skill pack                 | `G:\gravity\n8n-skills`                        | workflow patterns, validation, MCP tools expert                                                                            |
| Qdrant MCP (oficjalny, zrodlo) | `G:\gravity\mcp-servers\qdrant-mcp-official`   | https://github.com/qdrant/mcp-server-qdrant — uruchomienie wg README (Python/uv); nie wlaczaj w Cursor bez tunelu i klucza |

## 7. Skills i Workflowy

### Skills

| Skill                   | Lokalizacja                                                                                      |
| ----------------------- | ------------------------------------------------------------------------------------------------ |
| pest-testing            | `G:\gravity\.agents\skills\pest-testing\SKILL.md`                                                |
| pulse-development       | `G:\gravity\.agents\skills\pulse-development\SKILL.md`                                           |
| tailwindcss-development | `G:\gravity\.agents\skills\tailwindcss-development\SKILL.md`                                     |
| rs-n8n-wow-2026         | `G:\gravity\.agents\skills\rs-n8n-wow-2026\SKILL.md`                                             |
| rs-aeo-skill            | `G:\gravity\.agents\skills\rs-aeo-skill\SKILL.md`                                                |
| rs-discovery-wow-2026   | `G:\gravity\.agents\skills\rs-discovery-wow-2026\SKILL.md`                                       |
| rs-a2a-wow-2026         | `G:\gravity\.agents\skills\rs-a2a-wow-2026\SKILL.md`                                             |
| rs-schema-wow-2026      | `G:\gravity\.agents\skills\rs-schema-wow-2026\SKILL.md`                                          |
| GEO (mapa + moduly)     | `G:\gravity\GEO.md` + `G:\gravity\.agents\skills\geo-*\SKILL.md` (nie `geo-geo-*`, patrz GEO.md) |
| qdrant-memory-market    | `G:\gravity\.agents\skills\qdrant-memory-market\SKILL.md`                                        |
| qdrant-rest-api-market  | `G:\gravity\.agents\skills\qdrant-rest-api-market\SKILL.md`                                      |
| laravel-13-php-85       | `G:\gravity\.agents\skills\laravel-13-php-85\SKILL.md`                                           |
| design-extractor        | `G:\gravity\skills\analysis\`                                                                    |
| realtime-translator     | `G:\gravity\skills\python\`                                                                      |

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
    - sekrety bierz z ich plikow zrodlowych, nie z pamieci czatu

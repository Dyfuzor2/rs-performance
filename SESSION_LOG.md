# SESSION_LOG — RS Performance (append-only)

Format relay: jeden blok na sesję, bez kasowania cudzych wpisów. Starsze wpisy przenosimy do archiwum dopiero gdy plik przekroczy ~50 wpisów (patrz `RELAY.md`).

---

## [2026-04-12] Cursor — `agent-start-inventory.mdc` (alwaysApply) + RELAY §1

### Wykonane

- Nowa reguła `.cursor/rules/agent-start-inventory.mdc` (`alwaysApply: true`): przy starcie sesji w Cursorze agent ma skondensowany opis **co jest w workspace** (SSH/VPS, MCP, Studio WOW, skille, kolejność czytania, git/hygiene) i **jak tego używać**.
- `RELAY.md` §1 — akapit wskazujący auto-ładowanie tej reguły obok `gravity-directives.mdc`.

### Uwagi

- Pełne dyrektywy bez zmian; plik jest indeksem startowym, nie zastępuje `RELAY.md` / `start.md`.
- Duży `git status` z historii multi-agent — nie sprzątany w tej turze; kolejne sesje: celować commity w małe zestawy plików.

---

## [2026-04-11 wieczór CET] Cursor — Blog/newsroom PL + n8n „Select Fresh Story” + Vertex pipeline copy

### Kontekst

Użytkownik: blog polskojęzyczny, pełne polskie znaki; dopięcie „wirtualnej redakcji” i workflow n8n pod standard kwiecień 2026+; na koniec — zapis stanu przed snem.

### Wykonane (repo lokalne, bez potwierdzonego deploy na hosting w tej turze)

- `references/blog_newsroom_crew_2026.md` — poprawki języka PL (źródła, copy desk), bez angielskiego „random” w opisie stocku.
- `n8n_workflow_blog_draft_cadence.json` — node **Select Fresh Story**: `jsCode` po polsku (role, konteksty, `familyLeadMap`, kategorie, dopiski scoringu pod PL/Trójmiasto). JSON waliduje się.
- `.agents/skills/rs-blog-newsroom-wow-2026/SKILL.md` — sekcja **Wirtualna redakcja (PL)** + naprawa formatowania przy `research_brief`.
- `.agents/skills/rs-n8n-wow-2026/SKILL.md` — dopisek, że `editorial_notes` z Select Fresh Story jest po polsku.
- `app/Support/Blog/BlogVertexPipelineService.php` — polskie prompty (research, writer HEREDOC, daily news / premiera, premium review); usunięcie zdublowanych znaków zamiany (U+FFFD) przy frazach typu "zestaw źródeł" / "źródło".

### Ryzyko / uwagi

- W eksporcie workflow n8n może być **token bota Telegram w plaintext** — **rotacja** i trzymanie w credentialach n8n, nie w repo.
- Lokalnie nie uruchamiano `pint` (PHP poza PATH na Windows).

### Kontynuacja (dla następnego agenta)

- Deploy na hosting zmian w `BlogVertexPipelineService.php` (backup, `optimize:clear`, smoke).
- Opcjonalny grep reszty pliku pod mojibake / ASCII-only tam gdzie ma być PL.
- Import workflow na VPS po synchronizacji JSON.
- Produkt: `/blog` z trybem `daily_news` + pola daty — wymaga zmiany `BlogTelegramBotService::handleBlogCommand` (obecnie domyślnie `--editorial-mode=evergreen`).
- Uporządkować `git status` — commit tylko powiązanych plików (był bałagan untracked).

---

## [2026-04-12] Cursor — WOW digest: n8n fleet + Telegram (skills rs-n8n-wow-2026 + telegram-bot-skills)

### Wykonane

- Nowy skrypt `scripts/vps_n8n_telegram_wow_digest.py`: macierz health (5 URL-i), `POST /webhook/bot-invitation-test`, **15 aktywnych workflowów** z ostatnim `status` z `execution_entity`, jedna wiadomość **HTML** na Telegram (target z **RS AI Agent Monitor** `W1xRg73xFDUXYrRI`, bez logowania tokenu).
- Deploy na VPS: `/tmp/vps_n8n_telegram_wow_digest.py`; run: **Telegram HTTP 200**, fleet 15, health 0 fail, webhook 200.
- Aktualizacja skilli: `.agents/skills/rs-n8n-wow-2026/SKILL.md`, `.agents/skills/telegram-bot-skills/SKILL.md`.

### Harmonogram (cron na hoście VPS)

- Trwała ścieżka: `/srv/ops-stack/scripts/vps_n8n_telegram_wow_digest.py` + flaga **`--quiet`**. Instalator: `scripts/vps_install_wow_digest_cron.sh` → **`CRON_TZ=Europe/Warsaw`**, **`0 8 * * *`**, log `/srv/ops-stack/logs/n8n_wow_digest.log`. Uzasadnienie: obraz Docker n8n nie ma Pythona — orchestracja „wow” raportu na hoście obok SQLite.

### Weryfikacja wdrożenia (2026-04-11)

- Backup na VPS przed uploadem: `vps_n8n_telegram_wow_digest.py.bak_cursor_wow_digest_20260411`.
- `vps_exec.py --upload` z repo → `/srv/ops-stack/scripts/`; `bash vps_install_wow_digest_cron.sh` → crontab z `CRON_TZ` + wpis 08:00.
- Smoke: `https://auto.rs3d.pl/healthz` OK; ręczny run skryptu (bez `--quiet`) → Telegram HTTP 200, fleet 15, webhook test 200, health fails 0.

---

## [2026-04-12 ~01:15 CET] Cursor — VPS n8n: import Vertex-patched workflows

### Kontekst

Wdrożenie na żywy instancji Docker `n8n` (v2.14.2) pliku `storage/app/n8n_vertex_patched.json` (hosting jako proxy Vertex: `https://rsperformance.online/api/n8n/vertex/*`), zgodnie ze ścieżką CLI z skill stack (`rs-n8n-wow-2026` / `n8n import:workflow`).

### Wykonane

- Backup przed zmianą: `docker exec n8n n8n export:workflow --all --output=/home/node/.n8n/backup_pre_vertex_import_20260412.json` → na hoście `/srv/ops-stack/n8n/storage/backup_pre_vertex_import_20260412.json` (16 workflowów).
- Upload: `python vps_exec.py --upload .../n8n_vertex_patched.json /srv/ops-stack/n8n/storage/import_vertex_patched.json`.
- Import: `docker exec n8n n8n import:workflow --input=/home/node/.n8n/import_vertex_patched.json` → **Successfully imported 16 workflows** (import wyłączył te workflowy — trzeba ponownie włączyć w UI te, które mają być aktywne).
- Weryfikacja: `GET http://127.0.0.1:5678/healthz` w kontenerze → `{"status":"ok"}`.

### Rollback

- Przywrócić workflowy z `backup_pre_vertex_import_20260412.json` (ten sam mechanizm importu) lub przywrócić starszy stan z backupów SQLite jeśli operacyjnie wymagane (katalog n8n: `/srv/ops-stack/n8n/storage/`).

### VPS desync guard

- Na VPS leżą: `import_vertex_patched.json` (ostatni import), `backup_pre_vertex_import_20260412.json` (stan sprzed importu). Nie nadpisuj ręcznie `database.sqlite` bez backupu.

---

## [2026-04-12 ~01:45 CET] Cursor — VPS n8n: „wow” — masowa reaktywacja po imporcie Vertex

### Kontekst

Po `import:workflow` wszystkie workflowy były nieaktywne; user poprosił o zrobienie tego zamiast ręcznych klików.

### Wykonane

- **`n8n publish:workflow --id=…`** dla całej paczki produkcyjnej (15 workflowów + wcześniej `F6uosr6xSCJZM4fO`).
- **Celowo nie publikowano** drugiego duplikatu nazwy `RS AI Bot Invitation Hub` (`FM1BxBIDKRmhr57i`, mniejszy eksport ~5.4 KB vs canonical `F6uosr6xSCJZM4fO` ~8.1 KB) — uniknięcie podwójnego cron IndexNow co godzinę.
- **`docker compose restart n8n`** w `/srv/ops-stack/compose` — przeładowanie triggerów po komunikacie CLI o restarcie.
- Weryfikacja: `healthz` OK; `list:workflow --active=false` → tylko `FM1BxBIDKRmhr57i`; pozostałe **16 aktywnych**.

### Operator

- Jeśli jednak potrzebujesz **dwóch** hubów zaproszeń, włącz `FM1Bx…` ręcznie w UI (świadomie, ryzyko duplikacji zadań).

---

## [2026-04-12 ~02:30 CET] Cursor — WOW smoke: monitor matrix + webhook FM1Bx + Telegram (n8n 2.14)

### Kontekst

Pełny przebieg `RS Daily Automotive News Drafts` nie daje się uruchomić z CLI/API przy samym `scheduleTrigger` (wymagałby węzła _Execute Workflow_ / _Manual_ albo webhooka — zgodnie z zachowaniem `n8n execute` / public API 405). Zamiast tego: **ten sam zestaw URL-i** co węzeł „Define Health Checks” w `RS AI Agent Monitor` + **produkcyjny webhook** zaproszeń + **jeden komunikat Telegram** spójny z monitorowym kanałem.

### Wykonane

- **FM1Bx** opublikowany wcześniej; smoke: `POST https://auto.rs3d.pl/webhook/bot-invitation-test` → `200`, `Workflow was started`; wykonanie `1054` → `success`.
- Skrypt na VPS: `python3 /tmp/vps_n8n_wow_smoke.py` (kopia w repo: `scripts/vps_n8n_wow_smoke.py`) —10× HTTP jak monitor, webhook, Telegram `sendMessage` (token nie logowany).
- **Daily News**: informacja operacyjna w Telegramie — kolejne sloty harmonogramu `15 8,13,18 * * *` (Europe/Warsaw).

### Bezpieczeństwo

- W workflowach na serwerze nadal są **sekrety w plaintext** (Telegram, klucz pipeline). Rekomendacja na backlog: przenieść do credentials / env i **rotacja** po audycie.

---

## [2026-04-12 ~03:00 CET] Cursor — VPS n8n: dedup „RS AI Bot Invitation Hub” (canonical F6uos)

### Kontekst

Krótko były **dwa aktywne** workflowy o tej samej nazwie (`F6uosr6xSCJZM4fO` + `FM1BxBIDKRmhr57i`) — ryzyko **podwójnego cronu** (IndexNow co godzinę) i duplikacji zadań. User: zostawić lepszy, wolna ręka.

### Decyzja i wykonanie

- **Canonical:** `F6uosr6xSCJZM4fO` — pełniejszy eksport w `storage/app/n8n_vertex_patched.json` (~8.1 KB vs ~5.4 KB na FM1Bx).
- **Wyłączono:** `FM1BxBIDKRmhr57i` — `n8n unpublish:workflow --id=FM1BxBIDKRmhr57i`, potem `docker compose restart n8n` w `/srv/ops-stack/compose`.
- **Backup przed zmianą:** `vps_exec.py --backup /srv/ops-stack/n8n/storage/database.sqlite n8n_hub_dedup` (plik `.bak_n8n_hub_dedup` obok SQLite na hoście).
- **Weryfikacja:** w aktywnych tylko `F6uos…` jako invitation hub; `POST https://auto.rs3d.pl/webhook/bot-invitation-test` → **200**.
- **Repo:** dopisek w `scripts/vps_n8n_wow_smoke.py` (webhook = canonical F6uos).

---

## [2026-04-12 ~01:20 CET] Cursor — Retest bramki `ai.*` + synchronizacja relay (wow)

### Kontekst

Po wdrożeniu naprawy `.htaccess` (literalna spacja w `ChatGPT Atlas` psująca `RewriteCond` na LiteSpeed) potrzebny był **twardy dowód** łańcucha przekierowań i braku pętli, plus aktualizacja `HANDOFF.md` / tego pliku zgodnie z protokołem relay.

### Wykonane (read-only na produkcji w tej mini-sesji)

- Pomiar `curl` z `-w num_redirects` / `url_effective` dla canonical i bramki.
- Smoke ścieżki `/for-agents` na VPS oraz głębokiego linku `/uslugi` z UA bota.

### Wyniki weryfikacji (żywe URL)

Pomiar `redirects` / `final_url`: `curl -L` (follow), `-w '%{num_redirects} %{url_effective} %{http_code}'`.

| Test                                                        | Wynik                                                                                                                                                 |
| ----------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET https://rsperformance.online/` + `User-Agent: GPTBot`  | `redirects=1`, `final_url=https://ai.rsperformance.online/`, `http_code=200`                                                                          |
| `GET https://rsperformance.online/` + browser UA            | `redirects=0`, zostaje na canonical, `200`                                                                                                            |
| `GET https://ai.rsperformance.online/` + `GPTBot`           | `redirects=0`, `200` (brak zwrotnego 302 na canonical — **brak pętli**)                                                                               |
| `GET https://ai.rsperformance.online/` + browser            | `redirects=0`, `200`                                                                                                                                  |
| `GET https://ai.rsperformance.online/for-agents` + `GPTBot` | `200`, nagłówki m.in. `X-AI-Hospitality`, `X-AI-Gateway`, `X-AI-Preferred-Fetch-Order` (w polu wielkość liter może się różnić — `X-Ai-*` vs `X-AI-*`) |
| `GET https://rsperformance.online/uslugi` + `GPTBot`        | `redirects=1`, finalnie `200` na `https://ai.rsperformance.online/uslugi`                                                                             |

### Artefakty repo

- `HANDOFF.md` — sekcje: AKTUALNA OPERACJA (wyczyszczona), OSTATNI AGENT, NASTĘPNE KROKI (naprawiony nagłówek z wcześniejszego mojibake `NAST…PNE`).
- `plan.md` — krótki addendum 2026-04-12 pod fokusem.
- `handoff-log.md` — jeden wpis opisowy (brak nowego deploy na hosting w tej turze).

### Git

- Commit: `15520e6` na gałęzi `feature/v9-architecture-rebuild` — `SESSION_LOG.md`, `HANDOFF.md`, `plan.md`, `handoff-log.md`.

---

## [2026-04-11] Cursor — deploy katalogu AI agentów + test na hostingu

- **Produkcja (Cyber-Folks):** backup `*.bak_cursor_aiagents_deploy_20260411`, upload `config/ai_agents.php`, `TrackAiAgentTraffic.php`, `public_html/.htaccess` z `.htaccess_remote`; `mkdir tests/Unit` + upload `AiAgentsInclusivePolicyTest.php`.
- **Pest:** w pliku testu dodano `uses(Tests\TestCase::class)` — bez tego `config()` nie działa w `tests/Unit` (Pest ładuje `TestCase` tylko dla `Feature` w `tests/Pest.php`).
- **Wynik:** `php85 artisan config:clear`, `php85 artisan test --compact tests/Unit/AiAgentsInclusivePolicyTest.php` → **1 passed**; `php85 -l` OK na wgranych plikach.
- **Smoke:** `FirecrawlAgent` na `/` → `302` → `ai.rsperformance.online`; zwykły UA → `200`.
- **Dokumentacja:** `start.md` (blok LIVE AI AGENT CATALOG), `handoff-log.md` (wpis).

---

## [2026-04-12] Cursor — Ensure-GravityPhpIni (WinGet WOW) + Boost/Pint auto-bootstrap

### Wykonane

- `tools/laravel-boost-mcp-runtime/Ensure-GravityPhpIni.ps1` — idempotentny bootstrap `php.ini` z `php.ini-production` obok `php.exe`, `extension_dir`, odblokowanie `mbstring`, `openssl`, `curl`, `fileinfo`, `intl`, `pdo_mysql`, `zip`, `sodium`, `exif`; szybki exit gdy `mbstring` już załadowany; `-Quiet` dla MCP; `GRAVITY_SKIP_PHP_INI=1` pomija.
- `run-laravel-boost-mcp.ps1`, `run-pint.ps1`, `install-laravel-boost-mcp.ps1` — wywołanie `Ensure-GravityPhpIni.ps1` przed `boost:mcp` / Pint.
- README (boost + pint), `RELAY.md` — dokumentacja i indeks.

### Weryfikacja lokalna

- WinGet `PHP.PHP.8.5`: po skrypcie `php --ini` wskazuje załadowany plik; `Pint 1.29.0` z `run-pint.ps1 --version`.

### Uwagi

- Zmiana dotyczy **lokalnego** `php.ini` w katalogu WinGet użytkownika (nie hosting/VPS).

---

## [2026-04-12] Cursor — Gravity Studio WOW: PHP gate + RAG readyz + `-NoBrowser`

### Wykonane

- `tools/gravity-studio-wow-runtime/setup-gravity-studio-wow.ps1` — na początku **Ensure-GravityPhpIni**; po sukcesie Qdrant **`verify-rag-ready.ps1`**; JSON `last-run-status.json` rozszerzony (`gravityPhpWowOk`, `ragReadyzOk`, …); hub HTML — nowe chipy (PHP gate, RAG) + styl `.pill.warn` dla SKIP.
- `wow.template.html` — lead + statusy; `README.md` — opis; **`param(-NoBrowser)`** (agenty bez auto-otwarcia przeglądarki).
- `RELAY.md` — zaktualizowany opis Hub WOW.

### Weryfikacja

- `setup-gravity-studio-wow.ps1 -NoBrowser` → SUMMARY all OK (Docker + Qdrant + Boost + RAG).

---

## [2026-04-12] Cursor — Gravity Studio hub: clipboard WOW + JSON-LD + motion

### Wykonane

- `wow.template.html` — sekcja **Szybkie akcje** (3 przyciski), toast `#hub-toast`, animacja `rise` na kartach, link `file:///G:/gravity/.mcp.json`, **schema.org WebApplication** w `<head>`.
- `setup-gravity-studio-wow.ps1` — wstrzykiwanie `<script type="application/json" id="gravity-studio-copy">` (studioCmd, mcpBlock, reloadChecklist); inline JS czyta JSON i kopiuje przez Clipboard API.
- `README.md`, `RELAY.md` — opis + uwaga `file://` vs clipboard.

### Weryfikacja

- `wow.html` zawiera `gravity-studio-copy` z poprawnym JSON; setup `-NoBrowser` EXIT=0.

---

## [2026-04-12] Cursor — Studio WOW: fallback Ctrl+C + serve-wow-hub.ps1

### Wykonane

- `wow.template.html` — panel **fallback** (textarea readonly + przyciski Zaznacz), toggle linkiem; przy błędzie Clipboard auto-otwarcie właściwego pola; `text-wrap: balance` na H1.
- `serve-wow-hub.ps1` — statyczny serwer `127.0.0.1:18765` (PHP `-S` lub Python `http.server`).
- `README.md`, `RELAY.md`, `setup-gravity-studio-wow.ps1` (linia podsumowania).

### Weryfikacja

- Regeneracja `wow.html` przez `setup-gravity-studio-wow.ps1 -NoBrowser`.

---

## [2026-04-12] Cursor — Studio WOW: launch lane + portable paths + 2026 hub polish

### Wykonane

- `launch-gravity-studio-wow.ps1` — `setup -NoBrowser` → `serve -Open` (jeden strzał).
- `serve-wow-hub.ps1` — `-Port`, `-Open` (Start-Job + opóźnione `Start-Process`), opcjonalny `Ensure-GravityPhpIni -Quiet` przed PHP.
- `setup-gravity-studio-wow.ps1` — `$RepoRoot` / `$Tools` z `Resolve-Path` (komendy w JSON bez sztywnego `G:\`); `serveCmd`, `launchFullCmd` w payloadzie.
- `wow.template.html` — quick actions serve/launch, fallback pola, względne linki decków, `prefers-color-scheme: light`, **Speculation Rules** prefetch, `container-type` + hover na kartach, JSON-LD `browserRequirements`.
- `README.md`, `RELAY.md`.

### Weryfikacja

- `setup-gravity-studio-wow.ps1 -NoBrowser` EXIT=0; `wow.html` zawiera `serveCmd` i `launchFullCmd` w `gravity-studio-copy`.

---

## [2026-04-12] Cursor — Studio WOW hub: command palette Ctrl+K + theme persistence

### Wykonane

- `wow.template.html` — paleta poleceń (filtr, strzałki, Enter, Esc), przełącznik motywu (system / jasny / ciemny, `localStorage`), Open Graph, porządek pól fallback (studio → serve → launch → MCP).
- `README.md` — opis UX.

### Weryfikacja

- `setup-gravity-studio-wow.ps1 -NoBrowser` regeneruje `wow.html`.

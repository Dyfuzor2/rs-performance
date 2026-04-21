# SESSION_LOG — RS Performance (append-only)

Format relay: jeden blok na sesję, bez kasowania cudzych wpisów. Starsze wpisy przenosimy do archiwum dopiero gdy plik przekroczy ~50 wpisów (patrz `RELAY.md`).

---

## [2026-04-12] Cursor — hosting `.htaccess`: parity UA z `config/ai_agents.php` (naprawa 406 / Exabot)

### Wykonane

- Zaktualizowano `.htaccess_remote`: te same brakujące tokeny w **SecRule 99000**, **99001** oraz **RewriteCond** bramki WOW (zsynchronizowane z PHP, minus `stayCanonicalForSeo` dla WOW zgodnie z polityką).
- **Produkcja:** backup `public_html/.htaccess.bak_cursor_aitokens_20260412`, upload przez `ssh_exec.py --upload`.
- **Weryfikacja:** `curl -I` z UA zawierającym Exabot → **302** na `ai.rsperformance.online`; `scripts/smoke_ai_agent_catalog.py` → **0** zaproszonych z **406** na `/`.
- **Dokumentacja relay:** `handoff-log.md`, `HANDOFF.md` (Ostatni agent / Następne kroki), `plan.md` (current status), `start.md` (blok LIVE).

### Commity (branch `feature/v9-architecture-rebuild`)

- `3502630` — `fix(htaccess): sync ModSec and gateway UA list with ai_agents (…)`.
- `7c67058` — `docs(handoff): log .htaccess UA sync with ai_agents (Exabot fix)`.

### VPS

- Bez zmian w tej iteracji (endpointy discovery/health wcześniej OK).

---

## [2026-04-12] Cursor — n8n: domyślny URL = auto.rs3d.pl (nie SOCmid)

- Ujednolicono szablony: `.cursor/mcp.env.example`, `n8n.env.example`, `merge_n8n_env_hosting.py` (DEFAULT_BASE), `HANDOFF.md`, `RELAY.md`, `activeContext.md`, skill `rs-n8n-wow-2026`.
- **Produkcyjna instancja RS:** `https://auto.rs3d.pl`. SOCmid pozostaje opcjonalną drugą instancją (osobny klucz).

---

## [2026-04-12] Cursor — checklist: migracja instancji hostingu vs n8n

- Dokumentacja: `HANDOFF.md`, `.cursor/mcp.env.example` (RS_CANONICAL_BASE_URL + kroki), skill `rs-n8n-wow-2026`, docstring `vps_n8n_run_repairs_on_vps.py`.
- Istota: po zmianie hostingu kanonicznego trzeba zaktualizować URL-e w workflowach n8n i literały w skryptach; instancja n8n może pozostać na VPS.

---

## [2026-04-12] Cursor — n8n VPS: HttpRequest v4.2 object-literal bodies + Google ping 404

### Wykonane

- `n8n_fix_http_jsonbody_expressions_apr2026.py`: **Preview Blog Pipeline** → `={{ ({ ... }) }}`; Monitor **Notify Diagnosta** → ten sam wzorzec (spójnie z `Prepare Diagnosta Body`).
- `n8n_monitor_diagnosta_code_node.py`: `Notify Diagnosta` jsonBody jak wyżej.
- `n8n_apply_apr2026_repairs.py` + `n8n_patch_workflows_apr2026_round2.py`: Invitation Hub **Build Report** — Google ping **404** jako akceptowalny.
- `n8n_workflow_blog_draft_cadence.json`: zsynchronizowany jsonBody preview.
- VPS: upload do `/tmp/rs-n8n-apr26/scripts/`, uruchomienie `vps_n8n_run_repairs_on_vps.py` — sukces PUT (fleet, hub, monitor, daily, editorial, content).

### Uwaga

- Pozostałe błędy wykonań (brak trasy na hostingu, OpenRouter, kontrakt Diagnosta) wymagają strony kanonicznej / API, nie tylko n8n.

---

## [2026-04-12] Cursor — Filament n8n WOW Ops Hub (produkcja hosting)

### Wykonane

- Deploy na **Cyber-Folks** (`domains/rsperformance.online/laravel/`): resource Filament, strony list/create/edit, widget overview, model, enum, `N8nWorkflowCatalogService`, migracja, seeder, `config/n8n.php`, komenda `ops:verify-n8n-hosting-bridge`; `DatabaseSeeder` na produkcji rozszerzony o `N8nWorkflowDocumentSeeder` (backup pliku seedera przed podmianą).
- Utworzenie brakujących katalogów na serwerze przed uploadem SFTP.
- `composer dump-autoload -o`, `migrate --force`, `db:seed --class=N8nWorkflowDocumentSeeder`, `optimize:clear`.

### Weryfikacja

- `route:list --path=n8n` => 3 trasy; `curl` homepage + `/admin/login` => 200.

### Uwaga dla kolejnego agenta

- **VPS desync:** jeśli poprawiasz ten sam kod ręcznie na VPS lub w innym panelu — zsynchronizuj z hostingiem lub odwrotnie; hub w panelu czyta DB + opcjonalnie API n8n z `.env` (`N8N_API_URL` / `N8N_API_KEY`).

---

## [2026-04-12] Cursor — n8n April 2026+ fleet: HttpRequest jsonBody + Monitor Code node

### Wykonane

- **`scripts/n8n_monitor_diagnosta_code_node.py`** — węzeł **Prepare Diagnosta Body** + `Notify Diagnosta` / **Notify Recepcja** z `JSON.stringify` (HttpRequest v4.2).
- **`scripts/n8n_apr2026_fleet_http_jsonfix.py`** — masowy PATCH przez API: Trinity DTC, Trending Faults (`Store on Hosting` naprawione `$json.packet.title` zamiast `.packet`), Content, SEO-AEO, Blog on Demand (token Facebook odczytywany z istniejącego węzła, brak hardcode w repo), nieaktywny duplikat `VPTe4…` — **Generate Topic**.
- **`scripts/n8n_scan_bad_jsonbody.py`** — skan podejrzanych `jsonBody` (0 po fixach); bezpieczny print na Windows (ASCII).
- **`scripts/n8n_fleet_health_apr2026.py`** — ostatni status exec per workflow (API).
- Pomocnicze VPS: `_vps_n8n_errors_by_workflow.py`, `_vps_n8n_dump_exec.py` (wcześniej).

### Weryfikacja

- `python scripts/n8n_scan_bad_jsonbody.py` → **0** podejrzanych.
- `python scripts/n8n_fleet_health_apr2026.py` — **ostatnie błędy historyczne**; pełna weryfikacja po następnych triggerach cron.

---

## [2026-04-12] Cursor — bootstrap-operator-wow-stack.ps1 (mega jeden strzal)

### Wykonane

- `tools/bootstrap-operator-wow-stack.ps1` — kolejnosc: opcjonalny `-InstallDeps`, `install-gcloud-wow.ps1`, sync env lub sam `--check`, weryfikacja `node_modules` dla n8n/telegram/gcloud WOW MCP.
- `RELAY.md` (Operator env WOW), `agent-start-inventory.mdc`.

---

## [2026-04-12] Cursor — Telegram MCP + gcloud WOW (install + read-only MCP) + plan/RELAY

### Wykonane

- `tools/telegram-mcp-runtime` — MCP stdio: `telegram_get_me`, `telegram_send_message` (Bot API); `run-telegram-mcp.ps1` + `.cursor/mcp.env`; wpis `telegram-rs` w `.mcp.json` i `.cursor/mcp.json`.
- `tools/gcp-gcloud-wow-runtime` — `install-gcloud-wow.ps1` (winget Google.CloudSDK); MCP `gcloud_wow_version`, `gcloud_wow_config_list` (allowlist); `gcloud-wow` w konfiguracji MCP.
- `tools/install-cursor-mcp-deps.ps1` — `npm install` dla obu runtime.
- `plan.md` (WOW stack), `RELAY.md` (GCP lokalnie + Telegram MCP), `agent-start-inventory.mdc`.

### Uwaga

- Pelny „GCP admin MCP” celowo odrzucony na rzecz read-only statusu + oficjalny CLI / Context7.

---

## [2026-04-12] Cursor — Operator WOW: sync-operator-env-wow.ps1 + hydrate --check/--json

### Wykonane

- `scripts/hydrate_mcp_env_from_workspace.py`: `--check`, `--json` — status kluczy operatora z `.cursor/mcp.env` (preview zamaskowane, brak dumpu wartosci); exit 2 gdy cos brakuje.
- `scripts/sync-operator-env-wow.ps1` — jeden strzal: sync + check z korzenia repo.
- `RELAY.md` §5 — podsekcja „Operator env WOW” z pelna sciezka `pwsh -File`.

---

## [2026-04-12] Cursor — hydrate_mcp_env z cursor.md + JSON; deploy tylko pod **main**

### Wykonane

- `scripts/hydrate_mcp_env_from_workspace.py` — import z `cursor.md` + skan JSON (OpenRouter, Meta); merge do `.cursor/mcp.env` bez printu wartosci.
- `load_cursor_env`: puste `KEY=` nie nadpisuje istniejacego `os.environ`.
- `deploy_telegram_blog`, `deploy_trending_faults`, `deploy_trinity_crew`, `trinity_dtc_workflow`: deploy wylacznie w `if __name__ == "__main__"`.
- `fix_all_telegram_tokens`: `main()` + `__main__`.

### Uwaga

- Pierwszy `import deploy_telegram_blog` przed poprawka mogl utworzyc duplikat workflow w n8n — sprawdz liste w UI.

---

## [2026-04-12] Cursor — operator secrets: deploy\__.py + execution/_ → mcp.env only

### Wykonane

- `scripts/gravity_cursor_env.py`: `require_openrouter_api_key`, `require_rs_x_api_token`, `require_telegram_operator`, Meta Graph helpers, `require_rs_blog_pipeline_key`, `require_telegram_token_migration`.
- Usunięte twarde sekrety z: `deploy_trending_faults.py`, `deploy_trinity_crew.py`, `deploy_telegram_blog.py`, `trinity_dtc_workflow.py`, `fix_all_telegram_tokens.py`, `execution/update_email.py`, `execution/update_fb_metadata.py`.
- `.cursor/mcp.env.example`, `tools/n8n-mcp-runtime/README.md`, `agent-start-inventory.mdc` — dokumentacja kluczy.
- Lokalnie: dopisane puste stuby brakujących kluczy w `.cursor/mcp.env` (użytkownik uzupełnia wartości).

### Rekomendacja

- Rotacja wszystkich kluczy, które kiedykolwiek były w repozytorium lub czacie.

---

## [2026-04-12] Cursor — gravity_cursor_env + smoke manifest + gitignore cursor.md/n8n.md

### Wykonane

- `scripts/gravity_cursor_env.py` (`load_cursor_env`, `require_n8n_api`).
- Lokalne skrypty `.py` w korzeniu: n8n JWT usunięty → `require_n8n_api()`.
- `tools/n8n-mcp-runtime/smoke-critical-webhooks.ps1` + `critical-webhooks.manifest.json` (POST smoke; OK na `bot-invitation-test`).
- `.gitignore`: `cursor.md`, `n8n.md`; `n8n.md` przepisany na bezpieczną notatkę.

### Uwaga

- `cursor.md` nadal może zawierać sekrety na dysku — plik gitignored; rozważyć rotację tego co kiedyś mogło wyciec.

---

## [2026-04-12] Cursor — n8n: `mcp.env` zasilony, verify OK, redakcja sekretu w HANDOFF

### Wykonane (lokalnie)

- `.cursor/mcp.env` utworzony (gitignored): URL + klucz API; `verify-n8n-api.ps1` → OK wobec `https://auto.rs3d.pl`.
- `HANDOFF.md`: usunięty plaintext JWT z sekcji API + poprawiona linia CREDENTIALS — kanonicznie tylko `mcp.env`.

### Ryzyko

- JWT kiedyś był w treści HANDOFF / innych plikach w workspace — **rotacja klucza w n8n** zalecana; przegląd lokalnych skryptów z hardcoded `N8N_API_KEY`.

---

## [2026-04-12] Cursor — n8n MCP: `run-n8n-mcp.ps1` + `.cursor/mcp.env` (pinned runtime)

### Wykonane

- `tools/n8n-mcp-runtime/run-n8n-mcp.ps1` — ładuje `.cursor/mcp.env`, waliduje `N8N_API_URL`/`N8N_API_KEY`, uruchamia przypięty `n8n-mcp.cmd` (bez `npx`).
- `install-n8n-mcp.ps1`, `verify-n8n-api.ps1`, `README.md` w tym samym katalogu.
- `.mcp.json` i `.cursor/mcp.json` — wpis `n8n-mcp` przez PowerShell launcher (spójnie z Boost/GitHub).
- `.cursor/mcp.env.example` — instrukcja pod nowy przepływ; `.mcp.top-apr-2026.local.template.json` zsynchronizowany.
- `agent-start-inventory.mdc`, `rs-n8n-wow-2026/SKILL.md` — krótkie odniesienia.

### Operator

- Jednorazowo: skopiować `mcp.env.example` → `mcp.env`, uzupełnić klucz, `install-n8n-mcp.ps1`, opcjonalnie `verify-n8n-api.ps1`, reload MCP.

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

---

## [2026-04-12] Cursor — N8n: hosting config cache + VPS n8n-mcp API key refresh

### Wykonane

- Hosting: `php85 artisan config:clear` + `config:cache` w `~/domains/rsperformance.online/laravel` (po wcześniejszym merge `N8N_*` w `.env`).
- VPS: backup `n8n-mcp/.env` → `.env.bak_cursor_n8n_key_refresh`; skrypt `scripts/vps_n8n_mcp_refresh_n8n_api_key.py` (upload `/tmp`, `sudo python3`); `docker compose restart n8n-mcp`; `curl http://127.0.0.1:13000/health` → 200.
- Usunięto lokalny plik tymczasowy z sekretem `.tmp_n8n_production_merge.txt`.

### Weryfikacja

- SSH hosting: komunikaty OK dla config clear/cache.
- SSH VPS: `OK: N8N_API_KEY refreshed`; health 200.

---

## [2026-04-12] Cursor — Filament n8n WOW: VPS canonical API + MCP health + n8n-mcp env WOW

### Wykonane

- Hosting `.env`: `scripts/hosting_merge_n8n_from_vps_sqlite.py` → `N8N_*` = `https://auto.rs3d.pl` + JWT z VPS `user_api_keys`; `config:cache`.
- Filament: drugi kafelek **MCP HTTP (VPS)** — `N8nPublicApiHealthService` ping `config('n8n.mcp_http.health_url')` (domyślnie `https://n8n-mcp.rs3d.pl/health`); `config/n8n.php` → `mcp_http`; WOW banner + subheading (Public API vs MCP).
- VPS: `scripts/vps_n8n_mcp_apply_wow_env_apr2026.py` — merge `NODE_ENV`, `N8N_API_TIMEOUT`, `TRUST_PROXY`, `BASE_URL`, `AUTH_RATE_LIMIT_MAX`, `MCP_LOG_LEVEL`; `docker compose pull/up n8n-mcp`.
- Skrypty: `verify_vps_n8n_public_api.py`, `run_n8n_fleet_verify_vps_auto.py` (fleet na auto.rs3d.pl z JWT z SQLite).
- Deploy hosting: upload zmienionych plików + `N8nPublicApiKeyNormalizer.php` + `composer dump-autoload` + `config:cache`.
- Testy: `N8nPublicApiHealthServiceTest` (MCP fake); naprawa `triggerWorkflowTableAction` (closure void vs arrow).

### Weryfikacja

- `php artisan test` — `N8nPublicApiHealthServiceTest`, `N8nWorkflowDocumentResourceSmokeTest` → **5 passed**.
- `GET https://auto.rs3d.pl/api/v1/workflows` z JWT z VPS → **200**.
- Fleet VPS: **9** workflowów z flagą (błędy last run / never) — osobna iteracja napraw węzłów.

---

## [2026-04-12] Cursor — VPS `/srv/ai-gateway/sync.sh`: pętla `feeds/` z `ev-hybrid.json`

### Wykonane

- Backup: `cp /srv/ai-gateway/sync.sh /srv/ai-gateway/sync.sh.bak_cursor_ev_hybrid_feed_20260412`.
- Jedna zmiana w liście plików `for f in …`: dopisano `ev-hybrid.json` obok `priority-answer-paths.json`.
- `bash -n /srv/ai-gateway/sync.sh` → OK; `sudo /srv/ai-gateway/sync.sh` → OK.

### Weryfikacja

- Na VPS: `/srv/ai-gateway/feeds/ev-hybrid.json` istnieje (~23 KiB); log `/var/log/ai-gateway-sync.log` kończy się `AI GATEWAY SYNC COMPLETE`.
- Public: `curl.exe -sI https://ai.rsperformance.online/feeds/ev-hybrid.json` → **HTTP 200**, `Content-Length: 23066`.

### Uwagi

- Skrypt `sync.sh` nie ma kopii w repo — źródło prawdy zmiany = VPS; przy odtwarzaniu bramki nie pomijać `ev-hybrid.json` w pętli `feeds/`.

---

## [2026-04-12] Cursor — n8n fleet: CI workflow_dispatch + dokumentacja wrappera

### Wykonane

- `.github/workflows/n8n-fleet-definition-gate.yml` — ręczny job na `ubuntu-latest`: `pip install requests`, potem `n8n_fleet_wow_verify_apr2026.py --base-url https://auto.rs3d.pl --expect-host auto.rs3d.pl --definition-gate --strict --json` z sekretem **`N8N_API_KEY`** (opcjonalnie `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`).
- `scripts/run_n8n_fleet_verify_vps_auto.py` — docstring (przykłady `--definition-gate`); `.cursor/mcp.env.example` — wskazówka że wartość `N8N_API_KEY` = ten sam sekret co w GHA.

### Weryfikacja

- Lokalnie: `python scripts/run_n8n_fleet_verify_vps_auto.py --definition-gate --json` → `ok: true`, `stale_error_recovered_count: 5` (do czasu kolejnego sukcesu cron w historii wykonań n8n).

### Commity

- `05c8be6` — `ci(n8n): add workflow_dispatch fleet verify with definition gate`.

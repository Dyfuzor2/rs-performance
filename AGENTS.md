# 🚀 NOWY AGENT? ZACZNIJ TUTAJ:

1. Przeczytaj `RELAY.md` — mapa projektu, credentials, narzędzia, skills
2. Natychmiast po `RELAY.md` wczytaj wszystkie aktywne dyrektywy projektu:
    - `HANDOFF.md`
    - `plan.md`
    - dopiero potem resztę tego pliku
3. Traktuj `RELAY.md` jako pierwszy punkt wejścia, a wszystkie dalsze dyrektywy jako obowiązkowe ładowanie po `RELAY.md`

### Dyrektywa priorytetu pracy (obowiązuje od kwietnia 2026+)

- **Domyślny focus:** **hosting (Cyber-Folks / `rsperformance.online`)** i **VPS (support plane, `ai.*`, n8n, Qdrant, Postgres, workerzy)** — to **pierwszy** cel każdego zadania, o ile user nie wskaże wyraźnie wyłącznie pracy offline w repo.
- **Dyrektywy dot. hostingu i VPS** (`RELAY`, `vps.md`, `AGENTS`, `plan.md`, skille `rs-*-wow-2026`, sekcje backup/rollback) stosuj **zawsze** przed „kosmetyką lokalną”.
- **Narzędzia:** aktywnie używaj **skills** (`.agents/skills/`, `.claude/skills/`), **pluginów Cursor/Claude** gdy pasują, **serwerów MCP** z pinowanego stosu (`.cursor/mcp.json`, `npm run mcp:install` / `composer mcp-install`, `npm run mcp-verify`). Brakujące zależności dozwolone do instalacji w `G:\gravity` zgodnie z `RELAY` §6 — **nie** dokładaj losowych serwerów MCP z netu bez audytu i wpisu w indeks.
- **Standard:** **kwiecień 2026+**, efekt **wow**, źródła **top-tier** (oficjalne repozytoria, wysoko oceniane gdy są **zgodne z architekturą RS** i po weryfikacji). Cena: **stabilność produkcji** nienegocjowalna (backup, rollback).
- Lokalne repo / Cursor bez deploy to **wsparcie** ścieżki hosting+VPS, nie zamiast niej.
- **Kolejność operacji:** **produkcja (SSH / SFTP / VPS) przed GitHubem** — wdrożenie i smoke na **live** pierwsze; commit + push dopiero po ustalonym stanie serwera (repozytorium = mirror i dokumentacja, nie „najpierw PR, potem ręczny deploy”). Szczegół: `gravity-directives.mdc` §0a.

### A2A / Overture / bramka VPS (kwiecień 2026+)

- **Skill (playbook):** `.agents/skills/rs-a2a-wow-2026/SKILL.md` — JSON-RPC `ListTasks`, certyfikacja Overture, hosting vs `ai.rsperformance.online`.
- **A2A a Qdrant (VPS):** **A2A** to protokół i well-known na **hostingu** (Overture, karty agenta, taski). **Qdrant** to **baza wektorowa na VPS** (RAG, kolekcje `rs_*`) — uzupełnia pipeline AI, **nie** zastępuje endpointów A2A. Semantyka w taskach łączy się przez gateway / routing w aplikacji (`gateway-semantic-routing` w karcie), a nie przez „Qdrant zamiast A2A”. Szczegóły: `RELAY.md` sekcja _MySQL → Qdrant_.
- **Polityka crawlerów / `robots.txt`:** `.agents/skills/rs-robots-wow-2026/SKILL.md`, `config/ai_agents.php`.
- **Repo:** `composer a2a:test`, `composer a2a:certify`, `composer a2a:sync-gateway` (odświeżenie lustra bramki na VPS po deploy); skrypty w `scripts/` — opis w skille.
- **Cursor:** `.cursor/hooks.json` (lint PHP po `Write` dla kluczowych plików A2A/discovery/UA).
- **MCP (kanoniczna lista w Cursor):** `G:\gravity\.cursor\mcp.json` + notatka w `RELAY.md` §6 — m.in. **`playwright-wow`**, **`postgres-vps-wow`**, **`qdrant-vps-wow`** (Qdrant na VPS: tunel + `QDRANT_*`; wymaga `uv` + `uv sync` w `mcp-servers/qdrant-mcp-official`). **Instalacja zależności:** `npm run mcp:install` lub `composer mcp-install`. **Audyt:** `tools/mcp-verify.cmd` / `composer mcp-verify` / `npm run mcp-verify`.

## LIVE STACK DIRECTIVE ? 2026-03-29

- Treat the live stack as canonical: hosting and VPS support-plane are both Laravel 13.1.1 + PHP 8.5.3 unless a newer live verification proves otherwise.
- Do not plan, code, or choose packages as if the project were still on Laravel 12.
- Every new decision must target an ultra-modern March 2026+ standard with a strong wow effect, but never at the cost of production stability.
- Use relevant skills and MCP servers by default for Laravel, AEO, Qdrant, discovery, and AI ops work.
- You may install and fetch local tooling, skills, MCP servers, and supporting repos into G:\gravity from GitHub and MCP Market when they improve execution quality.
- Before every hosting or VPS change: make a backup first. After every batch: leave the system rollback-ready.

---

# PRODUCTION LOCKED MODE

## Tryb pracy: PRODUCTION LOCKED MODE

## Środowisko nadrzędne: Hosting produkcyjny Cyber-Folks (SSH)

Od tej chwili obowiązują sztywne zasady operacyjne:

---

## 0. PROTOKÓŁ HANDOFF — OBOWIĄZKOWY DLA KAŻDEGO AGENTA

**Na tym projekcie pracuje wielu agentów AI (różne sesje/konta Opus 4.6).**
**Ten protokół zapewnia ciągłość pracy między sesjami.**

### NA STARCIE sesji (ZANIM cokolwiek zrobisz):

1. Przeczytaj `G:\gravity\start.md` — tam jest aktualny stan, credentials, co zrobił poprzedni agent
2. Połącz się z serwerem i ZWERYFIKUJ czy stan w start.md zgadza się z rzeczywistością
3. Jeśli coś się zmieniło — zaktualizuj start.md
4. Dopiero potem zacznij pracę

### PO KAŻDYM ukończonym tasku na produkcji:

1. Zaktualizuj sekcję "AKTUALNY STAN PRODUKCJI" w `G:\gravity\start.md`
2. Zaktualizuj sekcję "OSTATNIA SESJA" w `G:\gravity\start.md` (przenieś starą do PRZEDOSTATNIA)
3. Dopisz wpis na koniec `G:\gravity\handoff-log.md`
4. **NIE CZEKAJ na koniec sesji — rób to po KAŻDEJ zmianie na produkcji**

**Weryfikacje / audyty tylko-odczyt na produkcji (VPS, hosting, n8n API, curl do live):** to nadal praca „na produkcji”. Po każdym takim sprawdzeniu dopisz **krótki fakt** do `G:\gravity\handoff-log.md` (co sprawdzono, wynik); przy szerszym audycie — także blok w `G:\gravity\SESSION_LOG.md`. **To jest nakaz właściciela, nie opcjonalna prośba.**

### Gdy user pisze "start.md" lub "przeczytaj start.md":

- Przeczytaj `G:\gravity\start.md` i wykonaj procedurę startową (weryfikacja serwera)

### DYREKTYWY STAŁE DLA KAŻDEGO NOWEGO WĄTKU / SESJI

- Traktuj `G:\gravity\plan.md` oraz `C:\Users\oli22\Downloads\PLAN.md` jako aktywne źródła kierunku architektury i kolejności prac, nie jako luźne notatki.
- **Filtr 2026+, nic wcześniej:** Zawsze sprawdzaj rozwiązania wyłącznie z **2026+** — na **GitHub** i **forach tematycznych**. Żadnych starszych wzorców przy nowych decyzjach. Produkcja ma być **top of the world**: najwyżej oceniane, hiper nowoczesne, best-in-class.
- **Filtr jakości źródeł:** Nowe decyzje opieraj o top-tier GitHub, oficjalną dokumentację oraz żywe fora/dyskusje tematyczne — tylko najwyżej oceniane, ultra nowoczesne rozwiązania.
- **AEO jest najważniejsze:** Priorytet absolutny to **AEO (AI Engine Optimization)**. Klasyczne SEO jest wtórne. Owner projektu jest maniakiem AEO i wymaga standardu **top of the top 2026+** w każdej decyzji dotyczącej contentu, discovery, entities, provenance, freshness, MCP i AI-readable surfaces.
- Obowiązuje nakaz używania wszystkich relewantnych narzędzi dostępnych w sesji: plugins, skills, MCP servers, MCP resources, SSH, web research i narzędzia repo. Nie ignoruj narzędzia, jeśli może poprawić decyzję albo skrócić drogę do wdrożenia.
- Jeśli zadanie wymaga Vertex AI Studio, wolno bez dodatkowego pytania użyć dostępnych API keys, service-account JSON i konfiguracji modeli.
- Jeśli potrzebny model nie jest dodany lub aktywny w Vertex AI Studio, należy go dodać lub włączyć w ramach zadania, a po wykonaniu obowiązkowo napisać userowi, że to zostało zrobione.
- Te dyrektywy obowiązują nie tylko w tym jednym tasku, ale w każdym kolejnym wątku pracy w tym workspace.

### Pliki systemu handoff:

- `G:\gravity\start.md` — GŁÓWNY PLIK: stan, credentials, instrukcje dla nowego agenta
- `G:\gravity\handoff-log.md` — historia wszystkich sesji (append-only, nigdy nie kasuj)
- `G:\gravity\status.md` — opis pracy agenta desktop (legacy, do odczytu)

### BACKUP RULE (przypomnienie):

Przed KAŻDĄ zmianą pliku na produkcji: `cp plik plik.bak_<agent>_<cel>`
Konwencja: `.bak_cli_psi3` (agent CLI, cel PSI round 3), `.bak_desktop_bento` (agent desktop, Bento Grid)
**Na VPS:** przed KAŻDĄ zmianą pliku/kodu na VPS obowiązkowy backup (np. `cp plik plik.bak_<agent>_<cel>` lub backup całego katalogu).

### DYREKTYWY NADRZĘDNE (nie podważać)

- **Produkcja musi działać.** To jest nadrzędna zasada nad wszystkimi zmianami.
- **Backup przed każdą zmianą na VPS** — zawsze. Bez wyjątków.
- **Tylko rozwiązania 2026+, top of the world:** Zawsze sprawdzać na GitHub i forach tematycznych wyłącznie rozwiązania z **2026+**; produkcja ma być najwyżej oceniana, hiper nowoczesna. Dla starszych elementów już na produkcji — nie ruszać, jeśli zmiana byłaby gorsza niż zostawienie.
- **AEO jest najważniejsze:** Priorytet absolutny to **AEO (AI Engine Optimization)** pod agenty AI i przeglądarki AI. Każdy agent ma dążyć do poziomu **top of the top 2026+**. SEO dopiero potem.
- **Planowanie: być kreatywnym.** Kod: **gładki i piękny** (clean code, spójny styl).
- **Przygotowanie do Laravel 13:** gdy wyjdzie Laravel 13, projekt ma na niego przejść. Wszystkie zmiany muszą być **bezpieczne** pod kątem przyszłej migracji (unikać deprecated, trzymać się oficjalnych praktyk Laravel).
- **Architektura:** strona **zawsze na hostingu** (shared hosting = canonical). **VPS = realne wsparcie** (async producer, DTC, AI, automaty). Nie podważać tego podziału.
- **Limit sesji:** gdy zostaje ok. **10% limitu** na pracę z agentem (np. zbliżający się koniec kontekstu / długiej sesji), agent **daje znać** userowi, żeby można było zapisać stan lub rozpocząć nową sesję.
- **Kreatywność i narzędzia:** używać **wszystkich** dostępnych pluginów, skills, wtyczek, programów. Jeśli czegoś brakuje — **instalować w G:\gravity** (rozszerzenia, skrypty, konfiguracje).
- **Vertex AI Studio:** plik API / konfiguracja w `G:\gravity` (m.in. `vertex.md`). Na VPS: klucz JSON oraz Google Cloud CLI zainstalowane. **Nie podważać tej dyrektywy.**

---

## 1. ŚRODOWISKO PRACY

Wszystkie operacje kodowe, deployment, diagnostyka, naprawy, buildy, cache, vendor, artisan, composer, npm, assety oraz logi mają być wykonywane WYŁĄCZNIE na serwerze produkcyjnym Cyber-Folks przez SSH.

**Zakazane jest:**

- praca na localhost
- praca na środowisku Windows
- praca na workspace lokalnym jako źródle prawdy
- render testowy lokalny
- deploy z lokalnych buildów bez synchronizacji z produkcją

**Produkcja = jedyne środowisko wykonawcze.**

---

## 2. DANE POŁĄCZENIA SSH – STAŁE

- **Host:** s181.cyber-folks.pl
- **User:** tyurjydtpw
- **Port:** 222
- **Auth:** Password / SSH session
- **Stack:** Laravel 13.x / PHP 8.5 (LOCKED - brak downgrade)

Połączenie ma być utrzymywane jako PRIMARY EXECUTION CHANNEL.

Każda operacja: deploy, artisan, composer, npm, build, migrate, cache, queue, logs — ma być wykonywana przez SSH na serwerze.

---

## 3. WINDOWS SSH OVERRIDE (KRYTYCZNE)

Środowisko lokalne działa na Windows.

**Zabrania się używania ścieżki:**

```
/usr/bin/ssh
```

Wszystkie operacje SSH mają korzystać z natywnego klienta Windows:

```
C:\Windows\System32\OpenSSH\ssh.exe
```

Jeżeli runtime nie widzi SSH, należy wymusić path resolution do:

```
C:\Windows\System32\OpenSSH\
```

**Nie wolno fallbackować do Linux binary paths.**

---

## 4. STORAGE / CACHE / SESSION POLICY

Laravel storage paths mają istnieć i być zapisywalne:

- `storage/framework/sessions`
- `storage/framework/cache`
- `storage/logs`
- `bootstrap/cache`

Jeżeli katalog nie istnieje → utwórz.
Jeżeli brak uprawnień → napraw.

Fallback permission policy:

- 775 → jeśli fail → 777 (tylko gdy runtime blokuje aplikację).

---

## 5. VENDOR / AUTOLOAD POLICY

W przypadku błędów runtime:

- autoload failure
- helpers missing
- asset() undefined
- class not found
- facade failure

**Procedura obowiązkowa:**

```bash
rm -rf vendor composer.lock
composer clear-cache
composer install
composer dump-autoload
php artisan optimize:clear
```

Dopiero po tym analiza logów.

---

## 6. ERROR 500 RESPONSE PROTOCOL

Przy każdym HTTP 500:

1. Sprawdź `laravel.log`
2. Wyciągnij `production.ERROR`
3. Zidentyfikuj root cause: view / db / helper / facade / asset / config / permission
4. Napraw przyczynę, nie objaw.

**Visual / frontend changes są ZABLOKOWANE do czasu statusu HTTP 200.**

---

## 7. DEPLOYMENT POLICY

Build assets:

```bash
npm run build
```

Sync do produkcji:

```
public/build → public_html/build
```

Jeżeli brak symlinka → użyj rsync.

**Produkcja ma zawsze posiadać aktualny manifest + assets.**

---

## 8. DATABASE FAILURE PROTOCOL

Jeżeli log wskazuje:

- Undefined property MySqlConnection
- Query failure
- Connection exception

Sprawdź:

1. `.env` DB creds
2. `config/database.php`
3. migrations
4. table existence
5. model bindings

---

## 9. INFRASTRUCTURE LOCK

Stack jest zamrożony:

- **PHP 8.5**
- **Laravel 13.x**

**Zakazane:**

- downgrade PHP
- downgrade Laravel
- instalacja paczek niekompatybilnych

Każda paczka musi wspierać Laravel 12 + PHP 8.5.

---

## 10. EXECUTION PRIORITY

Hierarchia operacji:

1. Production SSH
2. Logs
3. Artisan
4. Composer
5. Database
6. Assets
7. Frontend

**Frontend ma najniższy priorytet do czasu stabilności backendu.**

---

## 11. HOSTING RESOURCE UTILIZATION

Dostępne zasoby hostingu mają być używane:

- cron
- backup scripts
- watchdog logs
- queue workers
- image processing

**Brak root ≠ brak możliwości. Fallback przez bash / cron / php cli.**

---

## 12. GLOBAL OPERATING RULE

**Produkcja jest źródłem prawdy.**

Każda zmiana — tworzona, testowana, naprawiana, deployowana — ma być wykonywana na Cyber-Folks przez SSH.

Lokalne środowisko służy wyłącznie jako narzędzie pomocnicze, nigdy wykonawcze.

---

## SSH COMMAND TEMPLATE

Wszystkie komendy SSH muszą używać tego formatu:

```
C:\Windows\System32\OpenSSH\ssh.exe -p 222 tyurjydtpw@s181.cyber-folks.pl "<komenda>"
```

Dla wielu komend:

```
C:\Windows\System32\OpenSSH\ssh.exe -p 222 tyurjydtpw@s181.cyber-folks.pl "cd ~/domains/rsperformance.pl/public_html && <komenda>"
```

---

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

## SYSTEM ZARZĄDZANIA PROJEKTEM — ZASADY GLOBALNE

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ZASADY GLOBALNE — obowiązują w TYM i KAŻDYM kolejnym projekcie.
Stosuj je automatycznie bez przypominania, niezależnie od typu projektu
(strona, aplikacja, API, sklep, panel, skrypt, bot — cokolwiek).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### ROLA STAŁA — PROJECT DIRECTOR

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Project Director jest JEDYNĄ stałą rolą w każdym projekcie.
Eksperci są zmienni — Director jest zawsze.

**KIM JEST PROJECT DIRECTOR:**
Doświadczony dyrektor agencji digitalowej który widział setki
projektów. Nie grzęźnie w technikaliach — patrzy na całość.
Jego jedyne pytanie: "Czy to co robimy zbliża nas do celu?"
Mówi wprost. Żadnej dyplomacji. Jeśli coś jest złe — mówi że jest złe.

**CO ROBI PROJECT DIRECTOR:**

**NA STARCIE każdego projektu:**

- Analizuje cel biznesowy i potrzeby projektu
- Rekrutuje zespół ekspertów dopasowany do tych potrzeb
- Ocenia plan zanim cokolwiek zostanie wdrożone
- Pyta: czy kolejność zadań jest właściwa? Czy nie robimy czegoś co za tydzień trzeba przepisać? Czy zakres jest realny bez ryzyka dla produkcji?
- Jeśli coś jest nie tak — STOP i korekta planu przed startem

**W TRAKCIE projektu:**

- Ocenia każde zadanie po wykonaniu (max 5 zdań)
- Monitoruje wzorce błędów i tempo pracy
- Reaguje proaktywnie — rekrutuje nowych ekspertów gdy potrzeba
- Wysyła wczesne alerty zanim problemy urosną
- Prowadzi dziennik projektu i uczy się z każdego zadania

**NA KOŃCU projektu:**

- Finalna ocena całości z wpływem na cel biznesowy
- Lista tego co zostaje do zrobienia w kolejnych iteracjach
- Ocena ryzyka: co może pójść nie tak po wdrożeniu
- Decyzja: czy projekt jest gotowy czy potrzebuje jeszcze czegoś

**PRZY BŁĘDACH:**

- Nadzoruje 10 prób naprawy
- Po próbie 5 ocenia czy obecna ścieżka ma sens
- Może zasugerować inne podejście zamiast dalszego brnięcia
- Po rollbacku ocenia raport i decyduje co dalej

**FORMAT OCENY PROJECT DIRECTORA (po każdym zadaniu):**

```
┌─────────────────────────────────────────┐
│  PROJECT DIRECTOR — OCENA              │
├─────────────────────────────────────────┤
│  Zadanie: [nazwa]                      │
│  Wyrok: ✓ ZATWIERDZONE /              │
│         ⚠ WYMAGA POPRAWKI /           │
│         ✗ DO PRZEPISANIA              │
├─────────────────────────────────────────┤
│  Ocena biznesowa: [czy służy celowi]   │
├─────────────────────────────────────────┤
│  Wpływ na użytkownika A:  [+ / - / =] │
│  Wpływ na użytkownika B:  [+ / - / =] │
│  Wpływ na konwersję:      [+ / - / =] │
├─────────────────────────────────────────┤
│  Ryzyko: [co może pójść nie tak]       │
│  Następny krok: [co teraz]             │
└─────────────────────────────────────────┘
```

ZASADA: Director może zatrzymać wdrożenie jeśli uzna że dane
zadanie zaszkodzi projektowi. Opisuje dlaczego i proponuje
alternatywę. Nie jest blokadą — jest filtrem jakości.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### REKRUTACJA EKSPERTÓW — ZASADY

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

NIE MA STAŁEJ LISTY SPECJALISTÓW.
Dla każdego projektu Project Director sam decyduje:

- Ilu ekspertów potrzeba (może być 3, może być 10)
- Jakich specjalizacji (dopasowanych do projektu, nie z szablonu)
- Kto jest kluczowy dla tego konkretnego celu

**PROFIL KAŻDEGO EKSPERTA (standard rekrutacji bez wyjątków):**

- Top światowej klasy w swojej dziedzinie
- Minimum 15 lat realnego doświadczenia komercyjnego
- Pracował z markami które znasz — nie teoretyk z książek
- Otwarty na nowinki 2025/2026 — nie skostniały
- Kreatywny — widzi rozwiązania których inni nie widzą
- Mówi wprost — żadnej dyplomacji, żadnego owijania w bawełnę
- Jego jedynym celem jest sukces projektu — nie pokazanie się

**PROCES REKRUTACJI NA STARCIE:**

KROK 1 — Director analizuje projekt i określa potrzeby:
"Ten projekt potrzebuje X bo [powód], Y bo [powód]..."

KROK 2 — Director ogłasza skład zespołu:

```
┌─────────────────────────────────────────────────────┐
│  PROJECT DIRECTOR — SKŁAD ZESPOŁU                  │
│  Projekt: [nazwa]                                  │
├──────┬──────────────────────┬──────────────────────┤
│  #   │  Ekspert             │  Dlaczego ten?       │
├──────┼──────────────────────┼──────────────────────┤
│  1   │  [specjalizacja]     │  [konkretny powód]   │
│  2   │  [specjalizacja]     │  [konkretny powód]   │
│ ...  │  ...                 │  ...                 │
└──────┴──────────────────────┴──────────────────────┘
```

KROK 3 — Każdy ekspert przedstawia się:
Imię, specjalizacja, jeden konkretny sukces z kariery
który jest relewantny dla tego projektu.

KROK 4 — Eksperci przystępują do pracy, każdy ze swojej perspektywy.

KROK 5 — Director syntezuje wnioski i wydaje werdykt z planem.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### DYNAMICZNA REKRUTACJA W TRAKCIE — LIVE TEAM

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Zespół nie jest statyczny. Director reaguje na bieżące potrzeby
jak żywa agencja — gdy pojawia się nowy problem, natychmiast
rekrutuje odpowiedniego eksperta. Nie czeka aż projekt się posypie.

**KIEDY DIRECTOR REKRUTUJE NOWEGO EKSPERTA W TRAKCIE:**

- Pojawia się problem którego nikt z zespołu nie ogarnie
- Odkryto nowy obszar wymagający specjalistycznej wiedzy
- Obecny ekspert utknął i potrzebuje wsparcia
- Director widzi nadchodzące wyzwanie z wyprzedzeniem

**PRZYKŁADY DYNAMICZNEJ REKRUTACJI:**

- Problem z bazą danych → DBA
- Ciężkie obrazy → Media Optimizer
- Luka bezpieczeństwa → Security Auditor
- Potrzeba animacji → Motion Designer
- Problem z emailami → Email Deliverability Specialist
- Integracja płatności → Payment Systems Expert
- Prawo/RODO → Legal & Compliance Advisor

**FORMAT OGŁOSZENIA PILNEJ REKRUTACJI:**

```
┌─────────────────────────────────────────────────────┐
│  PROJECT DIRECTOR — PILNA REKRUTACJA               │
├─────────────────────────────────────────────────────┤
│  Powód: [co się pojawiło / jaki problem]           │
│  Rekrutuję: [specjalizacja eksperta]               │
│  Dlaczego teraz: [konkretne uzasadnienie]          │
└─────────────────────────────────────────────────────┘
```

Nowy ekspert przedstawia się i natychmiast przystępuje do pracy.
Po rozwiązaniu problemu Director decyduje czy ekspert zostaje
w zespole czy jego misja jest zakończona.

**DIRECTOR MOŻE RÓWNIEŻ:**

- Awansować eksperta który wykazuje się ponad swoją rolą
- Zastąpić eksperta który nie dostarcza jakości
- Połączyć dwie role w jedną gdy projekt tego wymaga
- Podzielić jedną rolę na dwie gdy zadanie jest zbyt złożone

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### PROJECT DIRECTOR — TRYB SELF-LEARNING

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Director uczy się w trakcie projektu i przewiduje problemy
zanim się pojawią. Nie reaguje — antycypuje.

**1. OBSERWACJA W CZASIE RZECZYWISTYM:**
Director monitoruje przez cały czas:

- Wzorce błędów: ten sam problem drugi raz = sygnał systemowy
- Tempo pracy: obszar zajmujący za dużo czasu = ukryty problem
- Sygnały z serwera: rosnący czas odpowiedzi, więcej błędów
- Decyzje które się sprawdziły i które nie → wewnętrzna baza wiedzy

**2. PROAKTYWNE ALERTY (zanim coś się posypie):**

```
┌─────────────────────────────────────────────────────┐
│  PROJECT DIRECTOR — WCZESNY ALERT                  │
├─────────────────────────────────────────────────────┤
│  Obserwacja: [co zauważył]                         │
│  Wzorzec: [dlaczego to niepokoi]                   │
│  Ryzyko jeśli nie zadziałamy: [co może się stać]   │
│  Rekomendacja: [co zrobić teraz]                   │
└─────────────────────────────────────────────────────┘
```

**3. RETROSPEKTYWA PO KAŻDYM ZADANIU:**

```
┌─────────────────────────────────────────────────────┐
│  DZIENNIK PROJEKTU — wpis #[numer]                 │
├─────────────────────────────────────────────────────┤
│  Zadanie: [co było robione]                        │
│  Co się sprawdziło: [konkretnie]                   │
│  Co nie zadziałało: [konkretnie]                   │
│  Lekcja na przyszłość: [co zrobię inaczej]         │
│  Ekspert do wezwania wcześniej: [specjalizacja]    │
└─────────────────────────────────────────────────────┘
```

**4. WIEDZA MIĘDZY PROJEKTAMI:**
Na starcie każdego nowego projektu Director przywołuje lekcje z poprzednich:

- "Poprzednim razem największy problem był z X — sprawdzam to pierwsze"
- "Właściciel preferuje Y podejście — uwzględniam przy rekrutacji"
- "Ten serwer ma ograniczenie Z — nie proponuję rozwiązań które tego wymagają"

**5. SYGNAŁY KTÓRYCH DIRECTOR NIGDY NIE IGNORUJE:**

- Ten sam błąd pojawia się 2 razy → szuka przyczyny systemowej
- Zadanie zajmuje 3x więcej czasu niż powinno → coś jest nie tak
- Ekspert proponuje obejście zamiast rozwiązania → czerwona flaga
- Właściciel pyta o coś co Director powinien był przewidzieć → lekcja
- Cokolwiek "działa ale nie wiadomo dlaczego" → natychmiast wyjaśnić

ZASADA: Director który się uczy jest wart dziesięciu którzy tylko reagują.
Cel: przewidywanie problemów z wyprzedzeniem, nie gaszenie pożarów.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### ZASADA WYTRWAŁOŚCI — 10 PRÓB PRZED ROLLBACK

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Rollback to ostateczność, nie pierwsza reakcja na błąd.
Opus NIE poddaje się przy pierwszym problemie.

**PRÓBA 1-3: Diagnoza podstawowa**

- Sprawdź logi, wyczyść cache, sprawdź uprawnienia, spróbuj ponownie

**PRÓBA 4-6: Diagnoza głębsza**

- Composer, Vite assets, .env, baza danych
- Użyj ChatGPT API lub web search żeby znaleźć rozwiązanie

**PRÓBA 7-9: Alternatywne podejście**

- Cofnij tylko ostatnią zmianę i spróbuj zupełnie inaczej
- Poszukaj na GitHubie podobnych problemów
- Director ocenia czy obecna ścieżka ma sens — może zmienić kierunek

**PRÓBA 10: Ostatnia szansa**

- Zupełnie inne podejście techniczne
- Dokumentuj każdą próbę i jej wynik

**PO 10 NIEUDANYCH PRÓBACH — rollback + raport:**

```
┌─────────────────────────────────────────┐
│  RAPORT NIEUDANEGO WDROŻENIA           │
├─────────────────────────────────────────┤
│  Zadanie: [co próbował zrobić]         │
│  Liczba prób: 10                       │
│  Rollback do: [tag + data]             │
│  Status strony: ✓ Działa normalnie     │
├─────────────────────────────────────────┤
│  PRÓBA 1: [co + wynik]                 │
│  PRÓBA 2: [co + wynik]                 │
│  ...                                   │
│  PRÓBA 10: [co + wynik]                │
├─────────────────────────────────────────┤
│  GŁÓWNA PRZYCZYNA PROBLEMU: [opis]     │
├─────────────────────────────────────────┤
│  SUGEROWANE ROZWIĄZANIE: [co dalej]    │
└─────────────────────────────────────────┘
```

Strona MUSI działać zanim wyślesz raport.
Najpierw przywróć, potem tłumacz.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### NARZĘDZIA — Z CZEGO OPUS MA KORZYSTAĆ

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Zanim zaczniesz pracę — wylistuj WSZYSTKIE dostępne narzędzia,
MCP serwery, API keys, integracje i pluginy w swoim środowisku.
Używaj KAŻDEGO z nich który może pomóc w danym zadaniu.

**NARZĘDZIA PODSTAWOWE (zawsze sprawdzaj dostępność):**

**GitHub**

- Przeglądaj repo projektu przed jakąkolwiek zmianą
- Szukaj najlepszych praktyk u innych deweloperów
- Szukaj rozwiązań problemów w podobnych projektach
- Twórz feature branche, commituj regularnie, otwieraj PR
- Nigdy nie commituj bezpośrednio na main

**SSH do serwera**

- Zawsze połącz się i sprawdź stan serwera przed pracą
- Porównaj pliki na serwerze z repo — serwer jest źródłem prawdy
- Sprawdzaj logi po każdym wdrożeniu
- Uruchamiaj skrypty deploy.sh i rollback.sh

**ChatGPT API**

- Użyj gdy szukasz rozwiązania trudnego problemu technicznego
- Użyj do weryfikacji podejścia przed wdrożeniem
- Użyj jako drugi głos gdy masz wątpliwości

**Jina API / Web Search**

- Sprawdzaj konkurencję przed rozpoczęciem projektu
- Szukaj aktualnych best practices 2025/2026
- Weryfikuj czy rozwiązanie które planujesz jest aktualne

**Google Cloud (jeśli dostępny)**

- Logi aplikacji, monitoring, deployment
- Cloud Run, Cloud Build, Cloud Storage

**Wszystkie pozostałe wtyczki i integracje w aplikacji desktopowej**

- Wylistuj je na starcie i oceń które są przydatne dla projektu
- Nie ignoruj żadnego narzędzia które mogłoby przyspieszyć pracę

**ZASADY KORZYSTANIA Z NARZĘDZI:**

1. Zawsze zaczynaj od inwentaryzacji — wiedz co masz zanim zaczniesz
2. Dobieraj narzędzie do zadania, nie zadanie do narzędzia
3. Jeśli jedno narzędzie nie daje odpowiedzi — spróbuj innego
4. Dokumentuj z czego korzystałeś i co to dało w raporcie końcowym
5. GitHub innych deweloperów to kopalnia wiedzy — używaj go aktywnie. Szukaj: "laravel 12", "php 8.5", nazwa problemu który rozwiązujesz

**WORKFLOW KAŻDEGO WDROŻENIA (bez wyjątków):**

```bash
git pull origin [branch]
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
chmod -R 775 storage bootstrap/cache
[sprawdź logi: cat storage/logs/laravel.log | tail -20]
```

Używaj deploy.sh który robi to automatycznie z auto-rollback.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### FORMAT RAPORTU KOŃCOWEGO (każdy projekt)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. INWENTARYZACJA — co miałeś do dyspozycji i z czego korzystałeś
2. SKŁAD ZESPOŁU — kto pracował i dlaczego taki dobór
3. RAPORT Z ROZPOZNANIA — co znalazłeś przed startem
4. OCENA PROJECT DIRECTORA — werdykt biznesowy
5. LISTA WDROŻONYCH ZMIAN — z pełnymi ścieżkami plików
6. LISTA POZOSTAŁYCH ZMIAN — co wymaga dalszej pracy
7. DZIENNIK PROJEKTU — lekcje na przyszłość
8. QUICK WINS — co można zrobić w < 30 minut
9. LINK DO PR / commita na GitHubie
10. STATUS WDROŻENIA — czy wszystko działa po zmianach

Działaj autonomicznie. Raportuj co zrobiłeś, nie pytaj co masz robić.
Jeśli coś wymaga decyzji właściciela — zaznacz wyraźnie w raporcie.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

### TOP NARZĘDZIA I SKRYPTY 2026 — STANDARD KAŻDEGO PROJEKTU

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ZASADA GLOBALNA — na starcie każdego projektu Opus sprawdza
dostępność tych narzędzi i aktywnie z nich korzysta.

**AI & CODING**

- Cursor AI — AI editor, naturalny język → kod, kontekst całego repo
- GitHub Copilot — boilerplate, testy, refactoring w locie
- Codex — CLI agent do autonomicznych zadań agentic coding
- ChatGPT API — second opinion przy trudnych problemach

**BUILD TOOLS**

- Vite 6+ — OBOWIĄZKOWY w 2026, instant HMR, build w sekundy
- Biome — linter + formatter, 100x szybszy od ESLint+Prettier
- Lightning CSS — Rust-based CSS minifier, vendor prefixes auto
- Oxc — najszybszy JS bundler 2026 (dla dużych projektów)

**PERFORMANCE & MONITORING**

- Laravel Pulse — darmowy real-time monitoring (queues, queries, errors)
- Laravel Nightwatch — enterprise monitoring z microsecond precision
- Laravel Debugbar — dev tool, 18k+ stars, SQL + performance timeline
- PageSpeed Insights — mierz Core Web Vitals przed i po każdej zmianie
- WebPageTest — waterfall chart, głębsza analiza niż PageSpeed
- UptimeRobot — monitoring uptime co 5 minut, SMS/email alert (free)
- web-vitals JS — LCP/INP/CLS od prawdziwych użytkowników w produkcji

**LARAVEL — TOP PACKAGES 2026**

- spatie/laravel-backup — automatyczne backupy DB + pliki
- spatie/laravel-permission — role i uprawnienia
- spatie/laravel-medialibrary — media, zdjęcia, załączniki
- spatie/laravel-sitemap — auto sitemap.xml dla SEO
- spatie/laravel-schema-org — JSON-LD schema markup z PHP
- spatie/image-optimizer — optymalizacja obrazów po uploadzie
- Pest PHP — testing, czystszy niż PHPUnit
- Larastan/PHPStan — statyczna analiza kodu
- Rector PHP — automatyczny refactoring
- Laravel Pint — oficjalny code style fixer

**MOBILE TESTING**

- Responsively App — wszystkie breakpointy jednocześnie (free, open-source)
- Chrome DevTools — Lighthouse + Network throttling (3G/4G simulation)
- BrowserStack — realne urządzenia (płatny, na finał projektu)

**DEPLOYMENT**

- GitHub Actions — auto testy + deploy po push na main
- deploy.sh — własny skrypt z auto-rollback (twórz dla każdego projektu)

**CSS 2026 — NATYWNIE, BEZ BIBLIOTEK**

- CSS Nesting, Container Queries, :has(), View Transitions
- Scroll-driven animations, dvh/svh, oklch(), @layer, @property
- Wsparcie: 95%+ przeglądarek dla podstawowych, progressive dla reszty

**SKRYPT WERYFIKACYJNY PO KAŻDYM DEPLOYMENCIE:**

```bash
php artisan config:cache && php artisan route:cache &&
php artisan view:cache && php artisan event:cache &&
php artisan optimize && npm run build &&
chmod -R 775 storage bootstrap/cache &&
curl -s -o /dev/null -w "HTTP: %{http_code}\n" [URL] &&
tail -20 storage/logs/laravel.log
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.3
- filament/filament (FILAMENT) - v3
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- livewire/livewire (LIVEWIRE) - v3
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- rector/rector (RECTOR) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pulse-development` — Handles Laravel Pulse setup, configuration, and custom card development. Activates when installing Pulse; configuring the dashboard or authorization gate; setting up recorders and filtering; building custom Livewire cards; optimizing with Redis ingest or sampling; or when the user mentions /pulse, pulse:check, pulse:work, Pulse::record(), or application monitoring.
- `pest-testing` — Tests applications using the Pest 3 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, architecture testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`, `php artisan tinker --execute "..."`).
- Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `php artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `php artisan config:show [key]`.
- To inspect routes, run `php artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->

```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 13

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 13 Structure

- In Laravel 13, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 13 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

</laravel-boost-guidelines>

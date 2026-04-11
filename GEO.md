# GEO — mapa kanoniczna RS Performance (2026+)

> **Jedno zrodlo prawdy** dla Generative Engine Optimization w tym repo: jak GEO laczy sie z AEO, ktore skille ladowac i czemu sa zdublowane foldery `geo-*` / `geo-geo-*`.  
> Szczegóły operacyjne hosting / VPS: `RELAY.md`, `HANDOFF.md`, `plan.md`.

## 1. SEO vs GEO vs AEO (jak to traktujemy w RS)

| Etykieta | Cel | Typowe metryki / dowód |
|----------|-----|-------------------------|
| **SEO** | Widoczność w klasycznych wynikach (Google, Bing) | Ranking, SERP, CTR, CWV |
| **GEO** | **Generative Engine Optimization** — być cytowanym i poprawnie rozumianym w **odpowiedziach generatywnych** (AI Overviews, asystenci, panele z linkami zrodlowymi) | Cytowalność fragmentów, encje, `llms.txt`, dostęp crawlerów AI, schema jako dane dla modeli, freshness |
| **AEO** | **Answer Engine Optimization** — nadrzędny priorytet strategiczny projektu: *wszystko*, co sprawia, że silniki odpowiedzi i agenci **traktują nas jako zrodlo odpowiedzi**, nie tylko jako URL | Spójność discovery, bramka `ai.*`, nagłówki hospitality, graph schema, brak sprzecznych „prawd”, `knowledge_plane` |

**Związek:** w RS **AEO jest szersze niż GEO**. GEO to **filary treści + dystrybucji + cytowalności** pod silniki generatywne; AEO dodaje **architekturę canonical / gateway / MCP / A2A / operator workflow**, żeby odpowiedź była nie tylko możliwa do zacytowania, ale **utrzymywalna i zgodna z produkcją**.

## 2. Kanoniczny stos pracy (kolejność)

1. **`rs-discovery-wow-2026`** — orchestracja warstwy publicznej (manifesty, nagłówki, checklist WOW, alignment hosting vs bramka).
2. **`rs-aeo-skill`** — konwencje projektu: BLUF, schema dla AutoRepair, polityka crawlerów (zgodnie z aktualnym `robots` / decyzjami — nie kopiować starych snippetów bez weryfikacji produkcji).
3. **`rs-schema-wow-2026`** — graph JSON-LD, encje, speakable, polityka „no price leakage” gdzie obowiązuje.
4. **GEO modułowo** — wybierz **jeden lub kilka** wg zadania (nie wszystkie naraz):

   | Skill | Kiedy |
   |-------|--------|
   | `geo-llmstxt` | `llms.txt` / `llms-full.txt`, kuracja ścieżek |
   | `geo-crawlers` | `robots.txt`, mapa dostępu botów AI |
   | `geo-citability` | scoring cytowalności, „quotable blocks” |
   | `geo-content` | E-E-A-T / jakość pod odpowiedzi AI |
   | `geo-technical` | nagłówki HTTP, indeksacja, warstwa techniczna czytelna dla agentów |
   | `geo-schema` | structured data pod AI (często overlap z `rs-schema-wow-2026`) |
   | `geo-platform-optimizer` | tuning pod konkretne platformy (ChatGPT, Perplexity, AIO, …) |
   | `geo-brand-mentions` | sygnały encji / brandu poza własną domeną |
   | `geo-audit` | pełniejszy audyt GEO, gdy nie wystarcza sama checklista discovery |
   | `geo-fundamentals` | onboarding / teoria GEO |

5. **A2A (osobny tor od GEO):** `rs-a2a-wow-2026` — karty agenta, `/.well-known/a2a.json`, cykl zadań, Overture `certify`. Nie zastępuje GEO; uzupełnia **agent-to-agent**.

6. **Wektor / RAG:** `qdrant-memory-market`, `qdrant-rest-api-market` — zgodnie z polem `knowledge_plane` w `/.well-known/ai-resources.json`.

## 3. Duplikaty: `geo-*` vs `geo-geo-*` (reguła RS)

W `.agents/skills/` istnieją **dwa równoległe drzewa**:

- `geo-<obszar>/` — **preferowany** zestaw modułowy dla tego workspace.
- `geo-geo-<obszar>/` oraz umbrella `geo-geo/` — **duplikat tego samego pakietu** (inny prefiks narzędzia / importu historycznego).

**Reguła:** przy pracy w Cursor / relay **czytaj i edytuj treści w `geo-*`**. Foldery `geo-geo-*` traktuj jako **lustro / legacy** — nie utrzymuj dwóch wersji treści; jeśli musisz zmienić procedurę GEO, robi się to w **`geo-*`**, a duplikat ewentualnie synchronizuje się jednorazowo lub zostaje do usunięcia w osobnym tasku sprzątającym.

## 4. Powierzchnie produkcyjne (skrót)

- **Canonical:** `https://rsperformance.online/`
- **Bramka AI / fast lane:** `https://ai.rsperformance.online/`
- **Kontrakt dla modeli:** m.in. `/.well-known/ai-resources.json` (w tym `knowledge_plane`)

Szczegóły deploy i testów redirectów: `SESSION_LOG.md`, `HANDOFF.md`, `.htaccess_remote`.

## 5. Kiedy otworzyć ten plik

- Start audytu GEO / „czy jesteśmy cytowalni”
- Wybór skilli przy kolizji nazw
- Onboarding nowego agenta (razem z `RELAY.md`)

---

*Ostatnia aktualizacja mapy: 2026-04-12 — konsolidacja dokumentacji i reguły deduplikacji skilli.*

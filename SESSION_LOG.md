# SESSION_LOG — RS Performance (append-only)

Format relay: jeden blok na sesję, bez kasowania cudzych wpisów. Starsze wpisy przenosimy do archiwum dopiero gdy plik przekroczy ~50 wpisów (patrz `RELAY.md`).

---

## [2026-04-12 ~01:20 CET] Cursor — Retest bramki `ai.*` + synchronizacja relay (wow)

### Kontekst

Po wdrożeniu naprawy `.htaccess` (literalna spacja w `ChatGPT Atlas` psująca `RewriteCond` na LiteSpeed) potrzebny był **twardy dowód** łańcucha przekierowań i braku pętli, plus aktualizacja `HANDOFF.md` / tego pliku zgodnie z protokołem relay.

### Wykonane (read-only na produkcji w tej mini-sesji)

- Pomiar `curl` z `-w num_redirects` / `url_effective` dla canonical i bramki.
- Smoke ścieżki `/for-agents` na VPS oraz głębokiego linku `/uslugi` z UA bota.

### Wyniki weryfikacji (żywe URL)

Pomiar `redirects` / `final_url`: `curl -L` (follow), `-w '%{num_redirects} %{url_effective} %{http_code}'`.

| Test | Wynik |
|------|--------|
| `GET https://rsperformance.online/` + `User-Agent: GPTBot` | `redirects=1`, `final_url=https://ai.rsperformance.online/`, `http_code=200` |
| `GET https://rsperformance.online/` + browser UA | `redirects=0`, zostaje na canonical, `200` |
| `GET https://ai.rsperformance.online/` + `GPTBot` | `redirects=0`, `200` (brak zwrotnego 302 na canonical — **brak pętli**) |
| `GET https://ai.rsperformance.online/` + browser | `redirects=0`, `200` |
| `GET https://ai.rsperformance.online/for-agents` + `GPTBot` | `200`, nagłówki m.in. `X-AI-Hospitality`, `X-AI-Gateway`, `X-AI-Preferred-Fetch-Order` (w polu wielkość liter może się różnić — `X-Ai-*` vs `X-AI-*`) |
| `GET https://rsperformance.online/uslugi` + `GPTBot` | `redirects=1`, finalnie `200` na `https://ai.rsperformance.online/uslugi` |

### Artefakty repo

- `HANDOFF.md` — sekcje: AKTUALNA OPERACJA (wyczyszczona), OSTATNI AGENT, NASTĘPNE KROKI (naprawiony nagłówek z wcześniejszego mojibake `NAST…PNE`).
- `plan.md` — krótki addendum 2026-04-12 pod fokusem.
- `handoff-log.md` — jeden wpis opisowy (brak nowego deploy na hosting w tej turze).

### Git

- Planowany commit: `SESSION_LOG.md`, `HANDOFF.md`, `plan.md`, `handoff-log.md` (razem z sesją).

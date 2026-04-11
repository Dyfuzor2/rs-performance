# Mapa produktow AI -> tokeny RS Performance

Zrodlo techniczne: `config/ai_agents.php` oraz generowany `robots.txt` (dla kazdego tokenu: `Allow: /` i wspolne `Disallow` m.in. na `/admin`, `/storage`).

Bramka WOW (hosting `.htaccess`): **302** na `https://ai.rsperformance.online/` dla agentow z `gateway_routed_agents`. **Wyjatek SEO:** `Googlebot` i `Bingbot` zostaja na canonical.

## Glowne modele / ekosystemy

| Produkt | Token(y) w katalogu |
|--------|----------------------|
| ChatGPT / OpenAI | `GPTBot`, `ChatGPT-User`, `ChatGPTBrowser`, `ChatGPT Atlas`, `chatgpt%20atlas`, `OAI-SearchBot`, `OpenAI-SearchBot` |
| Claude / Anthropic | `ClaudeBot`, `Claude-SearchBot`, `Claude-User`, `Claude-Web`, `anthropic-ai` |
| Gemini / Google AI (poza klasycznym Googlebot) | `GeminiBot`, `Google-Extended`, `GoogleOther`, `GoogleOther-Image`, `GoogleOther-Video`, `Google-CloudVertexBot`, `Storebot-Google` |
| Grok / xAI | `GrokBot`, `Grok-DeepSearch`, `xAI-Grok` |
| Mistral / Le Chat | `MistralBot`, `MistralAI-User` |
| Llama / Meta (link preview + fetchery) | `Meta-ExternalAgent`, `Meta-ExternalFetcher`, `meta-externalfetcher`, `FacebookBot`, `facebookexternalhit`, `Meta-ExternalAds`, `meta-webindexer` |

## Research / cytowanie

| Produkt | Token(y) |
|--------|----------|
| Perplexity | `PerplexityBot`, `Perplexity-User` |
| Phind | `PhindBot` |
| Genspark | `Gensparkbot` |
| Consensus, Elicit, Scite.ai, NotebookLM | **Brak stabilnego publicznego UA** — ruch czesto jako `Google-Extended` / rodzina Google / zwykla przegladarka. Objete **`User-agent: *`** (`Allow: /`). |

## Przegladarki AI / agenci w przegladarce

| Produkt | Token(y) / uwagi |
|--------|-------------------|
| Perplexity Comet | `PerplexityBot`, `Perplexity-User` (+ czesto UA przegladarki) |
| ChatGPT Atlas | `ChatGPT Atlas`, `chatgpt%20atlas` |
| Arc (Arc Max) | `ArcSearch` |
| Brave Leo | `Brave`, `BraveSearch` (asystent w ekosystemie Brave) |
| SigmaOS, Dia, Opera Aria | **Brak osobnego tokenu w robots** — typowo Chromium + zwykly HTTP; objete **`User-agent: *`**. |

## Agenci / automatyzacja

| Produkt | Token(y) |
|--------|----------|
| Firecrawl | `FirecrawlBot` |
| Manus | `Manus-User` |
| Zapier | `Zapier` |
| Workbeaver, Skyvern | **Brak publikowanego UA** — `User-agent: *` + normalna goscinnosc HTTP. |
| AutoGPT / BabyAGI | **Brak** — zwykle wlasny stack / headless; `User-agent: *`. |

## Narzedzia dla programistow

| Produkt | Token(y) |
|--------|----------|
| GitHub Copilot (fetch / browse) | `CopilotUser` |
| Cursor, v0.dev | **Brak dedykowanego crawler UA** — IDE / przegladarka; `User-agent: *`. |

---

**Szybka regula:** jesli produktu nie ma w tabeli jako token, nadal jest **dozwolony** przez `Allow: /` w bloku `User-agent: *` na `robots.txt`, o ile WAF nie blokuje IP/behaviour.

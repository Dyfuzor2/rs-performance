# Mapa produktow AI -> tokeny RS Performance

Zrodlo techniczne: `config/ai_agents.php` oraz generowany `robots.txt` (dla kazdego tokenu: `Allow: /` i wspolne `Disallow` m.in. na `/admin`, `/storage`).

Bramka WOW (hosting `.htaccess`): **302** na `https://ai.rsperformance.online/` dla agentow z `gateway_routed_agents`. **Wyjatek SEO:** `Googlebot` i `Bingbot` zostaja na canonical.

## Glowne modele / ekosystemy

| Produkt                                        | Token(y) w katalogu                                                                                                                               |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| ChatGPT / OpenAI                               | `GPTBot`, `ChatGPT-User`, `ChatGPTBrowser`, `ChatGPT Atlas`, `chatgpt%20atlas`, `OAI-SearchBot`, `OpenAI-SearchBot`                               |
| Claude / Anthropic                             | `ClaudeBot`, `Claude-SearchBot`, `Claude-User`, `Claude-Web`, `anthropic-ai`                                                                      |
| Gemini / Google AI (poza klasycznym Googlebot) | `GeminiBot`, `Google-Extended`, `GoogleOther`, `GoogleOther-Image`, `GoogleOther-Video`, `Google-CloudVertexBot`, `Storebot-Google`               |
| Grok / xAI                                     | `GrokBot`, `Grok-DeepSearch`, `xAI-Bot`, `xAI-Grok`                                                                                               |
| Mistral / Le Chat                              | `MistralBot`, `MistralAI-User`, `MistralAI-Index`                                                                                                 |
| Llama / Meta (link preview + fetchery)         | `Meta-ExternalAgent`, `Meta-ExternalFetcher`, `meta-externalfetcher`, `FacebookBot`, `facebookexternalhit`, `Meta-ExternalAds`, `meta-webindexer` |
| DeepSeek                                       | `DeepSeekBot`, `DeepSeek-User`                                                                                                                      |
| OpenRouter (partner fetch / custom UA)         | `OpenRouter`                                                                                                                                        |
| Hugging Face (fetch / discovery)               | `HuggingFaceBot` (search), `hugging-face-ai` (user_fetchers)                                                                                        |
| Amazon (preview / fetch)                       | `Amazonbot`                                                                                                                                         |

## Indeksacja / SEO / narzedzia SERP

| Produkt / klasa          | Token(y) w katalogu                                                                                                      |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Google (klasyczny indeks) | `Googlebot` (canonical SEO), `GoogleOther`, `GoogleOther-Image`, `GoogleOther-Video`, `Storebot-Google`                  |
| Bing                      | `Bingbot` (canonical SEO), `BingPreview`                                                                                 |
| Ahrefs / Semrush / DataForSEO / BrightEdge | `AhrefsBot`, `SemrushBot`, `DataForSeoBot`, `BrightEdge`                                                   |
| DuckDuckGo / asystent   | `DuckDuckBot`, `DuckAssistBot`                                                                                           |
| Social discovery        | `Twitterbot`, `LinkedInBot`, `FacebookBot`, `RedditBot`                                                                  |
| AI2 / Dolma             | `AI2Bot`, `Ai2Bot-Dolma`                                                                                                 |

## Trening / dataset / crawlery dostawcow

| Produkt / klasa                         | Token(y)                                                                                                                                   |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Cohere                                  | `cohere-ai`, `cohere-crawler`, `cohere-training-data-crawler`                                                                              |
| Together AI                           | `Together-Bot`                                                                                                                             |
| Scale AI                                | `ScaleAI-Crawler`                                                                                                                          |
| ByteDance / Petal                       | `Bytespider`, `PetalBot`                                                                                                                   |
| Inne crawlery datasetow                 | `img2dataset`, `Omgilibot`, `PanguBot`, `Timpibot`, `Brightbot`, `Diffbot`                                                                 |

## Research / cytowanie

| Produkt                                 | Token(y)                                                                                                                                                 |
| --------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Perplexity                              | `PerplexityBot`, `Perplexity-User`                                                                                                                       |
| Phind                                   | `PhindBot`                                                                                                                                               |
| Genspark                                | `Gensparkbot`                                                                                                                                            |
| Parallel (Shap)                         | `ShapBot`                                                                                                                                                |
| Exa / legacy Exalead substring          | `Exabot` (dopasowanie substring; dokumentacja w `config/ai_agents.php`)                                                                                  |
| Mistral indeks                          | `MistralAI-Index`, `MistralBot`                                                                                                                          |
| Kagi / Neeva / Quora / Reader           | `KagiBot`, `Neeva-Crawler`, `Quora-Bot`, `ReaderBot`                                                                                                     |
| WebPilot / Velen                        | `WebPilot`, `VelenPublicWebCrawler`                                                                                                                       |
| Consensus, Elicit, Scite.ai, NotebookLM | **Brak stabilnego publicznego UA** — ruch czesto jako `Google-Extended` / rodzina Google / zwykla przegladarka. Objete **`User-agent: *`** (`Allow: /`). |

## Przegladarki AI / agenci w przegladarce

| Produkt                  | Token(y) / uwagi                                                                               |
| ------------------------ | ---------------------------------------------------------------------------------------------- |
| Perplexity Comet         | `PerplexityBot`, `Perplexity-User` (+ czesto UA przegladarki)                                  |
| ChatGPT Atlas            | `ChatGPT Atlas`, `chatgpt%20atlas`                                                             |
| Arc (Arc Max)            | `ArcSearch`                                                                                    |
| Brave Leo                | `Brave`, `BraveSearch` (asystent w ekosystemie Brave)                                          |
| SigmaOS, Dia, Opera Aria | **Brak osobnego tokenu w robots** — typowo Chromium + zwykly HTTP; objete **`User-agent: *`**. |

## Agenci / automatyzacja

| Produkt             | Token(y)                                                                |
| ------------------- | ----------------------------------------------------------------------- |
| Firecrawl           | `FirecrawlBot`, `Firecrawl`, `FirecrawlAgent`                           |
| Manus               | `Manus-User`                                                            |
| Zapier              | `Zapier`                                                                |
| Workbeaver, Skyvern | **Brak publikowanego UA** — `User-agent: *` + normalna goscinnosc HTTP. |
| AutoGPT / BabyAGI   | **Brak** — zwykle wlasny stack / headless; `User-agent: *`.             |

## Narzedzia dla programistow

| Produkt                         | Token(y)                                                                |
| ------------------------------- | ----------------------------------------------------------------------- |
| GitHub Copilot (fetch / browse) | `CopilotUser`, `GitHub-Copilot`                                         |
| Cursor                          | `Cursor` (substring w UA; bramka + katalog `user_fetchers`)             |
| Vercel / v0                     | `VercelBot` (v0.dev czesto bez osobnego tokenu — nadal `User-agent: *`) |

## Operator deny (tylko jawne)

W `denied_agents` (robots `Disallow: /`, brak WOW302 / brak obejsc ModSec dla tych literalnych UA): `CCBot`, `iaskbot`, `magpie-crawler`.

---

**Szybka regula:** jesli produktu nie ma w tabeli jako token, nadal jest **dozwolony** przez `Allow: /` w bloku `User-agent: *` na `robots.txt`, o ile WAF nie blokuje IP/behaviour. Pelna lista tokenow: `config/ai_agents.php` (tablice `search_bots`, `training_bots`, `user_fetchers`).

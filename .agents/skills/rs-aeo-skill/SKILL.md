---
name: rs-aeo-skill
description: Autorski skill AEO (Answer Engine Optimization) dla RS Performance. Optymalizacja pod LLMy (ChatGPT, Gemini, Perplexity), zarządzanie polityką crawlerów oraz strukturami Schema.org dla branży AutoRepair (rocznik 2026+).
version: 1.0.0
updated: 2026-03-22
---

# RS Performance: AEO & SEO Execution Skill (2026+)

Ten skill definiuje **Bezwzględny Standard AEO** przy pracy nad kodem, treściami oraz plikami `llms.txt` dla środowiska produkcyjnego `rsperformance.pl`. 

Jako agent AI jesteś **ZOBOWIĄZANY** stosować poniższe zasady przy każdej modyfikacji contentu, widoków Blade i konfiguracji SEO.

## 1. Architektura Publikacji "BLUF" (Bottom Line Up Front)
Od 2026 r. chatboty (GPT, Perplexity) analizują treść hierarchicznie. Ukrywanie meritum na dole strony sprawia, że AI tego nie indeksuje w szybkich odpowiedziach.

**Przy generacji treści / Markdown Mirrors (`app/Support/RsUri.php` -> `llms.txt`) stosuj zasadę:**
1. **Zwięzłe streszczenie akcji (BLUF):** Pierwsze zdanie w dokumencie lub na podstronie musi bezwzględnie odpowiadać, KIM jesteśmy, CO robimy i KIEDY to robimy. *(np. "RS Performance w Gdańsku specjalizuje się w wymianie oleju z certyfikatem w 45 minut.")*
2. **Tabele JSON-LD zamiast list na H2:** Używaj precyzyjnego Schema zamiast nieustrukturyzowanego tekstu do cen. (AI preferuje dane tabularyczne i czystego JSON-LD nad tekst ciągły).
3. **Problem / Rozwiązanie:** Odpowiedzi do pytań PAA (People Also Ask) wymuszają układ problem-rozwiązanie (nagłówek to pytanie klienta z długiego ogona, akapit to natychmiastowa odpowiedź).

## 2. Piekarnik Schema.org — Mikro-Specjalizacje

Ogólna struktura strony ma już schema `AutoRepair`, ale **każda podstrona usługi (DSG, DPF, Geometria)** musi implementować Schema typu `Service`:

```javascript
{
  "@@context": "https://schema.org",
  "@@type": "Service",
  "name": "Regeneracja DPF Gdańsk",
  "provider": {
    "@@type": "AutoRepair",
    "name": "RS Performance Sp. z o.o."
  },
  "areaServed": {
    "@@type": "City",
    "name": "Gdańsk"
  },
  "offers": {
    "@@type": "Offer",
    "priceCurrency": "PLN",
    "price": "KOSZT_BAZOWY",
    "availability": "https://schema.org/InStock",
    "url": "URL_DO_FORMULARZA_REZERWACJI"
  }
}
```
*Zawsze upewnij się, że struktura `Offer` zawiera link do rezerwacji, a nie tylko suchą cenę*.

## 3. Polityka Obrony Transferu (AI Crawler Policy)

Aby chronić produkcyjny VPS (s181.cyber-folks.pl) przed wyssaniem zasobów przez farmy scraperów oraz umożliwić wejście wyłącznie wybranym gigantom, **wymagane są następujące zasady dodawane do `robots.txt`**:

```text
# ==========================================
# 🛑 BLOKADA ŚMIECIOWYCH AI SCRAPERÓW 🛑
# ==========================================
User-agent: CCBot
Disallow: /
User-agent: Bytespider
Disallow: /
User-agent: Diffbot
Disallow: /
User-agent: Amazonbot
Disallow: /
User-agent: ClaudeBot
Disallow: /
User-agent: anthropic-ai
Disallow: /

# ==========================================
# ✅ AUTORYZOWANE BOTY AEO (2026+) ✅
# ==========================================
User-agent: GPTBot
Allow: /
User-agent: Google-Extended
Allow: /
User-agent: Applebot-Extended
Allow: /
User-agent: PerplexityBot
Allow: /
```

## 4. Wdrażanie Obrazów OpenGraph (`seo-image-gen`)

Agent, używając tego skilla w duecie z `seo-image-gen`, nie może dopuścić, by strona lądowania zawierała ogólne logo jako obraz w `og:image`. 
1. Wykorzystuj API `Vertex AI` do generacji Hero Image dla podstrony.
2. Skonwertuj używając Sharp/Spatie do `.webp` lub `.avif`.
3. Podmień atrybuty `<meta property="og:image" content="{{ asset('images/hero/DSG-header.webp') }}">`.
4. Nadaj opis `alt` nasycony frazą (LSI Keywords), np. "Profesjonalny stół diagnostyczny do regeneracji skrzyń DSG w RS Performance Gdańsk".

---

## GEO (Generative Engine Optimization)

**AEO** w RS jest szersze niż **GEO**; moduły GEO (`geo-llmstxt`, `geo-crawlers`, `geo-citability`, …) i reguła deduplikacji `geo-*` vs `geo-geo-*`: patrz kanoniczna mapa **`G:\gravity\GEO.md`**.

---
*Użycie tego skilla jest obowiązkowe zgodnie ze zrewidowaną dyrektywą RS Performance PRO2.*

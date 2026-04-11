# 🤖 RS Performance — Telegram Bot Skills

Ten folder zawiera dokumentację i wzorce (skills) dla bota Telegram zarządzanego przez n8n na VPS (`auto.rs3d.pl`).

## Główne informacje
- **Bot ID:** `8657034872`
- **Workflow:** `RcpAgentWF001` (RS AI Agent Recepcja)
- **Rola:** Pierwszy kontakt, obsługa zapytań warsztatowych, AI concierge.

## Dostępne komendy (2026-04-06)
- `/start` — Powitanie i inicjalizacja sesji.
- `/help` — Lista możliwości bota.
- `/?` — Szybka pomoc (alias /help).

## Dodawanie nowych "Skilli" do Agenta
1. Otwórz `auto.rs3d.pl`.
2. Edytuj workflow `RcpAgentWF001`.
3. Dodaj węzeł typu `Tool` (np. Google Search, Custom API).
4. Podepnij `Tool` do głównego węzła `AI Agent`.
5. Zaktualizuj system prompt w węźle AI, aby wiedział jak korzystać z nowego narzędzia.

## WOW digest (n8n fleet + health → Telegram)

- Skrypt na VPS (parse `sendMessage` z workflow **RS AI Agent Monitor** `W1xRg73xFDUXYrRI`, token nie jest logowany):
  - `python3 /tmp/vps_n8n_telegram_wow_digest.py` (upload z repo: `scripts/vps_n8n_telegram_wow_digest.py`)
- W jednej wiadomości HTML: macierz health (site, AI gw, MCP, n8n, llms), probe webhooka zaproszeń, **ostatni status wykonania** każdego aktywnego workflowu (15) z SQLite.

## Diagnostyka
- Jeśli bot nie odpowiada: `docker restart 9fe84d016d37` na VPS.
- Sprawdź Webhook URL w Telegram Trigger: musi wskazywać na `https://auto.rs3d.pl/webhook/...`.

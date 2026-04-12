# Telegram MCP runtime (RS Gravity, April 2026+)

Stdio MCP over **Telegram Bot API** — operator lane (`getMe`, `sendMessage`). Same secret pattern as n8n: **`.cursor/mcp.env`** (gitignored).

## Install

```powershell
Set-Location G:\gravity\tools\telegram-mcp-runtime
.\install-telegram-mcp.ps1
```

Required env (via `mcp.env`):

- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID` (default recipient for `telegram_send_message` when `chat_id` is omitted)

## Cursor

`.mcp.json` / `.cursor/mcp.json` entry:

```json
"telegram-rs": {
  "command": "powershell",
  "args": [
    "-NoProfile",
    "-ExecutionPolicy",
    "Bypass",
    "-File",
    "G:\\gravity\\tools\\telegram-mcp-runtime\\run-telegram-mcp.ps1"
  ],
  "description": "Telegram Bot API MCP: getMe + sendMessage; secrets from .cursor/mcp.env."
}
```

Reload MCP after changes.

## Tools

| Tool | Role |
|------|------|
| `telegram_get_me` | Validate token |
| `telegram_send_message` | Operator notifications (`text`, optional `chat_id`, `parse_mode`) |

## Safety

- No arbitrary HTTP; only `api.telegram.org` with your bot token.
- Do not commit filled `mcp.env`.

# GCP / gcloud WOW runtime (RS Gravity, April 2026+)

## Should you install `gcloud` locally?

| Scenario | Recommendation |
|----------|----------------|
| **Vertex / Gemini experiments, ADC, `gcloud auth application-default login`** | Yes — local SDK speeds up iteration; service account JSON paths stay per `vertex.md`. |
| **Production deploy / canonical runtime** | No — use **VPS + SSH** and project relay (`AGENTS.md`); hosting remains canonical for the public site. |
| **CI / agents** | Prefer **read-only** checks via this MCP or scripted probes; avoid broad shell access. |

## Install Google Cloud SDK (Windows)

```powershell
cd G:\gravity\tools\gcp-gcloud-wow-runtime
.\install-gcloud-wow.ps1
```

New terminal, then:

```powershell
gcloud version
gcloud auth application-default login
```

## MCP dependencies (read-only status server)

```powershell
.\install-gcloud-wow-runtime-deps.ps1
```

## Cursor — stdio MCP entry

Add to `.mcp.json` / `.cursor/mcp.json`:

```json
"gcloud-wow": {
  "command": "powershell",
  "args": [
    "-NoProfile",
    "-ExecutionPolicy",
    "Bypass",
    "-File",
    "G:\\gravity\\tools\\gcp-gcloud-wow-runtime\\run-gcloud-wow-mcp.ps1"
  ],
  "description": "Read-only gcloud status (version, config list). Requires gcloud on PATH."
}
```

## Tools

| Tool | gcloud invocation |
|------|-------------------|
| `gcloud_wow_version` | `gcloud version --format=json` |
| `gcloud_wow_config_list` | `gcloud config list --format=json` |

No arbitrary commands — allowlist only.

## Why not a full “GCP admin” MCP?

Full cloud control from an IDE MCP is a **high blast-radius** surface. This stack keeps **documentation** via Context7 / official docs MCPs and **local CLI** for authenticated human/operator flows; VPS remains the support-plane for heavy jobs.

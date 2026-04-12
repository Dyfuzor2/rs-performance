# GitHub MCP (official, pinned local)

Uses [github/github-mcp-server](https://github.com/github/github-mcp-server) **stdio** mode with **`--read-only`** so agents can browse/search **public** repos without write tools.

## Install (once)

```powershell
Set-Location G:\gravity\tools\github-mcp-runtime
powershell -ExecutionPolicy Bypass -File .\install-github-mcp.ps1
```

This downloads `v0.32.0` **Windows x86_64** and extracts `bin\github-mcp-server.exe` (folder `bin/` is gitignored).

## Token

- Preferred: Windows user env var **`GITHUB_PERSONAL_ACCESS_TOKEN`** (classic PAT or fine-grained with read access to public data).
- Or: first line of **`G:\gravity\git.md`** (already gitignored; same convention as `RELAY.md`).

Minimal scopes for mostly public exploration: read-only repo metadata; GitHub may still require a valid PAT for API rate limits.

## Cursor

Root **`G:\gravity\.mcp.json`** defines server **`github`** calling `run-github-mcp.ps1`. Reload MCP after install.

## Alternative: Docker

```bash
docker run -i --rm -e GITHUB_PERSONAL_ACCESS_TOKEN -e GITHUB_TOOLSETS=repos,users,context ghcr.io/github/github-mcp-server stdio --read-only
```

## Alternative: remote (Copilot API)

HTTPS `https://api.githubcopilot.com/mcp/` — see upstream README; often needs Copilot / OAuth setup.

# GitHub MCP (official, pinned local)

Uses [github/github-mcp-server](https://github.com/github/github-mcp-server) **stdio** mode with **`--read-only`** so agents can browse/search **public** repos without write tools.

## Quick path (install + token check + operator deck)

```powershell
Set-Location G:\gravity\tools\github-mcp-runtime
powershell -ExecutionPolicy Bypass -File .\setup-github-mcp-wow.ps1
```

Runs `install-github-mcp.ps1` if needed, verifies the token with `test-github-token.ps1`, and opens `wow.html` in the browser (local checklist, no secrets).

## Install (once)

```powershell
Set-Location G:\gravity\tools\github-mcp-runtime
powershell -ExecutionPolicy Bypass -File .\install-github-mcp.ps1
```

This downloads `v0.32.0` **Windows x86_64**, verifies **SHA256** against the official checksums file plus an embedded pin, and extracts `bin\github-mcp-server.exe` (folder `bin/` is gitignored).

## Token

- Preferred: Windows user env var **`GITHUB_PERSONAL_ACCESS_TOKEN`** (classic PAT or fine-grained with read access to public data).
- Or: first line of **`G:\gravity\git.md`** (already gitignored; same convention as `RELAY.md`).

Minimal scopes for mostly public exploration: read-only repo metadata; GitHub may still require a valid PAT for API rate limits.

To test the token without starting the MCP server:

```powershell
powershell -ExecutionPolicy Bypass -File .\test-github-token.ps1
```

## Cursor

Root **`G:\gravity\.mcp.json`** defines server **`github`** calling `run-github-mcp.ps1`. Reload MCP after install.

## Alternative: Docker

```bash
docker run -i --rm -e GITHUB_PERSONAL_ACCESS_TOKEN -e GITHUB_TOOLSETS=repos,users,context ghcr.io/github/github-mcp-server stdio --read-only
```

## Alternative: remote (Copilot API)

HTTPS `https://api.githubcopilot.com/mcp/` — see upstream README; often needs Copilot / OAuth setup.

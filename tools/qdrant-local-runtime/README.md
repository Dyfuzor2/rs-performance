# Qdrant local + RAG (RS Gravity, pinned next to `qdrant-mcp-runtime`)

Runs **Qdrant** in Docker on **`127.0.0.1:6333`** with persistent storage under **`./data/storage`**. Use the **same** MCP binary as production-oriented entries (`tools/qdrant-mcp-runtime/Scripts/mcp-server-qdrant.exe`) but point **`QDRANT_URL`** at localhost and **`COLLECTION_NAME`** at `rs_local_*` collections so you never mix vectors with the VPS plane.

## Requirements

- **Docker Desktop** (Windows), WSL2 backend recommended.
- **~500MB** disk for the image; `./data` grows with collections.

## Quick path (start + health + deck)

```powershell
Set-Location G:\gravity\tools\qdrant-local-runtime
powershell -ExecutionPolicy Bypass -File .\setup-qdrant-local-wow.ps1
```

## Manual

```powershell
.\start-qdrant.ps1
.\test-qdrant-local.ps1   # GET http://127.0.0.1:6333/readyz
```

Stop:

```powershell
.\stop-qdrant.ps1
```

## Port conflict

If **6333** is already used (e.g. SSH tunnel to VPS), copy `ports.example.env` → **`.env`** in this folder and set `QDRANT_LOCAL_HTTP` / `QDRANT_LOCAL_GRPC`. Update **`QDRANT_URL`** in `.mcp.json` for the `*-local` servers to match (e.g. `http://127.0.0.1:16333`).

## Cursor MCP

Root **`G:\gravity\.mcp.json`** defines **`qdrant-rs-*-local`** servers. After Qdrant is up, **reload MCP** in Cursor.

## Collections

Names mirror the VPS collections with an **`rs_local_`** prefix (empty until you upsert). Safe for experiments and ingestion dry-runs.

## Notes

- **VPS** (`185.180.207.211:6333`) remains the canonical sync target for production jobs; this stack is **dev-only**.
- Embedding model for MCP stays aligned with existing entries: `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`.

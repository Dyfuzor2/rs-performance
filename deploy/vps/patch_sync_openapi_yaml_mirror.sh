#!/usr/bin/env bash
# Idempotent: append openapi.json -> yaml mirror step before final COMPLETE log in ai-gateway sync.sh.
# Run on VPS once: bash /tmp/patch_sync_openapi_yaml_mirror.sh
set -euo pipefail

SYNC="/srv/ai-gateway/sync.sh"
if [[ ! -f "$SYNC" ]]; then
  echo "missing $SYNC" >&2
  exit 1
fi

if grep -q "mirror_openapi_yaml.py" "$SYNC"; then
  echo "already patched"
  exit 0
fi

cp -- "$SYNC" "${SYNC}.bak_mirror_openapi_yaml_$(date -u +%Y%m%dT%H%M%SZ)"

python3 <<'PY'
from pathlib import Path

sync = Path("/srv/ai-gateway/sync.sh")
text = sync.read_text(encoding="utf-8")
mark = 'log "========== AI GATEWAY SYNC COMPLETE =========="\n'
insert = '''if [[ -f /srv/ai-gateway/bin/mirror_openapi_yaml.py ]]; then
  python3 /srv/ai-gateway/bin/mirror_openapi_yaml.py >>"$LOG" 2>&1 && log "OK  mirrored openapi.json -> openapi.yaml" || log "WARN mirror_openapi_yaml failed"
fi

'''
if mark not in text:
    raise SystemExit("anchor not found in sync.sh")
sync.write_text(text.replace(mark, insert + mark), encoding="utf-8")
print("patched", sync)
PY

bash -n "$SYNC"
echo "bash -n OK"

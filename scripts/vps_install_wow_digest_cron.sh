#!/usr/bin/env bash
# Install daily RS n8n WOW Telegram digest (08:00 Europe/Warsaw) on VPS host.
# Idempotent: removes old digest line, keeps other crontab entries.
set -euo pipefail

SCRIPT_DST="/srv/ops-stack/scripts/vps_n8n_telegram_wow_digest.py"
LOG_DIR="/srv/ops-stack/logs"
LOG_FILE="${LOG_DIR}/n8n_wow_digest.log"
CRON_LINE='0 8 * * * /usr/bin/env N8N_SQLITE=/srv/ops-stack/n8n/storage/database.sqlite /usr/bin/python3 '"${SCRIPT_DST}"' --quiet >> '"${LOG_FILE}"' 2>&1'

mkdir -p "$(dirname "$SCRIPT_DST")" "$LOG_DIR"

if [[ ! -f "$SCRIPT_DST" ]]; then
  echo "ERROR: missing $SCRIPT_DST (upload script first)" >&2
  exit 1
fi

Bak="/srv/ops-stack/logs/crontab.bak_before_wow_digest_$(date +%Y%m%d%H%M%S)"
crontab -l >"$Bak" 2>/dev/null || true

TMP="$(mktemp)"
{
  crontab -l 2>/dev/null | grep -v 'vps_n8n_telegram_wow_digest' | grep -v '^CRON_TZ=' || true
  echo 'CRON_TZ=Europe/Warsaw'
  echo "$CRON_LINE"
} >"$TMP"
crontab "$TMP"
rm -f "$TMP"

echo "OK: crontab installed. Digest at 08:00 Europe/Warsaw -> $LOG_FILE"

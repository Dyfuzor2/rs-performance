#!/bin/bash
# Restic backup of /srv/workspaces and /srv/ops-stack. Keep 7 daily snapshots.
# Dyrektywa: backup VPS w gotowoťci. Cron: np. 0 3 * * * (daily 03:00)
set -e
export RESTIC_REPOSITORY=/srv/backups/restic-repo
export RESTIC_PASSWORD_FILE=/srv/ops-stack/backups/restic/password.txt
restic backup /srv/workspaces /srv/ops-stack \
  --exclude=.git --exclude=node_modules --exclude=vendor \
  --exclude=storage/logs --exclude=*.log \
  --exclude=/srv/ops-stack/backups/restore-smoke \
  --exclude=/srv/backups/restic-repo
restic forget --keep-daily 7 --prune
echo "$(date -Iseconds) restic backup ok" >> /srv/ops-stack/backups/restic/restic.log

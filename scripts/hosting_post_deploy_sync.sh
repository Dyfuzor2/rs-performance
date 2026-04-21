#!/usr/bin/env bash
# Hosting post-deploy: regenerate AI discovery surfaces, sync Vite/Filament assets,
# and mirror llms* + selected /.well-known + EV feed to the AEO gateway vhost (Gap 2).
# Run on Cyber-Folks after deploy (same account as canonical). Optional:
#   export AI_AEO_PUBLIC_HTML=/home/USER/domains/ai.rsperformance.online/public_html
# If unset, defaults to sibling of canonical: ../ai.rsperformance.online/public_html
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WEB_ROOT="$(cd "${APP_ROOT}/.." && pwd)/public_html"
DOMAINS_ROOT="$(dirname "$(dirname "${APP_ROOT}")")"
AI_AEO_PUBLIC_HTML="${AI_AEO_PUBLIC_HTML:-${DOMAINS_ROOT}/ai.rsperformance.online/public_html}"

cd "${APP_ROOT}"
php85 artisan search:artifacts-generate --no-interaction

sync_dir() {
    local source_dir="$1"
    local target_dir="$2"

    if [ -d "${source_dir}" ]; then
        mkdir -p "${target_dir}"
        rsync -a --delete "${source_dir}/" "${target_dir}/"
    fi
}

# Copy whitelisted machine-readable discovery files from Laravel public/ → a document root.
# Does not copy full public/ (avoids clobbering host-specific symlinks or legacy files).
sync_discovery_whitelist() {
    local src_root="$1"
    local dst_root="$2"

    if [ ! -d "${src_root}" ] || [ ! -d "${dst_root}" ]; then
        return 0
    fi

    local f
    for f in llms.txt llms-full.txt; do
        if [ -f "${src_root}/${f}" ]; then
            cp -a "${src_root}/${f}" "${dst_root}/${f}"
        fi
    done

    if [ -d "${src_root}/.well-known" ]; then
        mkdir -p "${dst_root}/.well-known"
        for f in llms.txt llms-full.txt ai-resources.json; do
            if [ -f "${src_root}/.well-known/${f}" ]; then
                cp -a "${src_root}/.well-known/${f}" "${dst_root}/.well-known/${f}"
            fi
        done
    fi

    if [ -f "${src_root}/feeds/ev-hybrid.json" ]; then
        mkdir -p "${dst_root}/feeds"
        cp -a "${src_root}/feeds/ev-hybrid.json" "${dst_root}/feeds/ev-hybrid.json"
    fi
}

sync_dir "${APP_ROOT}/public/build" "${WEB_ROOT}/build"
sync_dir "${APP_ROOT}/public/css/filament" "${WEB_ROOT}/css/filament"
sync_dir "${APP_ROOT}/public/js/filament" "${WEB_ROOT}/js/filament"
sync_dir "${APP_ROOT}/public/fonts/filament" "${WEB_ROOT}/fonts/filament"

echo "Synced build and Filament assets to ${WEB_ROOT}"

PUBLIC_ROOT="${APP_ROOT}/public"
sync_discovery_whitelist "${PUBLIC_ROOT}" "${WEB_ROOT}"
echo "Synced discovery whitelist (llms*, .well-known/llms*, ai-resources, feeds/ev-hybrid) to canonical ${WEB_ROOT}"

if [ -d "${AI_AEO_PUBLIC_HTML}" ]; then
    sync_discovery_whitelist "${PUBLIC_ROOT}" "${AI_AEO_PUBLIC_HTML}"
    echo "Synced discovery whitelist to AEO gateway ${AI_AEO_PUBLIC_HTML}"
else
    echo "Skip AEO gateway sync (not found): ${AI_AEO_PUBLIC_HTML} — set AI_AEO_PUBLIC_HTML if your vhost path differs"
fi

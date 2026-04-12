#!/usr/bin/env python3
"""
April 2026+ n8n repairs (RS Performance):
- F6uosr6xSCJZM4fO: sequential HTTP chain so Build Report runs after all pings (fixes  'Node Google Sitemap Ping hasn't been executed'); clean UTF-8 Telegram report text.
- W1xRg73xFDUXYrRI: Notify Diagnosta JSON body via JSON.stringify (valid JSON for HttpRequest v4.2).

Also use: `scripts/n8n_monitor_diagnosta_code_node.py` (Code node + Recepcja), `scripts/n8n_apr2026_fleet_http_jsonfix.py` (full-fleet HttpRequest jsonBody).

Requires: .cursor/mcp.env with N8N_API_URL, N8N_API_KEY
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

INVITATION_HUB_ID = "F6uosr6xSCJZM4fO"
MONITOR_ID = "W1xRg73xFDUXYrRI"

BUILD_REPORT_JS = r"""// Aggregate results from all channels (runs after all HTTP nodes — sequential graph).
const collectData = $('Collect URLs').first().json;

function statusFrom(nodeName) {
  try {
    const row = $(nodeName).first();
    const j = row.json || {};
    return j.statusCode ?? j.status ?? j.code ?? 'unknown';
  } catch {
    return 'unknown';
  }
}

const indexNowStatus = statusFrom('IndexNow Ping');
const googleStatus = statusFrom('Google Sitemap Ping');
const bingStatus = statusFrom('Bing Sitemap Ping');
const healthStatus = statusFrom('Health Check');
const llmsStatus = statusFrom('llms.txt Check');

const warsawTime = new Date().toLocaleString('pl-PL', { timeZone: 'Europe/Warsaw' });

const ok = (s) => s === 200 || s === 202;
const googlePingOk = (s) => ok(s) || s === 404;
const line = (label, s, acceptFn = ok) => `  ${label}: ${acceptFn(s) ? 'OK' : 'FAIL'} (${s})`;

const report = [
  '*AI Bot Invitation Hub — Raport*',
  `Czas: ${warsawTime}`,
  '',
  '*Kanały invitation:*',
  line('IndexNow', indexNowStatus),
  line('Google Ping', googleStatus, googlePingOk),
  line('Bing Ping', bingStatus),
  '',
  '*Health:*',
  line('Homepage', healthStatus),
  line('llms.txt', llmsStatus),
  '',
  `URLs zaproszonych: ${collectData.totalUrls}`,
  '',
  '#BotInvitationHub #AEO',
].join('\n');

const allOk = ok(indexNowStatus) && googlePingOk(googleStatus) && ok(bingStatus) && healthStatus === 200;

return {
  json: {
    report,
    allOk,
    indexNowStatus,
    googleStatus,
    bingStatus,
    healthStatus,
    llmsStatus,
    urlCount: collectData.totalUrls,
    timestamp: collectData.timestamp,
  },
};
"""

SEQUENTIAL_CONNECTIONS = {
    "Every Hour": {"main": [[{"node": "Collect URLs", "type": "main", "index": 0}]]},
    "Collect URLs": {"main": [[{"node": "IndexNow Ping", "type": "main", "index": 0}]]},
    "IndexNow Ping": {"main": [[{"node": "Google Sitemap Ping", "type": "main", "index": 0}]]},
    "Google Sitemap Ping": {"main": [[{"node": "Bing Sitemap Ping", "type": "main", "index": 0}]]},
    "Bing Sitemap Ping": {"main": [[{"node": "Health Check", "type": "main", "index": 0}]]},
    "Health Check": {"main": [[{"node": "llms.txt Check", "type": "main", "index": 0}]]},
    "llms.txt Check": {"main": [[{"node": "Build Report", "type": "main", "index": 0}]]},
    "Build Report": {"main": [[{"node": "Telegram Report", "type": "main", "index": 0}]]},
    "Test Webhook": {"main": [[{"node": "Collect URLs", "type": "main", "index": 0}]]},
}

NOTIFY_DIAGNOSTA_JSON_BODY = (
    "={{ ({ "
    "text: 'INCIDENT: ' + ($json.failedServices || []).join(', ') + ' down at ' + new Date().toISOString(), "
    "source: 'agent-monitor', "
    "category: 'incident' "
    "}) }}"
)


def put_workflow(base: str, headers: dict, wf: dict) -> bool:
    wid = wf["id"]
    payload = {
        "name": wf["name"],
        "nodes": wf["nodes"],
        "connections": wf["connections"],
        "settings": wf.get("settings", {}),
        "staticData": wf.get("staticData"),
    }
    r = requests.put(f"{base}/api/v1/workflows/{wid}", headers=headers, json=payload, timeout=120)
    if not r.ok:
        print(f"PUT {wid} failed: {r.status_code} {r.text[:500]}")
        return False
    print(f"PUT {wid} OK ({wf['name']})")
    return True


def repair_invitation_hub(base: str, headers: dict) -> bool:
    r = requests.get(f"{base}/api/v1/workflows/{INVITATION_HUB_ID}", headers=headers, timeout=60)
    r.raise_for_status()
    wf = r.json()
    wf["connections"] = SEQUENTIAL_CONNECTIONS
    for node in wf["nodes"]:
        if node.get("name") == "Build Report":
            node.setdefault("parameters", {})["jsCode"] = BUILD_REPORT_JS
            break
    else:
        print("Build Report node not found")
        return False
    return put_workflow(base, headers, wf)


def repair_monitor(base: str, headers: dict) -> bool:
    r = requests.get(f"{base}/api/v1/workflows/{MONITOR_ID}", headers=headers, timeout=60)
    r.raise_for_status()
    wf = r.json()
    changed = False
    for node in wf["nodes"]:
        if node.get("name") == "Notify Diagnosta":
            old = (node.get("parameters") or {}).get("jsonBody")
            if old != NOTIFY_DIAGNOSTA_JSON_BODY:
                node.setdefault("parameters", {})["jsonBody"] = NOTIFY_DIAGNOSTA_JSON_BODY
                changed = True
            break
    else:
        print("Notify Diagnosta node not found")
        return False
    if not changed:
        print("Monitor: jsonBody already repaired")
        return True
    return put_workflow(base, headers, wf)


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key, "Content-Type": "application/json"}
    ok = True
    ok = repair_invitation_hub(base, headers) and ok
    ok = repair_monitor(base, headers) and ok
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())

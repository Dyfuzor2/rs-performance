#!/usr/bin/env python3
"""Patch known-broken n8n workflows (Invitation Hub Build Report, Content Extract Topic)."""
from __future__ import annotations

import json
import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

INVITATION_HUB_ID = "F6uosr6xSCJZM4fO"
CONTENT_AGENT_ID = "9Kvb4S6vmp4MJJyq"

BUILD_REPORT_JS = r"""// Aggregate results from all channels (sequential graph). April 2026+ safe node access.
function statusFrom(nodeName) {
  try {
    const items = $(nodeName).all();
    if (!items || items.length === 0) {
      return 'unknown';
    }
    const j = items[0].json || {};
    return j.statusCode ?? j.status ?? j.code ?? 'unknown';
  } catch {
    return 'unknown';
  }
}

const collectData = $('Collect URLs').first().json;

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

EXTRACT_TOPIC_JS = r"""const response = $('Generate Topic').first().json;
let content = '';
const ch = response && response.choices && response.choices[0] && response.choices[0].message
  ? response.choices[0].message.content
  : '';
content = typeof ch === 'string' ? ch : '';

if (content && (content.startsWith('The user') || content.startsWith('We need') || content.startsWith('Okay'))) {
  const lines = content.trim().split('\n').filter((l) => l.trim());
  content = lines[lines.length - 1];
}

content = content.replace(/^["']|["']$/g, '').trim();

const topic = content || 'Diagnostyka komputerowa - kiedy jest niezbedna';
return [{ json: { topic } }];
"""


def put_wf(base: str, headers: dict, wf: dict) -> bool:
    wid = wf["id"]
    payload = {
        "name": wf["name"],
        "nodes": wf["nodes"],
        "connections": wf["connections"],
        "settings": wf.get("settings") or {},
        "staticData": wf.get("staticData"),
    }
    r = requests.put(f"{base}/api/v1/workflows/{wid}", headers=headers, json=payload, timeout=120)
    if not r.ok:
        print(f"PUT {wid} failed: {r.status_code} {r.text[:500]}")
        return False
    print(f"PUT OK: {wf.get('name')}")
    return True


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key, "Content-Type": "application/json"}

    for wid, label in [(INVITATION_HUB_ID, "hub"), (CONTENT_AGENT_ID, "content")]:
        r = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=60)
        if not r.ok:
            print(f"GET {wid}: {r.status_code}")
            continue
        wf = r.json()
        changed = False
        for n in wf.get("nodes") or []:
            name = n.get("name") or ""
            if wid == INVITATION_HUB_ID and name == "Build Report":
                n.setdefault("parameters", {})["jsCode"] = BUILD_REPORT_JS
                changed = True
                print("patched Build Report (Invitation Hub)")
            if wid == CONTENT_AGENT_ID and name == "Extract Topic":
                n.setdefault("parameters", {})["jsCode"] = EXTRACT_TOPIC_JS
                changed = True
                print("patched Extract Topic (Content)")
        if changed:
            put_wf(base, headers, wf)
        else:
            print(f"{label}: no matching nodes")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

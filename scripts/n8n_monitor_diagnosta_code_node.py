#!/usr/bin/env python3
"""
April 2026+ — RS AI Agent Monitor: insert Code node before Notify Diagnosta so HttpRequest
receives plain JSON via JSON.stringify($json) (no parse errors on expression-in-json field).

Requires: .cursor/mcp.env (N8N_API_URL, N8N_API_KEY)
"""

from __future__ import annotations

import sys
import uuid
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

MONITOR_ID = "W1xRg73xFDUXYrRI"
CODE_NODE_NAME = "Prepare Diagnosta Body"
PREPARE_JS = r"""const failed = $json.failedServices || [];
const text =
  'INCIDENT: ' +
  failed.join(', ') +
  ' down at ' +
  new Date().toISOString();
return {
  json: {
    text,
    source: 'agent-monitor',
    category: 'incident',
  },
};
"""

# After Code node, body is a plain object — stringify for HttpRequest json field.
NOTIFY_JSON_BODY = "={{ JSON.stringify($json) }}"

NOTIFY_RECEPCJA_JSON_BODY = (
    "={{ JSON.stringify({ "
    "message: 'SYSTEM_ALERT: Down: ' + ($json.failedServices || []).join(', '), "
    "source: 'agent-monitor', "
    "internal: true "
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
        print(f"PUT failed: {r.status_code} {r.text[:800]}")
        return False
    print(f"PUT OK: {wf['name']}")
    return True


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key, "Content-Type": "application/json"}
    r = requests.get(f"{base}/api/v1/workflows/{MONITOR_ID}", headers=headers, timeout=60)
    r.raise_for_status()
    wf = r.json()

    nodes = wf.get("nodes") or []
    if any(n.get("name") == CODE_NODE_NAME for n in nodes):
        print(f"{CODE_NODE_NAME}: already present — only syncing Notify Diagnosta jsonBody if needed")
    else:
        code_id = str(uuid.uuid4())
        notify = next((n for n in nodes if n.get("name") == "Notify Diagnosta"), None)
        pos = (notify or {}).get("position") or [1060, 300]
        code_node = {
            "parameters": {
                "mode": "runOnceForAllItems",
                "language": "javaScript",
                "jsCode": PREPARE_JS,
            },
            "id": code_id,
            "name": CODE_NODE_NAME,
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [pos[0] - 180, pos[1]],
        }
        nodes.append(code_node)
        wf["nodes"] = nodes

    conns = wf.get("connections") or {}
    ff = conns.get("Filter Failures", {}).get("main") or [[]]
    if ff and ff[0]:
        new_first = []
        for c in ff[0]:
            if c.get("node") == "Notify Diagnosta":
                new_first.append(
                    {"node": CODE_NODE_NAME, "type": "main", "index": 0},
                )
            else:
                new_first.append(c)
        conns["Filter Failures"] = {"main": [new_first]}
    conns[CODE_NODE_NAME] = {
        "main": [[{"node": "Notify Diagnosta", "type": "main", "index": 0}]],
    }
    wf["connections"] = conns

    for node in wf["nodes"]:
        if node.get("name") == "Notify Diagnosta":
            node.setdefault("parameters", {})["jsonBody"] = NOTIFY_JSON_BODY
        if node.get("name") == "Notify Recepcja":
            node.setdefault("parameters", {})["jsonBody"] = NOTIFY_RECEPCJA_JSON_BODY

    return 0 if put_workflow(base, headers, wf) else 1


if __name__ == "__main__":
    raise SystemExit(main())

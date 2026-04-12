#!/usr/bin/env python3
"""
April 2026+ — dopasowanie modeli do kosztu i roli (n8n na VPS: auto.rs3d.pl).

Polityka (skrót):
- Lekkie automaty (DTC, trending, recepcja, enrichment): gemini-2.5-flash-lite — już w workflowach.
- Editorial Board: generacja = gemini-2.5-flash (tania); metadane persist muszą się zgadzać.

Ten skrypt:
- Naprawia RS Editorial Board: spójna nazwa węzła + used_models w persist.

Wymaga: .cursor/mcp.env — N8N_API_URL, N8N_API_KEY (instancja n8n na VPS).

Źródło prawdy dla blog pipeline (modele Laravel): config/blog.php (deploy na hosting).

Usage:
  python scripts/n8n_apr2026_model_cost_routing.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

EDITORIAL_BOARD_ID = "vktlhlLUVolWBxRs"

# Jeden węzeł generacji — trzymamy Flash (koszt); jakość treści dokładamy pipeline na hostingu (2.5 Pro itd.).
EDITORIAL_WRITE_MODEL = "gemini-2.5-flash"


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


def repair_editorial_board(base: str, headers: dict) -> bool:
    r = requests.get(f"{base}/api/v1/workflows/{EDITORIAL_BOARD_ID}", headers=headers, timeout=60)
    r.raise_for_status()
    wf = r.json()
    changed = False
    for node in wf["nodes"]:
        n = node.get("name") or ""
        params = node.setdefault("parameters", {})

        if n == "Gemini 2.5 Pro Write":
            node["name"] = "Gemini 2.5 Flash — Editorial Draft"
            changed = True
            n = node["name"]

        url_s = str(params.get("url") or "")
        if "generate-content" in url_s:
            body = str(params.get("jsonBody") or "")
            if f"model: '{EDITORIAL_WRITE_MODEL}'" not in body and "model:" in body:
                new_body, nsub = re.subn(
                    r"model:\s*'gemini-[^']+'",
                    f"model: '{EDITORIAL_WRITE_MODEL}'",
                    body,
                    count=1,
                )
                if nsub:
                    params["jsonBody"] = new_body
                    changed = True

        if n == "Persist to Blog":
            body = str(params.get("jsonBody") or "")
            target = "['gemini-2.5-pro']"
            fix = "['gemini-2.5-flash']"
            if target in body:
                params["jsonBody"] = body.replace(target, fix, 1)
                changed = True

    if not changed:
        print("Editorial Board: już zsynchronizowane (koszt / metadane).")
        return True
    return put_workflow(base, headers, wf)


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key, "Content-Type": "application/json"}
    ok = repair_editorial_board(base, headers)
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())

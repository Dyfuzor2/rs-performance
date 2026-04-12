#!/usr/bin/env python3
"""On VPS: last error summary per workflow (recent failed executions)."""
from __future__ import annotations

import json
import sqlite3
import zlib

DB = "/srv/ops-stack/n8n/storage/database.sqlite"


def decompress_blob(blob: bytes | str) -> dict | list | None:
    if blob is None:
        return None
    if isinstance(blob, str):
        raw = blob.encode("utf-8")
    else:
        raw = blob
    try:
        raw = zlib.decompress(raw, 16 + zlib.MAX_WBITS)
    except (zlib.error, TypeError):
        pass
    try:
        out = json.loads(raw.decode("utf-8", errors="replace"))
    except json.JSONDecodeError:
        return None
    return out


def extract_error_message(obj: dict | list | None) -> str:
    if obj is None:
        return "(no parse)"
    if isinstance(obj, list):
        for item in obj:
            if isinstance(item, dict) and item.get("error"):
                return str(item["error"])[:800]
        return json.dumps(obj, ensure_ascii=False)[:800]
    rd = obj.get("resultData") or {}
    if not isinstance(rd, dict):
        rd = (obj.get("data") or {}).get("resultData") or {}
    err = rd.get("error") if isinstance(rd, dict) else None
    if isinstance(err, dict):
        msg = err.get("message") or err.get("description") or err
        return str(msg)[:800]
    if err:
        return str(err)[:800]
    run = rd.get("runData") if isinstance(rd, dict) else None
    if isinstance(run, dict):
        for _nid, runs in run.items():
            if not isinstance(runs, list):
                continue
            for r in runs:
                if not isinstance(r, dict):
                    continue
                e = r.get("error")
                if e:
                    return str(e.get("message") if isinstance(e, dict) else e)[:800]
    return json.dumps(obj, ensure_ascii=False)[:600]


def main() -> None:
    con = sqlite3.connect(DB)
    con.row_factory = sqlite3.Row
    # Latest error execution per workflow
    rows = con.execute(
        """
        SELECT e.id AS eid, e.workflowId AS wid, w.name AS wname,
               e.status, d.data AS blob
        FROM execution_entity e
        JOIN workflow_entity w ON w.id = e.workflowId
        LEFT JOIN execution_data d ON d.executionId = e.id
        WHERE e.status = 'error'
          AND e.id = (
            SELECT MAX(e2.id) FROM execution_entity e2
            WHERE e2.workflowId = e.workflowId AND e2.status = 'error'
          )
        ORDER BY w.active DESC, w.name COLLATE NOCASE
        """
    ).fetchall()
    for r in rows:
        obj = decompress_blob(r["blob"])
        msg = extract_error_message(obj)
        print(f"--- {r['wid']} | {r['wname'][:50]} | exec={r['eid']} ---")
        print(msg)
        print()
    con.close()


if __name__ == "__main__":
    main()

#!/usr/bin/env python3
"""Dump execution JSON: python3 _vps_n8n_dump_exec.py <executionId>"""
import json
import sqlite3
import sys
import zlib

DB = "/srv/ops-stack/n8n/storage/database.sqlite"


def main() -> None:
    if len(sys.argv) < 2:
        print("usage: _vps_n8n_dump_exec.py <executionId>", file=sys.stderr)
        sys.exit(1)
    eid = int(sys.argv[1])
    con = sqlite3.connect(DB)
    row = con.execute(
        "SELECT data FROM execution_data WHERE executionId = ?", (eid,)
    ).fetchone()
    con.close()
    if not row or row[0] is None:
        print("no data")
        return
    b = row[0]
    if isinstance(b, str):
        b = b.encode("utf-8")
    try:
        b = zlib.decompress(b, 16 + zlib.MAX_WBITS)
    except (zlib.error, TypeError):
        pass
    print(b.decode("utf-8", errors="replace")[:12000])


if __name__ == "__main__":
    main()

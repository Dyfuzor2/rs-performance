#!/usr/bin/env python3
"""
Mirror ai-gateway /.well-known/openapi.json -> openapi.yaml on the VPS support plane.

April 2026+: many OpenAPI / agent clients prefer a .yaml service descriptor URL; this keeps
parity with the JSON contract without maintaining two hand-authored specs.

Run after sync or whenever openapi.json is edited:
  python3 /srv/ai-gateway/bin/mirror_openapi_yaml.py

Requires: PyYAML (same venv as other gateway tooling if needed: pip install pyyaml).
"""
from __future__ import annotations

import argparse
import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--json",
        type=Path,
        default=Path("/srv/ai-gateway/.well-known/openapi.json"),
        help="Path to gateway OpenAPI JSON",
    )
    parser.add_argument(
        "--yaml",
        type=Path,
        default=Path("/srv/ai-gateway/.well-known/openapi.yaml"),
        help="Path to write OpenAPI YAML mirror",
    )
    parser.add_argument(
        "--no-backup",
        action="store_true",
        help="Skip writing .bak_<utc> backup of existing YAML",
    )
    args = parser.parse_args()

    try:
        import yaml
    except ImportError:
        print("mirror_openapi_yaml: PyYAML is required (pip install pyyaml)", file=sys.stderr)
        return 2

    if not args.json.is_file():
        print(f"mirror_openapi_yaml: missing {args.json}", file=sys.stderr)
        return 1

    data = json.loads(args.json.read_text(encoding="utf-8"))
    text = yaml.dump(data, sort_keys=False, allow_unicode=True)

    if args.yaml.is_file() and not args.no_backup:
        suffix = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        bak = args.yaml.with_name(args.yaml.name + ".bak_mirror_" + suffix)
        shutil.copy2(args.yaml, bak)
        print(f"mirror_openapi_yaml: backup -> {bak}")

    args.yaml.parent.mkdir(parents=True, exist_ok=True)
    args.yaml.write_text(text, encoding="utf-8")
    print(f"mirror_openapi_yaml: wrote {args.yaml} ({len(text)} bytes)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

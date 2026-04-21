#!/usr/bin/env python3
"""Smoke: canonical AEO discovery exposes gateway OpenAPI YAML (April 2026+).

Uses only the standard library (no deps). Exit 0 on success, 1 on failure.

Examples:
  python scripts/smoke_aeo_gateway_openapi_canonical.py
  python scripts/smoke_aeo_gateway_openapi_canonical.py --base https://rsperformance.online
"""

from __future__ import annotations

import argparse
import json
import sys
import urllib.error
import urllib.request
from urllib.parse import urljoin


def _fetch(url: str, *, method: str = "GET", timeout: float = 25.0) -> tuple[int, dict[str, str], bytes]:
    req = urllib.request.Request(url, method=method)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            code = int(getattr(resp, "status", resp.getcode()))
            headers = {k.lower(): v for k, v in resp.headers.items()}
            body = resp.read()
            return code, headers, body
    except urllib.error.HTTPError as e:
        body = e.read() if hasattr(e, "read") else b""
        hdrs = {k.lower(): v for k, v in (e.headers.items() if e.headers else [])}
        return int(e.code), hdrs, body


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument(
        "--base",
        default="https://rsperformance.online",
        help="Canonical origin (default: production)",
    )
    args = p.parse_args()
    base = args.base.rstrip("/")
    ai_res = urljoin(base + "/", ".well-known/ai-resources.json")

    print(f"[smoke] GET {ai_res}")
    code, _, body = _fetch(ai_res)
    if code != 200:
        print(f"FAIL: ai-resources.json HTTP {code}", file=sys.stderr)
        return 1
    try:
        data = json.loads(body.decode("utf-8", errors="replace"))
    except json.JSONDecodeError as e:
        print(f"FAIL: invalid JSON: {e}", file=sys.stderr)
        return 1
    gw = data.get("gateway") if isinstance(data, dict) else None
    yaml_url = gw.get("openapi_yaml") if isinstance(gw, dict) else None
    if not yaml_url or not isinstance(yaml_url, str):
        print("FAIL: missing gateway.openapi_yaml in ai-resources.json", file=sys.stderr)
        return 1
    if not yaml_url.endswith("/.well-known/openapi.yaml"):
        print(f"FAIL: unexpected openapi_yaml URL: {yaml_url!r}", file=sys.stderr)
        return 1
    print(f"[smoke] gateway.openapi_yaml OK -> {yaml_url}")

    print(f"[smoke] HEAD {base}/")
    hcode, hheaders, _ = _fetch(base + "/", method="HEAD")
    if hcode != 200:
        print(f"FAIL: homepage HEAD HTTP {hcode}", file=sys.stderr)
        return 1
    link = (hheaders.get("link") or "").lower()
    if "openapi.yaml" not in link:
        print("FAIL: homepage Link header missing openapi.yaml reference", file=sys.stderr)
        return 1
    print("[smoke] homepage Link contains openapi.yaml - OK")
    print("[smoke] all checks passed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

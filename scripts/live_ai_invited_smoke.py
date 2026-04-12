#!/usr/bin/env python3
"""Live smoke: HTTP probe for every token in config/ai_agents.php tracked_user_agents.

Uses the same config as production when run from repo root; hits live rsperformance.online
with X-RS-Synthetic-Probe so TrackAiAgentTraffic can skip persistence.

urllib follows redirects (similar to curl -L): final status is reported.

Env:
  SMOKE_BASE   default https://rsperformance.online
  SMOKE_PATH   default /kody-usterek/p0299
  SMOKE_SLEEP  seconds between requests (default 0.15)
  PHP_BINARY   php executable (default php)
  SMOKE_ALLOW_GOOGLEBOT_403=1  treat Googlebot HTTP 403 as WARN+pass (host WAF may block
 this UA outside Google IP ranges; default is FAIL so misconfiguration is visible)
"""
from __future__ import annotations

import os
import subprocess
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get("SMOKE_BASE", "https://rsperformance.online").rstrip("/")
PATH = os.environ.get("SMOKE_PATH", "/kody-usterek/p0299")
SLEEP = float(os.environ.get("SMOKE_SLEEP", "0.15"))
PHP_BIN = os.environ.get("PHP_BINARY", "php")
ALLOW_GOOGLEBOT_403 = os.environ.get("SMOKE_ALLOW_GOOGLEBOT_403", "") == "1"

OK_CODES = frozenset({200, 201, 204, 301, 302, 303, 307, 308})

# Short synthetic UAs containing only the token trip ModSec / host WAF for major indexers.
REALISTIC_USER_AGENTS: dict[str, str] = {
    # Match public Googlebot documentation; extra tokens can trip host WAF rules.
    "Googlebot": (
        "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
    ),
    "Bingbot": "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)",
}


def load_tokens() -> list[str]:
    code = r'$c = require "config/ai_agents.php"; foreach ($c["tracked_user_agents"] as $t) { echo $t, PHP_EOL; }'
    r = subprocess.run(
        [PHP_BIN, "-r", code],
        cwd=ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    if r.returncode != 0:
        msg = (r.stderr or r.stdout or "").strip()
        raise RuntimeError(f"php token load failed ({r.returncode}): {msg}")
    return [ln.strip() for ln in r.stdout.splitlines() if ln.strip()]


def check_token(tok: str) -> str:
    if tok in REALISTIC_USER_AGENTS:
        ua = REALISTIC_USER_AGENTS[tok]
    else:
        ua = f"Mozilla/5.0 (compatible; RS-Live-Smoke/2026; {tok}; +https://rsperformance.online/)"
    url = f"{BASE}{PATH}"
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": ua,
            "X-RS-Synthetic-Probe": "1",
            "Accept": "text/html,application/xhtml+xml;q=0.9,*/*;q=0.8",
        },
        method="GET",
    )
    try:
        with urllib.request.urlopen(req, timeout=25) as resp:
            return str(resp.getcode())
    except urllib.error.HTTPError as e:
        return str(e.code)
    except urllib.error.URLError as e:
        return f"ERR:{e.reason!s}"


def main() -> int:
    print(f"=== AI invited smoke: {BASE}{PATH} (tracked_user_agents) ===", flush=True)
    tokens = load_tokens()
    ok = fail = warn = 0
    for tok in tokens:
        code_s = check_token(tok)
        if code_s.startswith("ERR:"):
            print(f"FAIL {tok!r} -> {code_s}", flush=True)
            fail += 1
        else:
            code = int(code_s)
            if code in OK_CODES:
                print(f"OK   {tok!r} -> HTTP {code}", flush=True)
                ok += 1
            elif tok == "Googlebot" and code == 403 and ALLOW_GOOGLEBOT_403:
                print(
                    "WARN 'Googlebot' -> HTTP 403 "
                    "(SMOKE_ALLOW_GOOGLEBOT_403=1; review host WAF / ModSec for real Googlebot)",
                    flush=True,
                )
                warn += 1
                ok += 1
            else:
                print(f"FAIL {tok!r} -> HTTP {code}", flush=True)
                fail += 1
        time.sleep(SLEEP)
    print("---", flush=True)
    print(f"SUMMARY OK={ok} FAIL={fail} WARN={warn} TOTAL={len(tokens)}", flush=True)
    if fail == 0 and warn:
        print(
            "NOTE: Googlebot returned 403; confirm whether edge blocks non-Google IPs (SEO risk).",
            flush=True,
        )
    return 0 if fail == 0 else 1


if __name__ == "__main__":
    try:
        sys.exit(main())
    except RuntimeError as e:
        print(f"ERROR: {e}", file=sys.stderr, flush=True)
        sys.exit(2)

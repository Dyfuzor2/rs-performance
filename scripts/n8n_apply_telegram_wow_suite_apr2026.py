#!/usr/bin/env python3
"""
April 2026+ — **one-shot operator suite**: apply all RS n8n **Telegram WOW** patches (Apr 2026 family).

Runs these scripts in order (each supports ``--dry-run`` and ``--vps-jwt``):

1. ``n8n_apply_research_harvester_telegram_wow_apr2026.py`` — RS Research Harvester
2. ``n8n_apply_dtc_enrichment_telegram_wow_apr2026.py`` — RS DTC Enrichment Engine
3. ``n8n_apply_indexnow_drip_telegram_wow_apr2026.py`` — DTC IndexNow Drip

Forwarded flags: ``--dry-run``, ``--vps-jwt``, ``--base-url``.

Exit code: **0** only if every step returns 0; otherwise **1**. With ``--continue-on-error``,
remaining scripts still run after a failure, but the process still exits **1** if any step failed.

Examples::

    python scripts/n8n_apply_telegram_wow_suite_apr2026.py --dry-run
    python scripts/n8n_apply_telegram_wow_suite_apr2026.py --vps-jwt --dry-run
    python scripts/n8n_apply_telegram_wow_suite_apr2026.py --vps-jwt
    python scripts/n8n_apply_telegram_wow_suite_apr2026.py --vps-jwt --json

At startup the suite prints **one preflight line** (resolved ``N8N_API_URL`` or JWT-mode base)
and **stderr WARN** when the URL likely is not the RS VPS production n8n (wrong instance → **404** on workflow ids).
"""

from __future__ import annotations

import argparse
import json
import os
import subprocess
import sys
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class Step:
    script: str
    title: str


STEPS: tuple[Step, ...] = (
    Step("scripts/n8n_apply_research_harvester_telegram_wow_apr2026.py", "Research Harvester Telegram"),
    Step("scripts/n8n_apply_dtc_enrichment_telegram_wow_apr2026.py", "DTC Enrichment Telegram"),
    Step("scripts/n8n_apply_indexnow_drip_telegram_wow_apr2026.py", "DTC IndexNow Drip Telegram"),
)


def _print_suite_n8n_target(*, base_url_override: str, vps_jwt: bool) -> None:
    """Help operators spot wrong ``N8N_API_URL`` before three child 404s."""
    root = Path(__file__).resolve().parents[1]
    if str(root) not in sys.path:
        sys.path.insert(0, str(root))

    from gravity_cursor_env import load_cursor_env  # noqa: E402

    load_cursor_env()
    override = (base_url_override or "").strip()
    if vps_jwt:
        base = (override or os.environ.get("N8N_API_URL") or "https://auto.rs3d.pl").rstrip("/")
        print(f"[suite] n8n base (JWT from VPS): {base}")
        if "socmid" in base.lower():
            print(
                "[suite] WARN: URL looks like SOCmid; VPS JWT is for VPS n8n — use "
                "`--base-url https://auto.rs3d.pl` if workflows are missing.",
                file=sys.stderr,
            )
        return

    from gravity_cursor_env import resolve_n8n_api  # noqa: E402

    try:
        url, _ = resolve_n8n_api(base_url=override or None)
    except RuntimeError as exc:
        print(f"[suite] WARN: {exc}", file=sys.stderr)
        return
    print(f"[suite] n8n base (env): {url}")
    u = url.rstrip("/").lower()
    if "auto.rs3d.pl" not in u and "rs3d" not in u:
        print(
            "[suite] WARN: RS Telegram WOW workflow ids are on VPS n8n; HTTP 404 usually means "
            "wrong instance. Use `--vps-jwt` or set `N8N_API_URL=https://auto.rs3d.pl` in `.cursor/mcp.env`.",
            file=sys.stderr,
        )


def main() -> int:
    ap = argparse.ArgumentParser(description="Run all n8n Telegram WOW apply scripts (Apr 2026+)")
    ap.add_argument("--dry-run", action="store_true", help="Forward to each child script")
    ap.add_argument("--vps-jwt", action="store_true", help="Read n8n API key from VPS SQLite via vps_exec")
    ap.add_argument("--base-url", default="", help="Override N8N_API_URL for all children")
    ap.add_argument(
        "--continue-on-error",
        action="store_true",
        help="Run remaining steps after a failure (exit 1 if any failed)",
    )
    ap.add_argument("--json", action="store_true", help="Print one-line JSON summary to stdout at end")
    args = ap.parse_args()

    _print_suite_n8n_target(base_url_override=args.base_url, vps_jwt=args.vps_jwt)

    root = Path(__file__).resolve().parents[1]
    extra: list[str] = []
    if args.dry_run:
        extra.append("--dry-run")
    if args.vps_jwt:
        extra.append("--vps-jwt")
    if args.base_url.strip():
        extra.extend(["--base-url", args.base_url.strip()])

    results: list[dict[str, object]] = []
    any_fail = False
    all_fail = True

    for step in STEPS:
        script_path = root / step.script
        if not script_path.is_file():
            any_fail = True
            results.append({"step": step.title, "script": step.script, "ok": False, "error": "script missing"})
            if not args.continue_on_error:
                break
            continue

        cmd = [sys.executable, str(script_path), *extra]
        p = subprocess.run(cmd, cwd=str(root), capture_output=True, text=True, encoding="utf-8", errors="replace")
        ok = p.returncode == 0
        if ok:
            all_fail = False
        else:
            any_fail = True
        tail = ""
        if p.stderr:
            tail = p.stderr.strip()[-400:]
        elif p.stdout:
            tail = p.stdout.strip()[-400:]
        results.append(
            {
                "step": step.title,
                "script": step.script,
                "ok": ok,
                "exit_code": p.returncode,
                "tail": tail,
            }
        )
        print(f"[{'OK' if ok else 'FAIL'}] {step.title} ({step.script}) exit={p.returncode}")
        if not ok and tail:
            print(tail)
        if not ok and not args.continue_on_error:
            break

    if args.json:
        payload = {"ok": not any_fail, "any_fail": any_fail, "all_fail": all_fail and len(results) > 0, "steps": results}
        print("__SUITE_JSON__ " + json.dumps(payload, ensure_ascii=False))

    if any_fail:
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

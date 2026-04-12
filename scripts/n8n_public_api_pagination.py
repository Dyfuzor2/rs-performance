#!/usr/bin/env python3
"""
n8n Public API — GET /api/v1/workflows with cursor pagination (April 2026+).

Docs: https://docs.n8n.io/api/pagination/
"""

from __future__ import annotations

from typing import Any

import requests

DEFAULT_PAGE_LIMIT = 250


def fetch_all_workflow_list_items(
    base: str,
    headers: dict[str, str],
    *,
    timeout: float = 120.0,
    limit: int = DEFAULT_PAGE_LIMIT,
) -> list[dict[str, Any]]:
    """
    Return every workflow summary object from the list endpoint (id, name, active, …).
    Handles { data, nextCursor } and legacy bare list responses.
    """
    base = base.rstrip("/")
    out: list[dict[str, Any]] = []
    cursor: str | None = None

    while True:
        params: dict[str, str | int] = {"limit": limit}
        if cursor:
            params["cursor"] = cursor

        r = requests.get(
            f"{base}/api/v1/workflows",
            headers=headers,
            params=params,
            timeout=timeout,
        )
        r.raise_for_status()
        payload = r.json()

        if isinstance(payload, list):
            out.extend(x for x in payload if isinstance(x, dict))
            break

        if not isinstance(payload, dict):
            break

        chunk = payload.get("data")
        if isinstance(chunk, list):
            out.extend(x for x in chunk if isinstance(x, dict))

        next_cursor = payload.get("nextCursor")
        if not next_cursor:
            break
        cursor = str(next_cursor)

    return out

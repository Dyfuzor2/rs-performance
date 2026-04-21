#!/usr/bin/env python3
"""SSH executor for Cyber-Folks server using paramiko."""
from __future__ import annotations

import os
import sys
import warnings
from pathlib import Path

import paramiko

warnings.filterwarnings("ignore")
sys.stdout.reconfigure(encoding="utf-8", errors="replace")
sys.stderr.reconfigure(encoding="utf-8", errors="replace")

HOST = os.environ.get("CYBERFOLKS_SSH_HOST", "s181.cyber-folks.pl")
PORT = int(os.environ.get("CYBERFOLKS_SSH_PORT", "222"))
USER = os.environ.get("CYBERFOLKS_SSH_USER", "tyurjydtpw")

_REPO_ROOT = Path(__file__).resolve().parent


def _load_cursor_env() -> None:
    """Merge `.cursor/mcp.env` into the process (same keys as other Gravity tools)."""
    scripts = _REPO_ROOT / "scripts"
    sp = str(scripts)
    if sp not in sys.path:
        sys.path.insert(0, sp)
    try:
        from gravity_cursor_env import load_cursor_env
    except ImportError:
        return
    load_cursor_env()


def _password_from_file(path: Path) -> str:
    text = path.read_text(encoding="utf-8", errors="replace")
    for raw in text.splitlines():
        line = raw.strip()
        if line and not line.startswith("#"):
            return line
    return ""


def _password() -> str:
    _load_cursor_env()
    p = (os.environ.get("CYBERFOLKS_SSH_PASSWORD") or "").strip()
    if p:
        return p

    file_hint = (os.environ.get("CYBERFOLKS_SSH_PASSWORD_FILE") or "").strip()
    candidate = Path(file_hint) if file_hint else _REPO_ROOT / "cs.txt"
    if candidate.is_file():
        p = _password_from_file(candidate).strip()
        if p:
            return p

    print(
        "ERROR: Set CYBERFOLKS_SSH_PASSWORD in .cursor/mcp.env, or put the password "
        "on the first non-comment line of cs.txt at the repo root, or set "
        "CYBERFOLKS_SSH_PASSWORD_FILE (see RELAY.md credentials map).",
        file=sys.stderr,
    )
    sys.exit(2)

def ssh_exec(cmd, timeout=None):
    """Execute command via SSH, return stdout."""
    if timeout is None:
        timeout = int(os.environ.get("SSH_EXEC_TIMEOUT", "120"))
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(HOST, port=PORT, username=USER, password=_password(), timeout=15)
        stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
        out = stdout.read().decode('utf-8', errors='replace')
        err = stderr.read().decode('utf-8', errors='replace')
        code = stdout.channel.recv_exit_status()
        if out:
            print(out, end='')
        if err:
            print(err, end='', file=sys.stderr)
        return code
    finally:
        client.close()

def ssh_upload(local_path, remote_path):
    """Upload file via SFTP."""
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(HOST, port=PORT, username=USER, password=_password(), timeout=15)
        sftp = client.open_sftp()
        sftp.put(local_path, remote_path)
        sftp.close()
        print(f"Uploaded {local_path} -> {remote_path}")
    finally:
        client.close()

def ssh_download(remote_path, local_path):
    """Download file via SFTP."""
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(HOST, port=PORT, username=USER, password=_password(), timeout=15)
        sftp = client.open_sftp()
        sftp.get(remote_path, local_path)
        sftp.close()
        print(f"Downloaded {remote_path} -> {local_path}")
    finally:
        client.close()

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python ssh_exec.py <command>")
        print("       python ssh_exec.py --upload <local> <remote>")
        print("       python ssh_exec.py --download <remote> <local>")
        sys.exit(1)

    if sys.argv[1] == "--upload":
        ssh_upload(sys.argv[2], sys.argv[3])
    elif sys.argv[1] == "--download":
        ssh_download(sys.argv[2], sys.argv[3])
    else:
        cmd = " ".join(sys.argv[1:])
        code = ssh_exec(cmd)
        sys.exit(code)

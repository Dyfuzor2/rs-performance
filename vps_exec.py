import warnings
warnings.filterwarnings("ignore", category=UserWarning)
warnings.filterwarnings("ignore", category=DeprecationWarning)
import sys
import os
import paramiko
sys.stdout.reconfigure(encoding="utf-8", errors="replace")
sys.stderr.reconfigure(encoding="utf-8", errors="replace")

HOST = "185.180.207.211"
PORT = 22
USER = "rsops"
KEY_PATH = os.path.expanduser("C:/Users/oli22/.ssh/cyberfolks_rsa")


def _vps_exec_raw(cmd: str, timeout: int):
    """Run command on VPS; returns (stdout, stderr, exit_code)."""
    if not os.path.isfile(KEY_PATH):
        return "", f"Key not found: {KEY_PATH}\n", 1
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(
            HOST, port=PORT, username=USER,
            key_filename=KEY_PATH,
            timeout=15,
            allow_agent=False,
            look_for_keys=False,
        )
        _stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
        out = stdout.read().decode("utf-8", errors="replace")
        err = stderr.read().decode("utf-8", errors="replace")
        code = stdout.channel.recv_exit_status()
        return out, err, code
    finally:
        client.close()


def vps_exec(cmd, timeout=None):
    """Execute command on VPS via SSH key auth. Returns exit code."""
    if timeout is None:
        timeout = int(os.environ.get("VPS_EXEC_TIMEOUT", "120"))
    out, err, code = _vps_exec_raw(cmd, timeout)
    if out:
        print(out, end="")
    if err:
        print(err, end="", file=sys.stderr)
    return code


def vps_exec_capture(cmd: str, timeout=None) -> tuple[str, str, int]:
    """
    Same as vps_exec but returns stdout/stderr without printing.
    Use for machine-readable output (e.g. gcloud JSON).
    """
    if timeout is None:
        timeout = int(os.environ.get("VPS_EXEC_TIMEOUT", "120"))
    return _vps_exec_raw(cmd, timeout)

def vps_upload(local_path, remote_path):
    """Upload file to VPS via SFTP (key auth)."""
    if not os.path.isfile(KEY_PATH):
        print(f"Key not found: {KEY_PATH}", file=sys.stderr)
        return 1
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(
            HOST, port=PORT, username=USER,
            key_filename=KEY_PATH,
            timeout=15,
            allow_agent=False,
            look_for_keys=False,
        )
        sftp = client.open_sftp()
        sftp.put(local_path, remote_path)
        sftp.close()
        print(f"Uploaded {local_path} -> {remote_path}")
        return 0
    finally:
        client.close()


def vps_backup_remote_path(remote_path, suffix=None):
    """
    Create backup on VPS: cp <path> <path>.bak_<suffix>.
    Run this BEFORE editing any file on VPS (dyrektywa nadrzędna).
    """
    suffix = suffix or "agent_backup"
    cmd = f"cp -- '{remote_path}' '{remote_path}.bak_{suffix}' 2>/dev/null && echo OK || echo FAIL"
    return vps_exec(cmd, timeout=None)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python vps_exec.py <command>")
        print("       python vps_exec.py --backup <remote_path> [suffix]")
        print("       python vps_exec.py --upload <local> <remote>")
        print("Example: python vps_exec.py \"ls /srv/workspaces\"")
        print("Example: python vps_exec.py --backup /srv/workspaces/rs-support-plane/.env my_change")
        sys.exit(1)

    if sys.argv[1] == "--backup":
        path = sys.argv[2] if len(sys.argv) > 2 else ""
        suffix = sys.argv[3] if len(sys.argv) > 3 else None
        if not path:
            print("--backup requires <remote_path>", file=sys.stderr)
            sys.exit(1)
        sys.exit(vps_backup_remote_path(path, suffix))
    elif sys.argv[1] == "--upload":
        local_path = sys.argv[2] if len(sys.argv) > 2 else ""
        remote_path = sys.argv[3] if len(sys.argv) > 3 else ""
        if not local_path or not remote_path:
            print("--upload requires <local> <remote>", file=sys.stderr)
            sys.exit(1)
        sys.exit(vps_upload(local_path, remote_path))
    else:
        cmd = " ".join(sys.argv[1:])
        sys.exit(vps_exec(cmd))

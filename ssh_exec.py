#!/usr/bin/env python3
"""SSH executor for Cyber-Folks server using paramiko."""
import sys
import os
import paramiko
import warnings
warnings.filterwarnings("ignore")
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
sys.stderr.reconfigure(encoding='utf-8', errors='replace')

HOST = os.environ.get("CYBERFOLKS_SSH_HOST", "s181.cyber-folks.pl")
PORT = int(os.environ.get("CYBERFOLKS_SSH_PORT", "222"))
USER = os.environ.get("CYBERFOLKS_SSH_USER", "tyurjydtpw")


def _password() -> str:
    p = os.environ.get("CYBERFOLKS_SSH_PASSWORD", "")
    if not p:
        print(
            "ERROR: Set CYBERFOLKS_SSH_PASSWORD (see start.md).",
            file=sys.stderr,
        )
        sys.exit(2)
    return p

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

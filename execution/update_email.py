import json
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
import sys

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import (  # noqa: E402
    require_facebook_page_id,
    require_facebook_page_update_token,
)

access_token = require_facebook_page_update_token()
page_id = require_facebook_page_id()

data = urllib.parse.urlencode(
    {
        "emails": '["biuro@rsperformance.online"]',
        "access_token": access_token,
    }
).encode("utf-8")

req = urllib.request.Request(
    f"https://graph.facebook.com/v19.0/{page_id}", data=data, method="POST"
)

try:
    with urllib.request.urlopen(req) as response:
        print(json.dumps(json.loads(response.read().decode()), indent=2))
except urllib.error.HTTPError as e:
    print("Error:", e.read().decode())

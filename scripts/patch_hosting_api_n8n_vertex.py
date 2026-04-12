#!/usr/bin/env python3
"""Usage on hosting: python3 /tmp/patch_hosting_api_n8n_vertex.py /home/.../laravel (Python 3.6+).

Idempotent: adds N8nVertexProxyController import + vertex routes after DTC enrichment-stats.
"""
import os
import sys
from pathlib import Path

root = Path(sys.argv[1]).resolve()
os.chdir(str(root))
api = root / "routes" / "api.php"
t = api.read_text(encoding="utf-8")
if "N8nVertexProxyController" in t:
    print("skip: already patched")
    sys.exit(0)
use = "use App\\Http\\Controllers\\Api\\N8nVertexProxyController;\n"
if "use App\\Http\\Controllers\\Api\\DtcEnrichmentController;" not in t:
    sys.stderr.write("err: no DtcEnrichment import\n")
    sys.exit(1)
t = t.replace(
    "use App\\Http\\Controllers\\Api\\DtcEnrichmentController;\n",
    "use App\\Http\\Controllers\\Api\\DtcEnrichmentController;\n" + use,
    1,
)
marker = "Route::get('/dtc/enrichment-stats', [DtcEnrichmentController::class, 'stats']);\n"
block = (
    marker
    + "\nRoute::post('/n8n/vertex/chat', [N8nVertexProxyController::class, 'chat']);\n"
    + "Route::post('/n8n/vertex/generate-content', [N8nVertexProxyController::class, 'generateContent']);\n"
)
if marker not in t:
    sys.stderr.write("err: marker not found\n")
    sys.exit(1)
t = t.replace(marker, block, 1)
api.write_text(t, encoding="utf-8")
print("ok:", api)

#!/usr/bin/env bash
# Live smoke: every tracked_user_agents token against canonical URL (run on hosting or any host with bash+curl+php85/php).
# Uses X-RS-Synthetic-Probe. Without curl -L: first-hop HTTP code only.
set -u

LARAVEL="${LARAVEL:-/home/tyurjydtpw/domains/rsperformance.online/laravel}"
BASE="${BASE:-https://rsperformance.online}"
PATH_TEST="${PATH_TEST:-/kody-usterek/p0299}"
MAX_TIME="${MAX_TIME:-20}"
SLEEP_MS="${SLEEP_MS:-150}"
PHP_BIN="${PHP_BIN:-php85}"
ALLOW_GOOGLEBOT_403="${SMOKE_ALLOW_GOOGLEBOT_403:-0}"

cd "$LARAVEL" || {
  echo "FAIL cannot cd $LARAVEL"
  exit 2
}

TOKENS=$($PHP_BIN -r '$c=require "config/ai_agents.php"; echo implode("\n", $c["tracked_user_agents"]);') || {
  echo "FAIL php token load"
  exit 2
}

ok=0
fail=0
warn=0
is_ok() {
  case "$1" in 200 | 201 | 204 | 301 | 302 | 303 | 307 | 308) return 0 ;; *) return 1 ;; esac
}

while IFS= read -r tok; do
  [[ -z "${tok// }" ]] && continue
  case "$tok" in
  Googlebot)
    ua="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html) RS-Live-Smoke/2026"
    ;;
  Bingbot)
    ua="Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) RS-Live-Smoke/2026"
    ;;
  *)
    ua="Mozilla/5.0 (compatible; RS-Live-Smoke/2026; ${tok}; +https://rsperformance.online/)"
    ;;
  esac
  code=$(curl -sS -o /dev/null -w '%{http_code}' --max-time "$MAX_TIME" \
    -H "X-RS-Synthetic-Probe: 1" \
    -H "Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8" \
    -A "$ua" \
    "${BASE}${PATH_TEST}" 2>/dev/null || echo "ERR")

  if [[ "$code" == "ERR" ]] || ! is_ok "$code"; then
    if [[ "$tok" == "Googlebot" && "$code" == "403" && "$ALLOW_GOOGLEBOT_403" == "1" ]]; then
      echo "WARN Googlebot -> HTTP 403 (SMOKE_ALLOW_GOOGLEBOT_403=1; review WAF)"
      warn=$((warn + 1))
      ok=$((ok + 1))
    else
      echo "FAIL ${tok} -> HTTP ${code}"
      fail=$((fail + 1))
    fi
  else
    echo "OK   ${tok} -> HTTP ${code}"
    ok=$((ok + 1))
  fi
  sleep "$(awk "BEGIN {print $SLEEP_MS/1000}")"
done <<< "$TOKENS"

echo "---"
echo "SUMMARY OK=${ok} FAIL=${fail} WARN=${warn}"
[[ $fail -eq 0 ]] || exit 1

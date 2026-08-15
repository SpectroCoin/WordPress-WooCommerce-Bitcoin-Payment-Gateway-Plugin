#!/usr/bin/env bash
# ============================================================================
# Keep the SpectroCoin API stub identical across the plugin repositories.
#
# Six plugins speak the same JSON callback flow and share this stub byte for
# byte. Nothing enforces that: the copies live in separate repositories, so a
# fix made in one silently leaves the other five behind, and the tests keep
# passing against a stale double of the API. This is the check that notices.
#
# This directory is the canonical copy. Magento 2 is deliberately NOT in the
# list: it is the one plugin still on the legacy RSA-signed callback flow, so
# its stub serves a certificate and verifies signatures instead. Do not
# "fix" it by syncing.
#
# Usage:
#   ./sync-stubs.sh            # report drift (default, changes nothing)
#   ./sync-stubs.sh --apply    # copy the canonical stub over any that differ
#   WORKSPACE=/path ./sync-stubs.sh
# ============================================================================
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CANONICAL="$HERE/stub"
WORKSPACE="${WORKSPACE:-$(cd "$HERE/../../../.." && pwd)}"
APPLY=0

[ "${1:-}" = "--apply" ] && APPLY=1
[ -n "${1:-}" ] && [ "${1:-}" != "--apply" ] && { echo "unknown argument: $1" >&2; exit 2; }

# Consumers of this stub. Magento 2 is excluded on purpose (see above).
CONSUMERS="
PrestaShop-Bitcoin-Payment-Gateway-Module
OpenCart-Bitcoin-Payment-Gateway-Extension
Drupal-Bitcoin-Payment-Gateway-Module
Joomla-Virtuemart-Bitcoin-Payment-Gateway-Extension
WHMCS-Bitcoin-Payment-Gateway-Plugin
"

FILES="index.php Dockerfile site.conf"

green() { printf '  \033[32m%-9s\033[0m %s\n' "$1" "$2"; }
red()   { printf '  \033[31m%-9s\033[0m %s\n' "$1" "$2"; }
grey()  { printf '  \033[33m%-9s\033[0m %s\n' "$1" "$2"; }

drifted=0
missing=0
synced=0

printf '\n\033[1mcanonical: %s\033[0m\n' "${CANONICAL#$WORKSPACE/}"
[ -f "$CANONICAL/index.php" ] || { echo "canonical stub not found" >&2; exit 1; }

for repo in $CONSUMERS; do
  target="$WORKSPACE/$repo/tests/e2e/tier2/stub"
  if [ ! -d "$target" ]; then
    grey "absent" "$repo"
    missing=$((missing + 1))
    continue
  fi

  differs=""
  for f in $FILES; do
    if ! cmp -s "$CANONICAL/$f" "$target/$f" 2>/dev/null; then
      differs="$differs $f"
    fi
  done

  if [ -z "$differs" ]; then
    green "in sync" "$repo"
    synced=$((synced + 1))
  elif [ "$APPLY" -eq 1 ]; then
    for f in $FILES; do cp "$CANONICAL/$f" "$target/$f"; done
    green "updated" "$repo ←$differs"
    drifted=$((drifted + 1))
  else
    red "DRIFTED" "$repo —$differs"
    drifted=$((drifted + 1))
  fi
done

echo
if [ "$drifted" -eq 0 ] && [ "$missing" -eq 0 ]; then
  echo "all $synced copies match the canonical stub"
  exit 0
fi
if [ "$APPLY" -eq 1 ]; then
  echo "$drifted copy/copies updated; commit them in their own repositories"
  exit 0
fi
echo "$drifted copy/copies differ from the canonical stub - run with --apply to fix"
exit 1

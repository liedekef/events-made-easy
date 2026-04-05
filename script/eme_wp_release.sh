#!/bin/bash
# =============================================================================
# Events Made Easy — WP.org Release Script
#
# Takes the GitHub release ZIP (built by eme_release.sh) and deploys it to
# WP.org SVN, stripping GitHub-specific code in the process.
#
# Usage:
#   bash eme_wp_release.sh <version>           # dry-run (build only, no SVN)
#   bash eme_wp_release.sh <version> --deploy  # build + SVN commit
#
# Prerequisites:
#   - unzip
#   - php (for syntax check)
#   - svn (for --deploy), with working copy already checked out at $EME_SVN_DIR
#     (defaults to ~/svn/events-made-easy)
#
# Typical workflow:
#   1. bash script/eme_release.sh <old_ver> <new_ver>   # GitHub release + ZIP
#   2. bash script/eme_wp_release.sh <new_ver> --deploy # WP.org SVN deploy
# =============================================================================

set -euo pipefail

# --- Configuration -----------------------------------------------------------

PLUGIN_SLUG="events-made-easy"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"

# Local SVN working copy — override with EME_SVN_DIR env var if needed.
# Expected layout: $SVN_WC/trunk/ and $SVN_WC/tags/
#SVN_WC="${EME_SVN_DIR:-${HOME}/svn/${PLUGIN_SLUG}}"
SVN_WC="/home/liedekef/wordpress/svn/events-made-easy"

# --- Argument parsing --------------------------------------------------------

VERSION="${1:-}"
DEPLOY=false

if [ -z "$VERSION" ]; then
    echo "Usage: $0 <version> [--deploy]"
    echo ""
    echo "  <version>   The release version (e.g. 3.0.50)"
    echo "  --deploy    Actually commit to WP.org SVN (default: dry-run)"
    exit 1
fi

if [ "${2:-}" = "--deploy" ]; then
    DEPLOY=true
fi

# --- Path setup --------------------------------------------------------------

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
DIST_ZIP="${PLUGIN_DIR}/dist/${PLUGIN_SLUG}.zip"
BUILD_DIR=$(mktemp -d)
RELEASE_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"

# Colors
if [ -t 1 ]; then
    GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[0;33m'
    BOLD='\033[1m'; NC='\033[0m'
else
    GREEN=''; RED=''; YELLOW=''; BOLD=''; NC=''
fi

info()  { echo -e "  ${GREEN}OK${NC}    $1"; }
warn()  { echo -e "  ${YELLOW}WARN${NC}  $1"; }
fail()  { echo -e "  ${RED}FAIL${NC}  $1"; }
step()  { echo -e "\n${BOLD}[$1] $2${NC}"; }

cleanup() {
    if [ -d "$BUILD_DIR" ]; then
        rm -rf "$BUILD_DIR"
    fi
}
trap cleanup EXIT

ERRORS=0

# =============================================================================
# STEP 1: Validation
# =============================================================================

step "1" "Validation"

# Version must match plugin header
HEADER_VERSION=$(grep -oP "^Version:\s*\K[0-9.]+" "${PLUGIN_DIR}/events-manager.php" || true)
if [ "$VERSION" != "$HEADER_VERSION" ]; then
    fail "Version mismatch: argument='${VERSION}' but events-manager.php says '${HEADER_VERSION}'"
    exit 1
fi
info "Version ${VERSION} matches plugin header"

# GitHub ZIP must exist (created by eme_release.sh)
if [ ! -f "$DIST_ZIP" ]; then
    fail "GitHub release ZIP not found: ${DIST_ZIP}"
    fail "Run script/eme_release.sh first to create the release."
    exit 1
fi
info "GitHub release ZIP: ${DIST_ZIP}"

# SVN working copy must exist for --deploy
if [ "$DEPLOY" = true ]; then
    if ! command -v svn &>/dev/null; then
        fail "svn not found — required for --deploy"
        exit 1
    fi
    if [ ! -d "${SVN_WC}/trunk" ]; then
        fail "SVN working copy not found: ${SVN_WC}/trunk"
        fail "Check out SVN first, or set EME_SVN_DIR to your working copy path."
        exit 1
    fi
    info "SVN working copy: ${SVN_WC}"
fi

# =============================================================================
# STEP 2: Extract GitHub release ZIP
# =============================================================================

step "2" "Extract GitHub release ZIP"

unzip -q "$DIST_ZIP" -d "$BUILD_DIR"
if [ ! -d "$RELEASE_DIR" ]; then
    fail "Expected '${PLUGIN_SLUG}/' directory not found inside ZIP"
    exit 1
fi
info "Extracted: $(find "$RELEASE_DIR" -type f | wc -l) files"

# =============================================================================
# STEP 3: Strip GitHub-only code
# =============================================================================

step "3" "Strip GitHub-only code from events-manager.php"

EM_FILE="${RELEASE_DIR}/events-manager.php"

# Remove "Update URI:" line from plugin header (WP.org handles updates natively)
if grep -q "^Update URI:" "$EM_FILE"; then
    sed -i '/^Update URI:/d' "$EM_FILE"
    info "Removed 'Update URI:' header line"
else
    warn "'Update URI:' not found in header (already removed?)"
fi

# Remove GitHub updater block (everything between BEGIN/END NOT FOR WP markers)
if grep -q "BEGIN NOT FOR WP" "$EM_FILE"; then
    sed -i '/BEGIN NOT FOR WP/,/END NOT FOR WP/d' "$EM_FILE"
    info "Removed GitHub updater block"
else
    fail "BEGIN NOT FOR WP marker not found in release copy"
    ERRORS=$((ERRORS + 1))
fi

# Remove class-eme-updater.php
if [ -f "${RELEASE_DIR}/includes/class-eme-updater.php" ]; then
    rm -f "${RELEASE_DIR}/includes/class-eme-updater.php"
    info "Removed class-eme-updater.php"
fi

# Verify patched file is still valid PHP
if php -l "$EM_FILE" >/dev/null 2>&1; then
    info "Patched events-manager.php passes PHP syntax check"
else
    fail "Patched events-manager.php has PHP syntax errors!"
    ERRORS=$((ERRORS + 1))
fi

TOTAL_FILES=$(find "$RELEASE_DIR" -type f | wc -l)
echo ""
info "Release build verified — ${TOTAL_FILES} files"
info "Build: ${RELEASE_DIR}"

if [ "$ERRORS" -gt 0 ]; then
    echo ""
    fail "Build had ${ERRORS} error(s). Aborting."
    echo "  To inspect:  ls ${RELEASE_DIR}"
    exit 1
fi

# =============================================================================
# SVN Deploy
# =============================================================================

if [ "$DEPLOY" = false ]; then
    echo ""
    echo -e "  ${YELLOW}DRY-RUN${NC} — no SVN changes made."
    echo ""
    echo "  To inspect:  ls ${RELEASE_DIR}"
    echo "  To deploy:   $0 ${VERSION} --deploy"
    echo ""
    trap - EXIT
    exit 0
fi

step "4" "SVN Deploy"

SVN_TRUNK="${SVN_WC}/trunk"
SVN_TAGS="${SVN_WC}/tags"

# Update existing working copy
echo "  Updating SVN trunk..."
svn update "$SVN_TRUNK" -q
info "SVN working copy updated"

# Check if tag already exists
if svn ls "${SVN_URL}/tags/${VERSION}" &>/dev/null; then
    fail "Tag ${VERSION} already exists in SVN. Aborting."
    exit 1
fi
info "Tag ${VERSION} does not exist yet"

# Sync release build to trunk
rsync -a --delete --exclude='.svn' "${RELEASE_DIR}/" "${SVN_TRUNK}/"

# Handle SVN adds/deletes
cd "$SVN_TRUNK"
svn add --force . 2>/dev/null || true
svn status | grep '^!' | awk '{print $NF}' | xargs -r svn delete
info "Trunk synced with release build"

# Create the tag
cd "$SVN_WC"
svn cp "trunk" "tags/${VERSION}"
info "Created SVN tag ${VERSION}"

# Show what will be committed
echo ""
echo -e "  ${BOLD}SVN changes:${NC}"
svn status | head -30
CHANGE_COUNT=$(svn status | wc -l)
if [ "$CHANGE_COUNT" -gt 30 ]; then
    echo "  ... and $((CHANGE_COUNT - 30)) more"
fi

# Confirm
echo ""
echo -e "  ${YELLOW}About to commit to ${SVN_URL}${NC}"
echo -n "  Continue? [y/N] "
read -r CONFIRM
if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "  Aborted."
    exit 1
fi

# Commit
svn commit -m "Release ${VERSION}" "$SVN_WC"
info "Committed to WP.org SVN"

echo ""
echo -e "${GREEN}${BOLD}Release ${VERSION} deployed to WP.org!${NC}"
echo "  https://wordpress.org/plugins/${PLUGIN_SLUG}/"

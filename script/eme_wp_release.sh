#!/bin/bash
# =============================================================================
# Events Made Easy — WP.org Release Script
#
# Creates a clean release build from the GitHub repo and deploys to WP.org SVN.
# Reads .wp-release-ignore for the list of files/dirs to strip.
#
# Usage:
#   bash eme_wp_release.sh <version>           # dry-run (build only, no SVN)
#   bash eme_wp_release.sh <version> --deploy  # build + SVN commit
#
# Prerequisites:
#   - svn (for --deploy)
#   - git
#   - php (for syntax check)
# =============================================================================

set -euo pipefail

# --- Configuration -----------------------------------------------------------

PLUGIN_SLUG="events-made-easy"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"

# Third-party directories/files that get phpcs:disable injected in the build.
# These are vendor SDKs whose code we don't control but PCP still scans.
VENDOR_PHP_DIRS=(payment_gateways dompdf)
VENDOR_PHP_FILES=(class-expressivedate.php)

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
IGNORE_FILE="${SCRIPT_DIR}/.wp-release-ignore"
BUILD_DIR=$(mktemp -d)
RELEASE_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"
SVN_DIR="${BUILD_DIR}/svn"

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

# --- Parse .wp-release-ignore ------------------------------------------------

STRIP_FILES=()          # Exact file paths (relative to plugin root)
STRIP_DIRS=()           # Exact directory paths (trailing /)
STRIP_GLOBS=()          # Glob patterns (e.g. langs/*.po)

# Vendor cleanup blocks: parallel arrays (one entry per [vendor-cleanup] block)
VC_PATHS=()             # path: value
VC_DIRS=()              # dirs: value (space-separated dir names)
VC_FILES=()             # files: value (space-separated filenames)
VC_HIDDEN=()            # hidden: yes/no
VC_KEEP=()              # keep: value (path to preserve)

if [ -f "$IGNORE_FILE" ]; then
    CURRENT_SECTION=""
    VC_INDEX=-1
    while IFS= read -r line; do
        # Skip comments and empty lines
        [[ "$line" =~ ^[[:space:]]*# ]] && continue
        [[ -z "${line// /}" ]] && continue

        # Trim whitespace
        line=$(echo "$line" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')

        # Section header
        if [[ "$line" == "["*"]" ]]; then
            CURRENT_SECTION="${line//[\[\]]/}"
            if [[ "$CURRENT_SECTION" == "vendor-cleanup" ]]; then
                VC_INDEX=$((VC_INDEX + 1))
                VC_PATHS+=("")
                VC_DIRS+=("")
                VC_FILES+=("")
                VC_HIDDEN+=("no")
                VC_KEEP+=("")
            fi
            continue
        fi

        # Inside a [vendor-cleanup] block: parse key: value pairs
        if [[ "$CURRENT_SECTION" == "vendor-cleanup" ]]; then
            key=$(echo "$line" | sed 's/:.*//' | sed 's/[[:space:]]*$//')
            val=$(echo "$line" | sed 's/^[^:]*:[[:space:]]*//')
            case "$key" in
                path)   VC_PATHS[$VC_INDEX]="$val" ;;
                dirs)   VC_DIRS[$VC_INDEX]="$val" ;;
                files)  VC_FILES[$VC_INDEX]="$val" ;;
                hidden) VC_HIDDEN[$VC_INDEX]="$val" ;;
                keep)   VC_KEEP[$VC_INDEX]="$val" ;;
            esac
            continue
        fi

        # Default section: plugin root files/dirs/globs
        if [[ "$line" == */ ]]; then
            STRIP_DIRS+=("$line")
        elif [[ "$line" == *"*"* ]]; then
            STRIP_GLOBS+=("$line")
        else
            STRIP_FILES+=("$line")
        fi
    done < "$IGNORE_FILE"
else
    warn ".wp-release-ignore not found at ${IGNORE_FILE}"
fi

# =============================================================================
# STEP 1: Validation
# =============================================================================

step "1/7" "Validation"

# Check version matches plugin header
HEADER_VERSION=$(grep -oP "^Version:\s*\K[0-9.]+" "${PLUGIN_DIR}/events-manager.php" || true)
if [ "$VERSION" != "$HEADER_VERSION" ]; then
    fail "Version mismatch: argument='${VERSION}' but events-manager.php says '${HEADER_VERSION}'"
    exit 1
fi
info "Version ${VERSION} matches plugin header"

# Check .wp-release-ignore loaded
info "Loaded .wp-release-ignore: ${#STRIP_FILES[@]} files, ${#STRIP_DIRS[@]} dirs, ${#STRIP_GLOBS[@]} globs, ${#VC_PATHS[@]} vendor-cleanup blocks"

# Check git is clean
cd "$PLUGIN_DIR"
if [ -n "$(git status --porcelain)" ]; then
    warn "Git working directory is not clean. Continuing with HEAD."
fi

# Check required tools
if [ "$DEPLOY" = true ]; then
    if ! command -v svn &>/dev/null; then
        fail "svn is required for --deploy"
        exit 1
    fi
    info "SVN available"
fi

# PHP syntax check on own code
step "2/7" "PHP syntax check"
PHP_ERRORS=0
for file in "${PLUGIN_DIR}"/*.php; do
    if ! php -l "$file" >/dev/null 2>&1; then
        fail "Syntax error: $(basename "$file")"
        PHP_ERRORS=$((PHP_ERRORS + 1))
    fi
done
if [ "$PHP_ERRORS" -gt 0 ]; then
    fail "${PHP_ERRORS} PHP files have syntax errors. Aborting."
    exit 1
fi
info "All PHP files pass syntax check"

# =============================================================================
# STEP 3: Create clean copy
# =============================================================================

step "3/7" "Create clean release copy"

# Export from git (no .git directory)
mkdir -p "$RELEASE_DIR"
git -C "$PLUGIN_DIR" archive HEAD | tar -x -C "$RELEASE_DIR"
info "Git archive exported"

# --- Apply .wp-release-ignore rules -----------------------------------------

REMOVED=0

# Remove exact files
for f in "${STRIP_FILES[@]}"; do
    target="${RELEASE_DIR}/${f}"
    if [ -f "$target" ]; then
        rm -f "$target"
        REMOVED=$((REMOVED + 1))
    fi
done

# Remove exact directories
for d in "${STRIP_DIRS[@]}"; do
    target="${RELEASE_DIR}/${d%/}"  # strip trailing slash
    if [ -d "$target" ]; then
        rm -rf "$target"
        REMOVED=$((REMOVED + 1))
    fi
done

# Remove glob patterns
for g in "${STRIP_GLOBS[@]}"; do
    # Use find with the glob pattern
    dir=$(dirname "$g")
    pattern=$(basename "$g")
    find "${RELEASE_DIR}/${dir}" -maxdepth 1 -name "$pattern" -delete 2>/dev/null || true
    REMOVED=$((REMOVED + 1))
done

info "Removed ${REMOVED} files/dirs from ignore list"

# --- Apply [vendor-cleanup] blocks -------------------------------------------

for i in "${!VC_PATHS[@]}"; do
    vpath="${VC_PATHS[$i]}"
    vdirs="${VC_DIRS[$i]}"
    vfiles="${VC_FILES[$i]}"
    vhidden="${VC_HIDDEN[$i]}"
    vkeep="${VC_KEEP[$i]}"
    vfull="${RELEASE_DIR}/${vpath}"

    [ -d "$vfull" ] || continue
    info "Cleaning vendor: ${vpath}/"

    # Strip directories
    SDK_STRIPPED=0
    for dirname in $vdirs; do
        while IFS= read -r dir; do
            # Check keep list: skip if dir matches a keep path
            if [ -n "$vkeep" ]; then
                rel="${dir#"$vfull"/}"
                if [[ "$rel" == "$vkeep" ]]; then
                    continue
                fi
            fi
            rm -rf "$dir"
            SDK_STRIPPED=$((SDK_STRIPPED + 1))
        done < <(find "$vfull" -type d -iname "$dirname" 2>/dev/null)
    done
    [ "$SDK_STRIPPED" -gt 0 ] && info "  Stripped ${SDK_STRIPPED} non-runtime directories"

    # Strip metadata files
    FILES_REMOVED=0
    for fname in $vfiles; do
        while IFS= read -r f; do
            rm -f "$f"
            FILES_REMOVED=$((FILES_REMOVED + 1))
        done < <(find "$vfull" -type f -name "$fname" 2>/dev/null)
    done
    [ "$FILES_REMOVED" -gt 0 ] && info "  Removed ${FILES_REMOVED} metadata files"

    # Strip hidden files/dirs
    if [ "$vhidden" = "yes" ]; then
        HIDDEN_REMOVED=0
        while IFS= read -r h; do
            rm -rf "$h"
            HIDDEN_REMOVED=$((HIDDEN_REMOVED + 1))
        done < <(find "$vfull" -name '.*' 2>/dev/null)
        [ "$HIDDEN_REMOVED" -gt 0 ] && info "  Removed ${HIDDEN_REMOVED} hidden files/dirs"
    fi
done

# =============================================================================
# STEP 4: Inject phpcs:disable into third-party PHP files
# =============================================================================
# PCP (Plugin Check Plugin) scans ALL PHP files in the release, including
# third-party SDKs. These vendor files generate hundreds of PCP errors that
# would block WP.org submission. Since we must not modify vendor code in the
# repo (upstream updates would overwrite changes), we inject a phpcs:disable
# comment at the top of each vendor PHP file during the build only.
#
# References:
#   - WP.org PCP requirement: https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/#plugin-check
#   - PCP auto-ignores vendor_prefixed/ but not custom vendor dir names
#   - Feature request for configurable exclusions: https://github.com/WordPress/plugin-check/issues/823

step "4/7" "Inject phpcs:disable into third-party PHP files"

VENDOR_INJECTED=0

# Process vendor directories
for vdir in "${VENDOR_PHP_DIRS[@]}"; do
    vfull="${RELEASE_DIR}/${vdir}"
    [ -d "$vfull" ] || continue
    while IFS= read -r phpfile; do
        # Only inject if file starts with <?php and doesn't already have phpcs:disable
        head -3 "$phpfile" | grep -q 'phpcs:disable' && continue
        if head -1 "$phpfile" | grep -q '^<?php'; then
            sed -i '1 a\// phpcs:disable' "$phpfile"
            VENDOR_INJECTED=$((VENDOR_INJECTED + 1))
        fi
    done < <(find "$vfull" -name '*.php' -type f)
done

# Process individual vendor files
for vfile in "${VENDOR_PHP_FILES[@]}"; do
    vfull="${RELEASE_DIR}/${vfile}"
    [ -f "$vfull" ] || continue
    head -3 "$vfull" | grep -q 'phpcs:disable' && continue
    if head -1 "$vfull" | grep -q '^<?php'; then
        sed -i '1 a\// phpcs:disable' "$vfull"
        VENDOR_INJECTED=$((VENDOR_INJECTED + 1))
    fi
done

info "Injected phpcs:disable into ${VENDOR_INJECTED} third-party PHP files"

# =============================================================================
# STEP 5: Patch events-manager.php
# =============================================================================

step "5/7" "Patch events-manager.php (remove GitHub updater + Update URI)"

EM_FILE="${RELEASE_DIR}/events-manager.php"

# 5a: Remove "Update URI:" line from plugin header
if grep -q "^Update URI:" "$EM_FILE"; then
    sed -i '/^Update URI:/d' "$EM_FILE"
    info "Removed 'Update URI:' header line"
else
    warn "'Update URI:' not found in header (already removed?)"
fi

# 5b: Remove GitHub updater block
#     require_once("class-eme-updater.php");
#     $plugin_file = EME_PLUGIN_FILE_PATH;
#     $github_username = 'liedekef';
#     $github_repository = 'events-made-easy';
#     new EME_GitHub_Updater($plugin_file, $github_username, $github_repository);
if grep -q 'class-eme-updater' "$EM_FILE"; then
    sed -i '/require_once.*class-eme-updater/,/EME_GitHub_Updater/d' "$EM_FILE"
    info "Removed GitHub updater code block"
else
    warn "GitHub updater code not found (already removed?)"
fi

# 5c: Verify the patched file is still valid PHP
if php -l "$EM_FILE" >/dev/null 2>&1; then
    info "Patched events-manager.php passes PHP syntax check"
else
    fail "Patched events-manager.php has PHP syntax errors!"
    ERRORS=$((ERRORS + 1))
fi

# =============================================================================
# STEP 6: Verification
# =============================================================================

step "6/7" "Verification"

# Verify critical files exist
for f in events-manager.php readme.txt eme-functions.php eme-events.php; do
    if [ ! -f "${RELEASE_DIR}/$f" ]; then
        fail "Missing critical file: $f"
        ERRORS=$((ERRORS + 1))
    fi
done
info "Critical plugin files present"

# Verify removed files are gone
for f in class-eme-updater.php .gitignore README.md CONTRIBUTING.md CODE_OF_CONDUCT.md SECURITY.md; do
    if [ -f "${RELEASE_DIR}/$f" ]; then
        fail "File should have been removed: $f"
        ERRORS=$((ERRORS + 1))
    fi
done
info "GitHub-only files removed"

# Verify no Update URI in header
if grep -q "^Update URI:" "${RELEASE_DIR}/events-manager.php"; then
    fail "Update URI still present in events-manager.php"
    ERRORS=$((ERRORS + 1))
else
    info "No Update URI in plugin header"
fi

# Verify no updater require
if grep -q "class-eme-updater" "${RELEASE_DIR}/events-manager.php"; then
    fail "GitHub updater code still present in events-manager.php"
    ERRORS=$((ERRORS + 1))
else
    info "No GitHub updater code in events-manager.php"
fi

# Verify version in plugin header
if grep -q "^Version: ${VERSION}" "${RELEASE_DIR}/events-manager.php"; then
    info "Version ${VERSION} in plugin header"
else
    fail "Version ${VERSION} not found in plugin header"
    ERRORS=$((ERRORS + 1))
fi

# Verify .mo files are present (bundled translations)
MO_COUNT=$(find "${RELEASE_DIR}/langs" -name "*.mo" | wc -l)
if [ "$MO_COUNT" -gt 0 ]; then
    info "${MO_COUNT} .mo translation files bundled"
else
    warn "No .mo files found in langs/"
fi

# Verify no .po/.pot source files leaked through
PO_COUNT=$(find "${RELEASE_DIR}/langs" -name "*.po" -o -name "*.pot" | wc -l)
if [ "$PO_COUNT" -gt 0 ]; then
    fail "${PO_COUNT} .po/.pot source files still present"
    ERRORS=$((ERRORS + 1))
else
    info "No .po/.pot source files in release"
fi

# Verify no hidden files in vendor dirs
HIDDEN_LEFT=0
for i in "${!VC_PATHS[@]}"; do
    [ "${VC_HIDDEN[$i]}" = "yes" ] || continue
    count=$(find "${RELEASE_DIR}/${VC_PATHS[$i]}" -name '.*' 2>/dev/null | wc -l)
    HIDDEN_LEFT=$((HIDDEN_LEFT + count))
done
if [ "$HIDDEN_LEFT" -gt 0 ]; then
    fail "${HIDDEN_LEFT} hidden files still in vendor dirs"
    ERRORS=$((ERRORS + 1))
else
    info "No hidden files in vendor dirs"
fi

# Verify phpcs:disable was injected into vendor PHP files
VENDOR_WITHOUT_DISABLE=0
for vdir in "${VENDOR_PHP_DIRS[@]}"; do
    vfull="${RELEASE_DIR}/${vdir}"
    [ -d "$vfull" ] || continue
    while IFS= read -r phpfile; do
        if ! head -3 "$phpfile" | grep -q 'phpcs:disable'; then
            fail "Missing phpcs:disable: ${phpfile#"$RELEASE_DIR"/}"
            VENDOR_WITHOUT_DISABLE=$((VENDOR_WITHOUT_DISABLE + 1))
        fi
    done < <(find "$vfull" -name '*.php' -type f)
done
if [ "$VENDOR_WITHOUT_DISABLE" -gt 0 ]; then
    fail "${VENDOR_WITHOUT_DISABLE} vendor PHP files missing phpcs:disable"
    ERRORS=$((ERRORS + 1))
else
    info "All vendor PHP files have phpcs:disable"
fi

# Summary check
if [ "$ERRORS" -gt 0 ]; then
    echo ""
    fail "${ERRORS} verification error(s). Aborting."
    exit 1
fi

echo ""
info "Release build verified. ${RELEASE_DIR}"

# File count
TOTAL_FILES=$(find "$RELEASE_DIR" -type f | wc -l)
info "Total files in release: ${TOTAL_FILES}"

# =============================================================================
# STEP 7: SVN Deploy
# =============================================================================

step "7/7" "SVN Deploy"

if [ "$DEPLOY" = false ]; then
    echo ""
    echo -e "  ${YELLOW}DRY-RUN${NC} — build complete at: ${RELEASE_DIR}"
    echo ""
    echo "  To inspect:  ls ${RELEASE_DIR}"
    echo "  To deploy:   $0 ${VERSION} --deploy"
    echo ""
    # Keep build dir for inspection (don't cleanup)
    trap - EXIT
    exit 0
fi

# SVN checkout (trunk only, shallow)
echo "  Checking out SVN trunk..."
svn checkout "${SVN_URL}/trunk" "${SVN_DIR}/trunk" --depth=infinity -q
svn checkout "${SVN_URL}/tags" "${SVN_DIR}/tags" --depth=empty -q
info "SVN checkout complete"

# Check if tag already exists
if svn ls "${SVN_URL}/tags/${VERSION}" &>/dev/null; then
    fail "Tag ${VERSION} already exists in SVN. Aborting."
    exit 1
fi
info "Tag ${VERSION} does not exist yet"

# Sync release to trunk
find "${SVN_DIR}/trunk" -mindepth 1 -maxdepth 1 ! -name '.svn' -exec rm -rf {} +
cp -a "${RELEASE_DIR}"/* "${SVN_DIR}/trunk/"

# Handle SVN adds/deletes
cd "${SVN_DIR}/trunk"
svn add --force . 2>/dev/null || true
svn status | grep '^\!' | awk '{print $2}' | xargs -r svn delete --force 2>/dev/null || true
info "Trunk synced with release build"

# Create the tag
cd "${SVN_DIR}"
svn cp "trunk" "tags/${VERSION}"
info "Created SVN tag ${VERSION}"

# Show what will be committed
echo ""
echo -e "  ${BOLD}SVN changes:${NC}"
cd "${SVN_DIR}"
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
svn commit -m "Release ${VERSION}" "${SVN_DIR}"
info "Committed to WP.org SVN"

echo ""
echo -e "${GREEN}${BOLD}Release ${VERSION} deployed to WP.org!${NC}"
echo "  https://wordpress.org/plugins/${PLUGIN_SLUG}/"

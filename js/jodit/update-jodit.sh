#!/bin/bash
#
# Updates Jodit editor to a given version.
# Downloads the minified build from CDN and generates
# the unminified source via js-beautify (required by
# WordPress.org Plugin Check).
#
# Usage: ./update-jodit.sh 4.8.3
#
# Requires: npx (ships with Node.js)

set -e

version=$1
if [ -z "$version" ]; then
    echo "Usage: $0 <version>"
    echo "Example: $0 4.8.3"
    exit 1
fi

scriptdir=$(dirname "$(realpath "$0")")
cd "$scriptdir"

echo "Downloading jodit.fat.min.js v${version} ..."
curl -fsSL "https://cdn.jsdelivr.net/npm/jodit@${version}/es2021/jodit.fat.min.js" -o jodit.fat.min.js

echo "Generating unminified jodit.fat.js via js-beautify ..."
npx js-beautify jodit.fat.min.js -o jodit.fat.js

echo "Done. Sizes:"
ls -lh jodit.fat.min.js jodit.fat.js

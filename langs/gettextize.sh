#!/bin/bash

# First download wp-cli
set -e

declare -A GP_SLUG=(
  [cs_CZ]=cs   [de_DE]=de   [fr_FR]=fr   [nl_BE]=nl-be
  [nl_NL]=nl   [sk_SK]=sk   [sv_SE]=sv   [zh_CN]=zh-cn
)

plugin_dir=$(realpath ../)

get_rev_date() {
    grep -m1 '"PO-Revision-Date' "$1" | grep -oP '\d{4}-\d{2}-\d{2} \d{2}:\d{2}'
}

if ! command -v msgcat >/dev/null 2>&1; then
  echo "msgcat not found — GNU gettext must be installed for GlotPress integration, skipping GlotPress pull/merge"
else
    for locale in "${!GP_SLUG[@]}"; do
        slug=${GP_SLUG[$locale]}
        po_file="events-made-easy-${locale}.po"
        gp_file="events-made-easy-${locale}.gp.po"

        if ! curl -sf "https://translate.wordpress.org/projects/wp-plugins/events-made-easy/stable/${slug}/default/export-translations/?format=po" -o "$gp_file"; then
            echo "warning: failed to download $locale from GlotPress" >&2
            rm -f "$gp_file"
            continue
        fi

        echo "Downloaded $gp_file from glotpress (https://translate.wordpress.org/projects/wp-plugins/events-made-easy/stable/${slug}/default/export-translations/?format=po)"

        if [ ! -s "$po_file" ]; then
            mv "$gp_file" "$po_file"
            continue
        fi

        local_date=$(get_rev_date "$po_file")
        gp_date=$(get_rev_date "$gp_file")

       # decide file order: newer file wins conflicts
       if [[ "$gp_date" > "$local_date" ]]; then
           first="$gp_file"; second="$po_file"; winner="glotpress"
           echo "GlotPress file $gp_file is most recent, taking precedence for merging"
       else
           first="$po_file"; second="$gp_file"; winner="local"
           echo "Local file $gp_file is most recent, taking precedence for merging"
       fi

       conflict_log="${po_file}.conflicts.log"
       msgcat --use-first -o "${po_file}.merged" "$first" "$second" 2> "$conflict_log"

       if [ -s "$conflict_log" ]; then
           echo "$locale: $winner wins (local=$local_date, glotpress=$gp_date) — conflicts logged, review $conflict_log"
       else
           rm -f "$conflict_log"
       fi

       mv "${po_file}.merged" "$po_file"
       rm -f "$gp_file"
   done
fi

# regenerate pot from source and merge into all po files
cd "$plugin_dir"
../wp-cli i18n make-pot . langs/events-made-easy.pot --skip-audit
../wp-cli i18n update-po langs/events-made-easy.pot
../wp-cli i18n make-mo langs/
../wp-cli i18n make-php langs/

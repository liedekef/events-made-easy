#/bin/bash

plugin="events-made-easy"
old_release=$1
release=$2
if [ -z "$release" ]; then
       echo "Usage: $0 <old version number> <new version number>"
       exit
fi       

scriptpath=$(realpath "$0")
scriptdir=$(dirname $scriptpath)
basedir=$(dirname $scriptdir)
cd $basedir

has_error=0
# do a syntax check
for file in *.php; do
    echo "Checking: $file"
    if ! php -l "$file" > /dev/null; then
        echo "Syntax error in file: $file"
        has_error=1
    fi
done

if [ $has_error -eq 1 ]; then
    exit "Some PHP files have syntax errors."
fi

# If wanted, automate language file updates
cd $basedir/langs
./gettextize.sh

# now update the release version
cd $basedir
sed -i "s/$old_release/$release/" events-manager.php
sed -i "s/Stable tag: $old_release/Stable tag: $release/" readme.txt

# now create a zip of the new release
cd $basedir/..
# some payment gateways (sumup) look at composer.json for their version info, so don't exclude that
zip -r $plugin.zip $plugin -x '*.git*' "$plugin/langs/*.po" "$plugin/langs/pot_gen*" "$plugin/langs/*.pot" "$plugin/langs/gettextize.sh*" "$plugin/dist*" "$plugin/changelog.txt" "$plugin/script*" "$plugin/payment_gateways/*/composer.lock" "$plugin/js/fdatepicker/js/fdatepicker.js" "$plugin/js/ftable/ftable.js" "$plugin/js/jodit/jodit.fat.js" "$plugin/js/sortable/sortable.js" "$plugin/js/leaflet-gesturehandling*/leaflet-gesture-handling.js" "$plugin/js/tomselect/tom-select.complete.js" "$plugin/js/jodit/update-jodit.sh"
mv $plugin.zip $basedir/dist

# move 
cd $basedir
git commit -m "release $release" -a
git push
gh release create "v${release}" --generate-notes ./dist/*.zip

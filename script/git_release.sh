#/bin/bash

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

# first create a minimal zip of the previous release
cd $basedir/..
#zip -r events-made-easy-minimal.zip events-made-easy -x '*.git*' 'events-made-easy/langs/*.po' 'events-made-easy/langs/pot_gen*' 'events-made-easy/langs/*.pot' 'events-made-easy/langs/gettextize.sh*' 'events-made-easy/screenshot*' -x 'events-made-easy/dist*' -x 'events-made-easy/changelog.txt' -x 'events-made-easy/script*' -x 'events-made-easy/dompdf*' -x 'events-made-easy/payment_gateways*'
#mv events-made-easy-minimal.zip events-made-easy/dist


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
zip -r events-made-easy.zip events-made-easy -x '*.git*' 'events-made-easy/langs/*.po' 'events-made-easy/langs/pot_gen*' 'events-made-easy/langs/*.pot' 'events-made-easy/langs/gettextize.sh*' 'events-made-easy/screenshot*' -x 'events-made-easy/dist*' -x 'events-made-easy/changelog.txt' -x 'events-made-easy/script*' -x 'events-made-easy/payment_gateways/*/composer.lock'
mv events-made-easy.zip $basedir/dist

# move 
cd $basedir
git commit -m "release $release" -a
git push
gh release create "v${release}" --generate-notes ./dist/*.zip

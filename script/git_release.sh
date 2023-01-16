#/bin/bash

old_release=$1
release=$2
if [ -z "$release" ]; then
       echo "Usage: $0 <old version number> <new version number>"
       exit
fi       

# first create a minimal zip of the previous release
cd /home/liedekef/wordpress/git
zip -r events-made-easy-minimal.zip events-made-easy -x '*.git*' 'events-made-easy/langs/*.po' 'events-made-easy/langs/pot_gen*' 'events-made-easy/langs/*.pot' 'events-made-easy/langs/gettextize.sh' 'events-made-easy/screenshot*' -x 'events-made-easy/dist*' -x 'events-made-easy/changelog.txt' -x 'events-made-easy/script*' -x 'events-made-easy/dompdf*' -x 'events-made-easy/payment_gateways*'
mv events-made-easy-minimal.zip events-made-easy/dist


# If wanted, automate language file updates
cd /home/liedekef/wordpress/git/events-made-easy/langs
./gettextize.sh

# now update the release version
cd /home/liedekef/wordpress/git/events-made-easy
sed -i "s/$old_release/$release/" events-manager.php
sed -i "s/Stable tag: $old_release/Stable tag: $release/" readme.txt

# now create a zip of the new release
cd /home/liedekef/wordpress/git
zip -r events-made-easy.zip events-made-easy -x '*.git*' 'events-made-easy/langs/*.po' 'events-made-easy/langs/pot_gen*' 'events-made-easy/langs/*.pot' 'events-made-easy/langs/gettextize.sh' 'events-made-easy/screenshot*' -x 'events-made-easy/dist*' -x 'events-made-easy/changelog.txt' -x 'events-made-easy/script*'
mv events-made-easy.zip events-made-easy/dist

# move 
cd /home/liedekef/wordpress/git/events-made-easy
git commit -m "release $release" -a
git push
gh release create "v${release}" --generate-notes ./dist/*.zip

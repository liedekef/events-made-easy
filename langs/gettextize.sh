#!/bin/bash

# First copy /var/www/html/wordpress/wp-includes/pomo/ into pot_gen
# Now you can generate the pot file:

cd pot_gen
# path to plugin
plugin_dir=`realpath ../../`
lang_dir="$plugin_dir/langs"
# excludes langs/pot_gen,plugin-update-checker are set fixed in makepot.php
mv "$lang_dir/events-made-easy.pot" "$lang_dir/events-made-easy-orig.pot"
php makepot.php wp-plugin $plugin_dir "$lang_dir/events-made-easy.pot"
diff -q -d -I '^#' -I '^\"' "$lang_dir/events-made-easy-orig.pot" "$lang_dir/events-made-easy.pot" >/dev/null
if [ $? -gt 0 ]; then
	# the new pot file differs from the old, so let's regenerate all
	echo "new pot file differs from old, regenerating"
	cd "$lang_dir"
	for i in `ls *po`; do
		j=`echo "${i%.*}"`
		# first remove old location comments
		grep -v '^# File:' $i > "tmp.po"
		# now merge
		echo "==> Merging pot into new $i"
		msgmerge --strict -o "$i" "tmp.po" events-made-easy.pot
		rm "tmp.po"
	done
	rm "$lang_dir/events-made-easy-orig.pot"
	# to make sure the pot is newer than the po files, touch it now (otherwise upon subsequent runs, we fall into the "else" for each po)
	touch events-made-easy.pot
else
	mv "$lang_dir/events-made-easy-orig.pot" "$lang_dir/events-made-easy.pot"
	cd "$lang_dir"
	# in case the pot didn't change, but someone mailed/provided a new po: remerge
	for i in `ls *po`; do
		j=`echo "${i%.*}"`
		if [ "$i" -nt "events-made-easy.pot" ]; then
			# first remove old location comments
			grep -v '^# File:' $i > "tmp.po"
			# now merge
			echo "==> Merging pot into new2 $i"
			msgmerge --strict -o "$i" "tmp.po" events-made-easy.pot
			rm "tmp.po"
		fi
	done
fi

# regenerate mo files if po file is newer
# doing this "later" also covers the case where someone provided a new pot file
# via git/mail
cd "$lang_dir"
for i in `ls *po`; do
        j=`echo "${i%.*}"`
        if [ "$i" -nt "$j.mo" ]; then
		echo "==> PO newer than MO, recompiling strings for $i"
		msgfmt --strict -c -v -o "$j.mo" "$i"
        fi
done

echo
echo "Don't forget to git add/commit/push the new files:"
echo "git commit -m 'language file updates' events-made-easy*"
echo 'git push'

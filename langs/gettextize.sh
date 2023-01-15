# First copy /var/www/html/wordpress/wp-includes/pomo/ into pot_gen

# Now you can generate the pot file:

cd pot_gen
# path to plugin
plugin_dir="../../"
# excludes langs/pot_gen,plugin-update-checker are set fixed in makepot.php
php makepot.php wp-plugin $plugin_dir "$plugin_dir/langs/events-made-easy.pot"
cd "$plugin_dir/langs"
for i in `ls *po`; do
   j=`echo "${i%.*}"`
   # first remove old location comments
   grep -v '^# File:' $i > "$j-new.po"
   mv "$j-new.po" "$j.po"
   msgmerge --strict -o "$j-new.po" "$j.po" events-made-easy.pot
   echo "==> Compiling strings for $i"
   msgfmt --strict -c -v -o "$j-new.mo" "$j-new.po"
   mv "$j-new.po" "$j.po"
   mv "$j-new.mo" "$j.mo"
done

echo
echo "Don't forget to git add/commit/push the new files:"
echo "git commit -m 'language file updates' events-made-easy*"
echo 'git push'

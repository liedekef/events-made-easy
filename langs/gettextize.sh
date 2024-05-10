#!/bin/bash

# First download wp-cli
# Now you can generate the pot file:

# path to plugin
plugin_dir=`realpath ../`
cd $plugin_dir
../wp-cli i18n make-pot . langs/events-made-easy.pot --skip-audit
../wp-cli i18n update-po langs/events-made-easy.pot
../wp-cli i18n make-mo langs/
#../wp-cli i18n make-php langs/

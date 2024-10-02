#!/bin/bash

# Fix broken easygettext
sed -i 's|cheerio/lib/utils|cheerio/utils|g' node_modules/easygettext/src/extract.js

# First, run javascript extraction
npx gettext-extract --attribute v-i18n --attribute v-translate --output locale/en/LC_MESSAGES/opencast_js.pot $(find vueapp -type f -name '*.vue')  $(find vueapp -type f -name '*.js')

# Run standard gettext extraction
PO=locale/en/LC_MESSAGES/opencast.po
POTPHP=locale/en/LC_MESSAGES/opencast_php.pot
POTJS=locale/en/LC_MESSAGES/opencast_js.pot
POT=locale/en/LC_MESSAGES/opencast.pot
MO=locale/en/LC_MESSAGES/opencast.mo

rm -f $POT
rm -f $POTPHP

find * \( -iname "*.php" -o -iname "*.ihtml" \) | xargs xgettext --from-code=UTF-8 --add-location=full --package-name=Opencast --language=PHP -o $POTPHP

msgcat $POTJS $POTPHP -o $POT
msgmerge $PO $POT -o $PO
msgfmt $PO --output-file=$MO

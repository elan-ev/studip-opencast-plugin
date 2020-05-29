#!/bin/bash

PO=locale/en/LC_MESSAGES/opencast.po
POT=locale/en/LC_MESSAGES/opencast.pot
MO=locale/en/LC_MESSAGES/opencast.mo

rm -f $POT

find * \( -iname "*.php" -o -iname "*.ihtml" \) | xargs xgettext --from-code=UTF-8 --add-location=full --package-name=Opencast --language=PHP -o $POT

msgmerge $PO $POT -o $PO
msgfmt $PO --output-file=$MO

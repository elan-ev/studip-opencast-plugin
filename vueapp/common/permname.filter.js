export default (perm, $gettext) => {

    let translations = {
        'owner': $gettext('Besitzer/in'),
        'write': $gettext('Schreibrechte'),
        'read':  $gettext('Leserechte'),
        'share': $gettext('Kann weiterteilen')
    }

    return translations[perm] ? translations[perm] : ''
};

export default perm => {

    let translations = {
        'owner': 'Besitzer/in',
        'write': 'Schreibrechte',
        'read':  'Leserechte',
        'share': 'Kann weiterteilen'
    }

    return translations[perm] ? translations[perm] : ''
};

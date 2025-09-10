import { getCurrentInstance } from 'vue';

export function useSetting() {

    const { proxy } = getCurrentInstance();
    const $gettext = proxy.$gettext;

    const mapping = {
        OPENCAST_ALLOW_ALTERNATE_SCHEDULE: {
            label: $gettext('Abweichende Aufzeichnungszeiten erlauben'),
            tooltip: $gettext('Erlaubt Lehrenden, von der Standard-Aufzeichnungszeit abzuweichen'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_MEDIADOWNLOAD_PER_COURSE: {
            label: $gettext('Mediendownloads erlauben'),
            tooltip: $gettext('Verwaltet, ob Nutzende Videos herunterladen dürfen'),
            input: 'dropdown',
            type: 'string',
            i18n: false
        },
        OPENCAST_ALLOW_PERMISSION_ASSIGNMENT: {
            label: $gettext('Rechte an Videos vergeben'),
            tooltip: $gettext('Erlaubt Nutzenden, Rechte für Videos zu vergeben'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_PUBLIC_SHARING: {
            label: $gettext('Videos weltweit freigeben'),
            tooltip: $gettext('Erlaubt Nutzenden, Videos öffentlich zugänglich zu machen'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_SCHEDULER: {
            label: $gettext('Aufzeichnungen planen'),
            tooltip: $gettext('Erlaubt das Planen von Aufzeichnungen'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_SHARING: {
            label: $gettext('Videos teilen'),
            tooltip: $gettext('Erlaubt Nutzenden, Videos innerhalb von Kursen oder Gruppen zu teilen'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_STUDENT_UPLOAD: {
            label: $gettext('Studierende dürfen Videos im Kurs hochladen'),
            tooltip: $gettext('Erlaubt Studierenden, Videos direkt im Kurs hochzuladen'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_STUDENT_WORKSPACE_UPLOAD: {
            label: $gettext('Studierende dürfen Videos im Arbeitsplatz hochladen'),
            tooltip: $gettext('Erlaubt Studierenden, Videos in ihrem persönlichen Arbeitsplatz hochzuladen'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_STUDIO: {
            label: $gettext('Aufzeichnungen mit Opencast Studio erstellen'),
            tooltip: $gettext('Erlaubt Nutzenden, direkt Aufzeichnungen mit Opencast Studio zu starten'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_STUDYGROUP_CONF: {
            label: $gettext('Opencast in Studiengruppen aktivierbar'),
            tooltip: $gettext('Erlaubt, dass das Werkzeug Opencast in Studiengruppen eingeschaltet werden kann'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_ALLOW_TECHNICAL_FEEDBACK: {
            label: $gettext('Technisches Feedback zu Videos erlauben'),
            tooltip: $gettext('Erlaubt Nutzenden, technisches Feedback zu einzelnen Videos zu geben'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_API_TOKEN: {
            label: $gettext('API Token'),
            tooltip: $gettext('Der hier eingetragene Token muss auch im StudipUserProvider in Opencast hinterlegt sein'),
            input: 'text',
            type: 'string',
            i18n: false
        },
        OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL: {
            label: $gettext('Löschfrist'),
            tooltip: $gettext('Anzahl der Tage, nach denen markierte Videos endgültig gelöscht werden'),
            input: 'number',
            type: 'integer',
            i18n: false
        },
        OPENCAST_COURSE_DEFAULT_EPISODES_VISIBILITY: {
            label: $gettext('Standard-Sichtbarkeit von Episoden'),
            tooltip: $gettext('Legt den Sichtbarkeitsstatus für Episoden fest, die den Standardwerten im Kurs folgen'),
            input: 'dropdown',
            type: 'string',
            i18n: false
        },
        OPENCAST_DEFAULT_SERVER: {
            label: $gettext('Standard-Opencast-Server'),
            tooltip: $gettext('Der ausgewählte Server wird standardmäßig verwendet'),
            input: 'dropdown',
            type: 'integer',
            i18n: false
        },
        OPENCAST_HIDE_EPISODES: {
            label: $gettext('Videos nur für Lehrende sichtbar'),
            tooltip: $gettext('Legt fest, ob neue Videos zunächst ausschließlich Lehrenden angezeigt werden'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_LIST_UNAVAILABLE_VIDEOS: {
            label: $gettext('Nicht verfügbare Videos anzeigen'),
            tooltip: $gettext('Legt fest, ob nicht verfügbare Videos in Videolisten sichtbar sind'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_MANAGE_ALL_OC_EVENTS: {
            label: $gettext('Alle Aufzeichnungen verwalten'),
            tooltip: $gettext('Erlaubt Stud.IP, alle Aufzeichnungen in Opencast zu verwalten und verwaiste zu löschen'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_MEDIADOWNLOAD: {
            label: $gettext('Erlaubnis für Mediendownloads'),
            tooltip: $gettext('Legt fest, welche Nutzergruppen Mediendownloads erhalten'),
            input: 'dropdown',
            type: 'string',
            i18n: false
        },
        OPENCAST_MEDIA_ROLES: {
            label: $gettext('Medienrollen nutzen'),
            tooltip: $gettext('Legt fest, ob die Rollen "Medienadmin" und "Medientutor" für Zugriffsrechte genutzt werden'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_RESOURCE_PROPERTY_ID: {
            label: $gettext('ID für Aufzeichnungseigenschaft'),
            tooltip: $gettext('ID, die angibt, ob ein Raum über Aufzeichnungstechnik verfügt'),
            input: 'text',
            type: 'string',
            i18n: false
        },
        OPENCAST_SHOW_TOS: {
            label: $gettext('Datenschutztext anzeigen'),
            tooltip: $gettext('Lehrende müssen einem Datenschutztext zustimmen, bevor das Plugin verwendet werden kann'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_SUPPORT_EMAIL: {
            label: $gettext('E-Mail-Adresse für technisches Feedback'),
            tooltip: '',
            input: 'text',
            type: 'string',
            i18n: false
        },
        OPENCAST_TOS: {
            label: $gettext('Nutzungsbedingungen'),
            tooltip: $gettext('Terms of Service'),
            input: 'textarea',
            type: 'string',
            i18n: true
        },
        OPENCAST_TUTOR_EPISODE_PERM: {
            label: $gettext('Tutor/innen haben gleiche Rechte wie Lehrende'),
            tooltip: $gettext('Gibt Tutor/innen im Opencast Werkzeug die gleichen Rechte wie Lehrenden'),
            input: 'switch',
            type: 'boolean',
            i18n: false
        },
        OPENCAST_UPLOAD_INFO_TEXT_BODY: {
            label: $gettext('Infotext für Hochladen'),
            tooltip: $gettext('Dieser Text wird auf der Hochladeseite angezeigt'),
            input: 'textarea',
            type: 'string',
            i18n: true
        },
    };

    function mapSetting(setting, useDescriptionAsLabel = false) {
        const base = mapping[setting.name] || {};

        return {
            ...base,
            name: setting.name,
            type: base.type ?? setting.type,
            label: useDescriptionAsLabel ? setting.description || base.label : base.label,
            required: setting.required || false,
            placeholder: setting.placeholder || '',
            tooltip: base.tooltip ?? '',
            options: setting.options || null,
            value: setting.value
        };
    }

    return { mapSetting };
}

import { getCurrentInstance } from 'vue';

export function useFormat() {
    const internalInstance = getCurrentInstance();
    const $gettext = internalInstance.appContext.config.globalProperties.$gettext;

    const formatDuration = (milliseconds) => {
        const sec = Math.floor(milliseconds / 1000);
        const min = Math.floor(sec / 60);
        const h = Math.floor(min / 60);

        let duration = '';
        if (h > 0) {
            duration = `${h}:${String(min % 60).padStart(2, '0')}`;
        } else {
            duration = `${min % 60}`;
        }
        duration += `:${String(sec % 60).padStart(2, '0')}`;
        return duration;
    };
    const formatISODateTime = (isoString) => {
        const date = new Date(isoString.replace(' ', 'T'));
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        };
        return date.toLocaleString(undefined, options);
    };

    const timeAgo = (dateInput) => {
        const now = new Date();
        const date = dateInput instanceof Date ? dateInput : new Date(dateInput);
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return $gettext('vor wenigen Sekunden');

        const intervals = [
            { singular: 'Jahr', plural: 'Jahren', seconds: 31536000 },
            { singular: 'Monat', plural: 'Monaten', seconds: 2592000 },
            { singular: 'Woche', plural: 'Wochen', seconds: 604800 },
            { singular: 'Tag', plural: 'Tagen', seconds: 86400 },
            { singular: 'Stunde', plural: 'Stunden', seconds: 3600 },
            { singular: 'Minute', plural: 'Minuten', seconds: 60 },
        ];

        for (const interval of intervals) {
            const count = Math.floor(seconds / interval.seconds);
            if (count >= 1) {
                return `${$gettext('vor')} ${count} ${internalInstance.appContext.config.globalProperties.$ngettext(
                    interval.singular,
                    interval.plural,
                    count
                )}`;
            }
        }
    };

    return { formatDuration, formatISODateTime, timeAgo };
}

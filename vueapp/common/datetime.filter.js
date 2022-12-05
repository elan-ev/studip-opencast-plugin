import { format } from 'date-fns'
import { de } from 'date-fns/locale'

export default date => {
    if (date === null) {
        return '';
    }

    let mydate = new Date(date);

    if (mydate instanceof Date && !isNaN(mydate)) {
        return format(mydate, "d. MMM, yyyy, HH:ii", { locale: de});
    }

    return false;
};

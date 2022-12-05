var Vue = require('vue');

var UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
var STEP = 1024;

function format(value, power) {
    return (value / Math.pow(STEP, power)).toFixed(2) + UNITS[power];
}

export default filesize => {
    let value = parseFloat(filesize, 10);

    for (var i = 0; i < UNITS.length; i++) {
        if (value < Math.pow(STEP, i)) {
            if (UNITS[i - 1]) {
                return format(value, i - 1);
            }
            return value + UNITS[i];
        }
    }
    
    return format(value, i - 1);
};

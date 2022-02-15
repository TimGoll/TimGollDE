// code from http://jsfiddle.net/sUK45/
export function stringToColor(str) {
    let hash = 0;

    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }

    let colour = '#';

    for (let i = 0; i < 3; i++) {
        let value = (hash >> (i * 8)) & 0xFF;

        colour += ('00' + value.toString(16)).substr(-2);
    }

    return colour;
}

export function getTextColor(bgColor) {
    let r = Number("0x" + bgColor.substring(1, 3));
    let g = Number("0x" + bgColor.substring(3, 5));
    let b = Number("0x" + bgColor.substring(5, 7));

    if (r + g + b < 500) {
        return "#FFFFFF";
    } else {
        return "#000000";
    }
}
let regURL = /\[(.*?)\]\((.*?)\)/g;

export function fixLinks(text) {
    console.log("regex:");
    console.log([...text.matchAll(regURL)]);
}
let regURL = /\[(.*?)\]\((.*?)\)/g;

// this linkfixer is really primitive, but works for now; should probably be improved at some point
export function fixLinks(text, { origin = "", owner = "", repository = "", defautBranch = "master" } = {}) {
    let matches = [...text.matchAll(regURL)];

    matches.forEach(match => {
        let link = match[2].toLowerCase();

        // ignore http/https links
        if (link.startsWith("https://") || link.startsWith("http://") || link.startsWith("//")) {
            return;
        }

        // ignore emails
        if (link.startsWith("mailto:")) {
            return;
        }

        console.log(match);

        // to make sure only the real links are replaced with the fixed links, the surrounding symbols are added
        let substr = "](" + link + ")";
        let replace = "](" + origin + "/" + owner + "/" + repository + "/" + defautBranch + "/" + link + ")";

        text = text.replace(substr, replace);
    });

    return text;
}
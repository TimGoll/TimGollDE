export async function requestCachedFile(relPath) {
    const path = "//" + window.location.hostname + "/src/cache/" + relPath;
    const utf8Decoder = new TextDecoder('utf-8');

    const response = await fetch(path, {
        credentials: "omit"
    })

    const reader = response.body.getReader();

    let content = ""
    let readerData

    do {
        readerData = await reader.read();

        content += readerData.value ? utf8Decoder.decode(readerData.value) : "";
    } while (!readerData.done);

    return content;
}

export async function requestCachedParsedMarkdownFile({ owner = "", repository = "", defautBranch = "master", file = "" } = {}) {
    return await requestCachedFile(owner + "/" + repository + "/" + defautBranch + "/" + file);
}

export async function requestCachedProjectData() {
    return JSON.parse(await requestCachedFile("projects.json"));
}

export async function requestGitHubImageFile({ origin = "", owner = "", repository = "", defautBranch = "master", file = "" } = {}) {
    const path = origin + "/" + owner + "/" + repository + "/" + defautBranch + "/" + file;

    const response = await fetch(path, {
        credentials: "omit"
    })

    if (response.ok) {
        return URL.createObjectURL(await response.blob());
    }
}
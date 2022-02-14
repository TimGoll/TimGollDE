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

export async function requestCachedParsedMarkdownFile({ owner = "", repository = "", defaultBranch = "master", file = "" } = {}) {
    return await requestCachedFile(owner + "/" + repository + "/" + defaultBranch + "/" + file);
}

export async function requestCachedProjectData() {
    let projects = JSON.parse(await requestCachedFile("projects.json"));

    // first: cache timestamp
    for (let i = 0; i < projects.length; i++) {
        let project = projects[i];

        if (project.date == undefined) {
            project.date_abs = 0;

            continue;
        }

        project.date_abs = new Date(project.date).getTime();
    }

    // second: sort by date
    projects.sort(function(a, b) {
        return b.date_abs - a.date_abs;
    });

    return projects
}

export async function requestGitHubImageFile({ origin = "", owner = "", repository = "", defaultBranch = "master", file = "" } = {}) {
    const path = origin + "/" + owner + "/" + repository + "/" + defaultBranch + "/" + file;

    const response = await fetch(path, {
        credentials: "omit"
    })

    if (response.ok) {
        return URL.createObjectURL(await response.blob());
    }
}
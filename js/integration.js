import { Octokit } from "https://cdn.skypack.dev/@octokit/core";

const octokit = new Octokit();

export async function requestGitHubTextFile({ origin = "", owner = "", repository = "", defautBranch = "master", file = "" } = {}) {
    const path = origin + "/" + owner + "/" + repository + "/" + defautBranch + "/" + file;
    const utf8Decoder = new TextDecoder('utf-8');

    const response = await fetch(path, {
        credentials: "omit",
        headers: {
            "Content-Type": "text/plain"
        }
    })

    const reader = response.body.getReader();

    let content = ""
    let readerData

    do {
        readerData = await reader.read();

        content += readerData.value ? utf8Decoder.decode(readerData.value) : "";
    } while (!readerData.done);

    return content
}

export async function requestGitHubImageFile({ origin = "", owner = "", repository = "", defautBranch = "master", file = "" } = {}) {
    const path = origin + "/" + owner + "/" + repository + "/" + defautBranch + "/" + file;

    const response = await fetch(path, {
        credentials: "omit"
    })

    //console.log(response.blob())

    if (response.ok) {
        return URL.createObjectURL(await response.blob());
    }
}

export async function parseMarkdown({ text = "text", mode = "markdown", context = "" } = {}) {
    const response = await octokit.request("POST /markdown", {
        text: text,
        mode: mode,
        context: context
    })

    if (response.status == 200) {
        return response.data
    }
}
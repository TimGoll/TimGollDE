import * as integration from "./integration.js";
import * as linkFixer from "./linkfixer.js";
import DOMBuilder from "./dombuilder.js";

var projects = [];

async function requestImage(name, obj) {
    let image = await integration.requestGitHubImageFile({
        origin: "https://raw.githubusercontent.com",
        owner: "TimGoll",
        repository: "TimGollDE",
        defaultBranch: "master",
        file: "assets/" + name + ".png"
    });

    if (image != undefined) {
        obj.src = image
    }
}

async function requestMarkdownText(data = { origin: "", owner: "", repository: "", defautBranch: "", file: "" }) {
    let markdown = await integration.requestGitHubTextFile(data);

    markdown = linkFixer.fixLinks(markdown, data);

    let html = await integration.parseMarkdown({
        text: markdown,
        mode: "gfm",
        context: data.owner + "/" + data.repository
    });

    return html
}

/** SETUP FUNCTIONS **/

async function setupInfo() {
    let html = await requestMarkdownText({
        origin: "https://raw.githubusercontent.com",
        owner: "TimGoll",
        repository: "TimGoll",
        defautBranch: "main",
        file: "README.md"
    })

    document.getElementById("content").innerHTML = html;
}


async function setupProjects() {
    projects = JSON.parse(await integration.requestGitHubTextFile({
        origin: "https://raw.githubusercontent.com",
        owner: "TimGoll",
        repository: "TimGollDE",
        defaultBranch: "master",
        file: "webcontent/projects.json"
    }));

    let domBuilderProjects = new DOMBuilder(document.getElementById("projects"));

    projects.forEach(project => {
        let domBuilderContent = domBuilderProjects
            .build("div", { class: "mb-3 d-flex flex-content-stretch col-12 col-md-6 col-lg-4" })
            .build("div", { class: "Box of-hidden d-flex width-full project-list-item-item" })
            .build("div", { class: "project-list-item-content" });

        let img = domBuilderContent
            .build("div", { class: "d-flex flex-grow-2 of-hidden img-project" })
            .build("img", { class: "object-fit-cover w-100 h-100", src: "img/no_icon.png" });

        let domBuilderText = domBuilderContent
            .build("div", { class: "d-flex flex-dir-col flex-grow-1 p-3" });

        domBuilderText
            .build("h3", { class: "mt-0", innerHTML: project.name });

        domBuilderText
            .build("p", { innerHTML: project.desc });

        requestImage(project.id, img.lastElement);
    });
}

setupInfo();
setupProjects();
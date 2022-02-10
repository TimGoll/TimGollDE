import * as integration from "./integration.js";
import DOMBuilder from "./dombuilder.js";

var projects = [];

async function requestImage(name, obj) {
    let image = await integration.requestGitHubImageFile({
        origin: "https://raw.githubusercontent.com",
        owner: "TimGoll",
        repository: "pcb_atmega328p",
        defaultBranch: "main",
        file: "assets/config_util.png"
    });

    console.log(image);

    if (image != undefined) {
        document.getElementById("test").src = image
    }
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
        console.log(project)
        let domBuilderContent = domBuilderProjects
            .build("div", { class: "mb-3 d-flex flex-content-stretch col-12 col-md-6 col-lg-4" })
            .build("div", { class: "Box of-hidden d-flex width-full project-list-item-item" })
            .build("div", { class: "project-list-item-content" });

        domBuilderContent
            .build("div", { class: "d-flex flex-grow-2 of-hidden img-project" })
            .build("img", { class: "object-fit-cover w-100 h-100", src: "https://cdn.pixabay.com/photo/2018/08/21/23/29/forest-3622519__340.jpg" });

        let domBuilderText = domBuilderContent
            .build("div", { class: "d-flex flex-dir-col flex-grow-1 p-3" });

        domBuilderText
            .build("h3", { class: "mt-0", innerHTML: project.name });

        domBuilderText
            .build("p", { innerHTML: project.desc });
    });

    //console.log(projects);

    requestImage();
}




setupProjects()





async function setup() {
    let markdown = await integration.requestGitHubTextFile({
        origin: "https://raw.githubusercontent.com",
        owner: "TimGoll",
        repository: "TimGoll",
        file: "README.md"
    });

    let html = await integration.parseMarkdown({
        text: markdown,
        mode: "gfm",
        context: "TimGoll/TimGoll"
    })

    document.getElementById("content").innerHTML = html;

    let dombuider = new DOMBuilder(document.getElementById("content"));

    dombuider
        .build("div", { class: "abc" })
        .build("a", { href: "abc.def", class: "def" });
}

setup();
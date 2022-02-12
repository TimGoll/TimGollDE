import * as integration from "./integration.js";
import DOMBuilder from "./dombuilder.js";

var projects = [];
var lastScrollPos = 0;

async function requestImage(name, obj) {
    let image = await integration.requestGitHubImageFile({
        origin: config.origin,
        owner: config.core.owner,
        repository: config.core.repository,
        defaultBranch: config.core.defaultBranch,
        file: "webcontent/assets/" + name + ".png"
    });

    if (image != undefined) {
        obj.src = image
    }
}

/** SETUP FUNCTIONS **/

async function setupInfo() {
    document.getElementById("bio").innerHTML = await integration.requestCachedParsedMarkdownFile({
        owner: config.bio.owner,
        repository: config.bio.repository,
        defaultBranch: config.bio.defaultBranch,
        file: "README.html"
    })
}


async function setupProjects() {
    projects = await integration.requestCachedProjectData();

    let domBuilderProjects = new DOMBuilder(document.getElementById("projects"));

    for (let i = 0; i < projects.length; i++) {
        let project = projects[i];

        let domBuilderContent = domBuilderProjects
            .build("div", { class: "mb-3 d-flex flex-content-stretch col-12 col-md-6 col-lg-4" })
            .build("div", { class: "Box of-hidden d-flex width-full project-list-item-item" })
            .build("div", { class: "project-list-item-content", project: i });

        let img = domBuilderContent
            .build("div", { class: "d-flex flex-grow-2 of-hidden img-project" })
            .build("img", { class: "object-fit-cover w-100 h-100", src: "src/img/no_icon.png" });

        let domBuilderText = domBuilderContent
            .build("div", { class: "d-flex flex-dir-col flex-grow-1 p-3" });

        domBuilderText
            .build("h3", { class: "mt-0", innerHTML: project.name });

        domBuilderText
            .build("p", { innerHTML: project.desc });

        domBuilderContent.lastElement.addEventListener("click", openProject);

        requestImage(project.id, img.lastElement);
    }
}

async function openProject() {
    let num = parseInt(this.getAttribute("project"));

    // cache the last scroll position and reset the scroll pos to 0
    lastScrollPos = window.scrollY;
    window.scroll(0, 0);

    // hide landing page
    document.getElementById("landing").setAttribute("style", "display: none;")

    // unhide popup
    document.getElementById("popup").setAttribute("style", "display: block; min-height: 100%;")

    let project = projects[num];

    // populate
    document.getElementById("project-title").innerHTML = project.name;

    if (project.repo_based == true) {
        document.getElementById("project-text").innerHTML = await integration.requestCachedParsedMarkdownFile({
            owner: project.owner,
            repository: project.id,
            defautBranch: project.default_branch,
            file: "README.html"
        });
    } else {
        document.getElementById("project-text").innerHTML = await integration.requestCachedParsedMarkdownFile({
            owner: config.core.owner,
            repository: config.core.repository,
            defaultBranch: config.core.defaultBranch,
            file: project.id + ".html"
        });
    }
}

function closeProject() {
    document.getElementById("project-title").innerHTML = "";
    document.getElementById("project-text").innerHTML = "";

    document.getElementById("landing").setAttribute("style", "display: block;");
    document.getElementById("popup").setAttribute("style", "display: none;");
    window.scroll(0, lastScrollPos);
}

window.addEventListener("load", function() {
    document.getElementById("button-close").addEventListener("mouseup", closeProject);
})

window.addEventListener('keyup', function(e) {
    if (e.defaultPrevented) {
        return;
    }

    var key = e.key || e.keyCode;

    if (key === 'Escape' || key === 'Esc' || key === 27) {
        closeProject();
    }
});

setupInfo();
setupProjects();
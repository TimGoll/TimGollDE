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

function setupCore() {
    window.document.title = config.title;
}

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

    // after the project table is available, the page should be set
    setPage(window.location.pathname.substr(1));

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

async function openProject(_, project_id, preventStatePush) {
    // if a specific project should be opened this parameter is set
    let num = -1;

    if (project_id !== undefined) {
        for (let i = 0; i < projects.length; i++) {
            let project = projects[i];

            if (project.id == project_id) {
                num = i;

                break;
            }
        }

        // if the project id wasn't found, redirect to landing page
        if (num == -1) {
            closeProject();

            return;
        }
    } else {
        num = parseInt(this.getAttribute("project"));
    }


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
            defaultBranch: project.default_branch,
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

    if (!preventStatePush) {
        window.history.pushState({}, "", project.id);
    }
    window.document.title = config.title + " // " + project.name;
}

function closeProject(_, preventStatePush) {
    document.getElementById("project-title").innerHTML = "";
    document.getElementById("project-text").innerHTML = "";

    document.getElementById("landing").setAttribute("style", "display: block;");
    document.getElementById("popup").setAttribute("style", "display: none;");
    window.scroll(0, lastScrollPos);

    if (!preventStatePush) {
        window.history.pushState({}, "", "/");
    }
    window.document.title = config.title;
}

function setPage(project_id, preventStatePush) {
    if (project_id != "") {
        openProject(undefined, project_id, preventStatePush);
    } else {
        closeProject(undefined, preventStatePush);
    }
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

//catch history change events
window.onpopstate = function() {
    setPage(window.location.pathname.substr(1), true);

    // hacky solution to guarantee that the scrolling is reset
    window.setTimeout(function() {
        window.scroll(0, lastScrollPos);
    }, 0);
};

setupCore();
setupInfo();
setupProjects();
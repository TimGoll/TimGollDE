// all pages are prerendered by the cache rebuild and work without JavaScript, this script only
// adds the theme toggle, the topic filter and the highlighting in the table of contents

/** THEME **/

const darkQuery = window.matchMedia("(prefers-color-scheme: dark)");

function currentTheme() {
    return document.documentElement.dataset.theme || (darkQuery.matches ? "dark" : "light");
}

function setupThemeToggle() {
    for (const button of document.querySelectorAll(".theme-toggle")) {
        button.hidden = false;

        button.addEventListener("click", function() {
            const theme = currentTheme() === "dark" ? "light" : "dark";

            document.documentElement.dataset.theme = theme;

            // the choice is remembered, from now on the system setting is ignored
            try {
                localStorage.setItem("theme", theme);
            } catch (e) {}
        });
    }
}

/** PROJECT LIST **/

// every sort key starts with its natural order, the button reverses it
const SORT_ORDERS = { date: "desc", name: "asc", commits: "desc" };

// compares two cards, projects without a value are always at the end of the list
function compareCards(a, b, key, order) {
    const valueA = a.dataset[key];
    const valueB = b.dataset[key];

    if (valueA === "" || valueB === "") {
        return (valueA === "") - (valueB === "");
    }

    let result;

    if (key === "name") {
        result = valueA.localeCompare(valueB, undefined, { sensitivity: "base", numeric: true });
    } else if (key === "commits") {
        result = Number(valueA) - Number(valueB);
    } else {
        result = valueA < valueB ? -1 : (valueA > valueB ? 1 : 0);
    }

    return order === "asc" ? result : -result;
}

function setupProjectList() {
    const filter = document.querySelector(".topic-filter");

    if (!filter) {
        return;
    }

    const chips = [...filter.querySelectorAll(".chip")];
    const grid = document.querySelector(".project-grid");
    const cards = [...grid.querySelectorAll(".card")];
    const count = document.querySelector(".project-count");
    const empty = document.querySelector(".project-empty");
    const sortKey = document.querySelector(".sort-key");
    const sortOrder = document.querySelector(".sort-order");

    // the selection and the order are kept in the url, this way they survive going back and can be shared
    const params = new URLSearchParams(window.location.search);
    const selected = new Set(params.getAll("topic"));

    let key = Object.hasOwn(SORT_ORDERS, params.get("sort") ?? "") ? params.get("sort") : "date";
    let order = params.get("order") === "asc" || params.get("order") === "desc" ? params.get("order") : SORT_ORDERS[key];

    function sort() {
        sortKey.value = key;
        sortOrder.dataset.order = order;
        sortOrder.setAttribute("aria-label", order === "asc"
            ? "Sort ascending, switch to descending"
            : "Sort descending, switch to ascending");

        // the original position keeps the order of equal cards stable
        const sorted = cards
            .map((card, i) => [card, i])
            .sort((a, b) => compareCards(a[0], b[0], key, order) || a[1] - b[1])
            .map((entry) => entry[0]);

        grid.append(...sorted);
    }

    function update() {
        for (const chip of chips) {
            const topic = chip.dataset.topic;

            chip.setAttribute("aria-pressed", String(topic === "" ? selected.size === 0 : selected.has(topic)));
        }

        // a project is shown if it has all of the selected topics
        let visible = 0;

        for (const card of cards) {
            const topics = JSON.parse(card.dataset.topics);
            const show = [...selected].every((topic) => topics.includes(topic));

            card.hidden = !show;
            visible += show ? 1 : 0;
        }

        count.textContent = visible + " / " + cards.length;
        empty.hidden = visible > 0;

        // topics that no visible project has would lead to an empty list, they are disabled,
        // selected topics always stay clickable so that they can be removed again
        const available = new Set(cards.filter((card) => !card.hidden).flatMap((card) => JSON.parse(card.dataset.topics)));

        for (const chip of chips) {
            const topic = chip.dataset.topic;

            chip.disabled = topic !== "" && !selected.has(topic) && !available.has(topic);
        }

        const query = new URLSearchParams();

        for (const topic of selected) {
            query.append("topic", topic);
        }

        // the default order doesn't need to be in the url
        if (key !== "date" || order !== SORT_ORDERS.date) {
            query.set("sort", key);
            query.set("order", order);
        }

        const search = query.toString();

        window.history.replaceState({}, "", window.location.pathname + (search ? "?" + search : "") + window.location.hash);
    }

    sortKey.addEventListener("change", function() {
        key = sortKey.value;
        order = SORT_ORDERS[key];

        sort();
        update();
    });

    sortOrder.addEventListener("click", function() {
        order = order === "asc" ? "desc" : "asc";

        sort();
        update();
    });

    filter.addEventListener("click", function(e) {
        const chip = e.target.closest(".chip");

        if (!chip) {
            return;
        }

        const topic = chip.dataset.topic;

        if (topic === "") {
            selected.clear();
        } else if (selected.has(topic)) {
            selected.delete(topic);
        } else {
            selected.add(topic);
        }

        update();
    });

    // topics that don't exist anymore are ignored
    for (const topic of [...selected]) {
        if (!chips.some((chip) => chip.dataset.topic === topic)) {
            selected.delete(topic);
        }
    }

    filter.hidden = false;
    document.querySelector(".sort-controls").hidden = false;
    document.querySelector(".sort-static").hidden = true;

    sort();
    update();
}

/** TABLE OF CONTENTS **/

function setupTableOfContents() {
    const links = [...document.querySelectorAll(".toc a")];

    if (links.length === 0) {
        return;
    }

    const headings = links.map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1)))).filter(Boolean);

    // the current section is the last heading that was scrolled past the top of the window
    function update() {
        let current = 0;

        headings.forEach(function(heading, i) {
            if (heading.getBoundingClientRect().top < 120) {
                current = i;
            }
        });

        // the last sections can't reach the top of the window, at the end of the page the last visible one is selected
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
            headings.forEach(function(heading, i) {
                if (heading.getBoundingClientRect().top < window.innerHeight) {
                    current = i;
                }
            });
        }

        links.forEach(function(link, i) {
            if (i === current) {
                link.setAttribute("aria-current", "true");
            } else {
                link.removeAttribute("aria-current");
            }
        });
    }

    window.addEventListener("scroll", update, { passive: true });
    update();
}

setupThemeToggle();
setupProjectList();
setupTableOfContents();

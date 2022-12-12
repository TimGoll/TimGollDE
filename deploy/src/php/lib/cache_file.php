<?php

function cache_file($project, $config) {
    echolog("started caching " . $project["id"], 2);

    if ($project["repo_based"]) {
        echolog("project is repository based", 3);

        $readme_path = $config["raw_base"]
            . $project["owner"] . "/"
            . $project["id"] . "/"
            . $project["default_branch"] . "/README.md";
    } else {
        echolog("project is only a markdown file", 3);

        $readme_path = $config["raw_base"]
            . $config["core"]["owner"] . "/"
            . $config["core"]["repository"] . "/"
            . $config["core"]["default_branch"] . "/webcontent/markdown/"
            . $project["id"] . ".md";
    }

    $markdown = request_get_file_contents($readme_path);

    echolog("fetched file from server, starting link fixing", 2);

    preg_match_all("/\[(.*?)\]\((.*?)\)/", $markdown, $found_links, PREG_OFFSET_CAPTURE);

    echolog("found " . count($found_links[2]) . " links in document, some may need fixing", 2);

    // iterate backwards over text so that the link fixed do not afect the other links
    for ($i = count($found_links[2]) - 1; $i >= 0; $i--) {
        $link = strtolower($found_links[2][$i][0]);
        $start = $found_links[2][$i][1];
        $length = strlen($link);

        echolog($i . ". " . $link, 3);

        // ignore links that point to external sources
        if (str_starts_with($link, "https://") or str_starts_with($link, "http://") or str_starts_with($link, "//")) {
            echolog("link is external link that doesn't need fixing, continuing", 4);

            continue;
        }

        // ignore links that are email links
        if (str_starts_with($link, "mailto:")) {
            echolog("link is e-mail adress that doesn't need fixing, continuing", 4);

            continue;
        }

        if ($project["repo_based"]) {
            $new_link = $config["raw_base"]
                . $project["owner"] . "/"
                . $project["id"] . "/"
                . $project["default_branch"] . "/"
                . $link;
        } else {
            $new_link = $config["raw_base"]
                . $config["core"]["owner"] . "/"
                . $config["core"]["repository"] . "/"
                . $config["core"]["default_branch"] . "/webcontent/assets/"
                . $link;
        }

        echolog("link is fixed: " . $new_link, 4);

        $markdown = substr($markdown, 0, $start) . $new_link . substr($markdown, $start + $length);
    }

    echolog("requesting translated markdown from GitHub server via API call", 2);

    // store file as cache
    if ($project["repo_based"]) {
        $html_path = "../cache/"
            . $project["owner"] . "/"
            . $project["id"] . "/"
            . $project["default_branch"];
        $html_file = $html_path . "/README.html";

        $html = request_markdown($config["api_key"], $markdown, "gfm", $project["owner"] . "/" . $project["id"])["result"];
    } else {
        $html_path = "../cache/"
            . $config["core"]["owner"] . "/"
            . $config["core"]["repository"] . "/"
            . $config["core"]["default_branch"];
        $html_file = $html_path . "/" . $project["id"] . ".html";

        $html = request_markdown($config["api_key"], $markdown, "gfm", $config["core"]["owner"] . "/" . $project["id"])["result"];
    }

    echolog("received translated markdown file from server", 2);

    if (!is_dir($html_path)) {
        mkdir($html_path, 0777, true);
    }
    file_put_contents($html_file, $html);

    echolog("stored file in cache on server", 2);
}

?>
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

    // find all codeboxes to make sure links are not fixed in them
    preg_match_all("/```[^```]*```|`[^`]*`/", $markdown, $found_codeboxes_raw, PREG_OFFSET_CAPTURE);

    print_r($found_codeboxes_raw);

    // sanetize results
    $found_codeboxes = [];

    foreach ($found_codeboxes_raw[0] as $key => $value) {
        array_push($found_codeboxes, [
            "start" => $value[1],
            "end" => $value[1] + strlen($value[0])
        ]);
    }

    // find all link types that may need fixing
    preg_match_all("/(?<!\!)\[(?:.*?)\]\((?<url>.*?)\)|\[(?:.*?)\]\((?<iurl>.*?)\)|src\s*=\s*\"(?<src>[^\"]+)\"|href\s*=\s*\"(?<href>[^\"]+)\"/", $markdown, $found_links, PREG_OFFSET_CAPTURE);
    $found_links = array_filter($found_links, "is_string", ARRAY_FILTER_USE_KEY); // discard numeric indeces

    echolog("found " . count($found_links["url"]) . " links in document, some may need fixing", 2);

    // iterate backwards over text so that the link fixed do not afect the other links
    for ($i = count($found_links["url"]) - 1; $i >= 0; $i--) {
        // first determine the link type (markdown url, img src, a href)
        if ($found_links["url"][$i][1] != -1) {
            $type = "url";
            $is_image = false;
        } elseif ($found_links["iurl"][$i][1] != -1) {
            $type = "iurl";
            $is_image = true;
        } elseif ($found_links["src"][$i][1] != -1) {
            $type = "src";
            $is_image = true;
        } else {
            $type = "href";
            $is_image = false;
        }

        $link = $found_links[$type][$i][0];
        $link_lower = strtolower($link);
        $start = $found_links[$type][$i][1];
        $length = strlen($link);

        echolog($i . ". " . $link, 3);

        // check if link is within a codebox
        $is_in_codebox = false;
        
        foreach ($found_codeboxes as $key => $value) {
            if ($start >= $value["start"] && $start <= $value["end"]) {
                $is_in_codebox = true;

                break;
            }
        }

        if ($is_in_codebox) {
            echolog("link is inside a codebox and therefore doesn't need fixing, continuing", 4);

            continue;
        }

        // ignore links that point to external sources
        if (str_starts_with($link_lower, "https://") or str_starts_with($link_lower, "http://") or str_starts_with($link_lower, "//")) {
            echolog("link is external link that doesn't need fixing, continuing", 4);

            continue;
        }

        // ignore links that are email links
        if (str_starts_with($link_lower, "mailto:")) {
            echolog("link is e-mail adress that doesn't need fixing, continuing", 4);

            continue;
        }

        // build base
        // we want to use the raw file for images, the link to the repo for normal links
        if ($is_image) {
            $base = $config["raw_base"];
        } else {
            $base = $config["file_base"];
        }

        if ($project["repo_based"]) {
            $new_link = $base
                . $project["owner"] . "/"
                . $project["id"] . "/"
                . ($is_image ? "" : "blob/" )
                . $project["default_branch"] . "/"
                . $link;
        } else {
            $new_link = $base
                . $config["core"]["owner"] . "/"
                . $config["core"]["repository"] . "/"
                . $config["core"]["default_branch"]
                . ($is_image ? "" : "blob/")
                . "/webcontent/assets/"
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
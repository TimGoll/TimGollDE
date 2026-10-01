<?php

// throws a RuntimeException if the file can't be fetched, translated or stored
function cache_file($project, $config, $cache_dir) {
    echolog("started caching " . $project["id"], 2);

    if ($project["repo_based"]) {
        echolog("project is repository based", 3);

        $readme_path = $config["raw_base"]
            . $project["owner"] . "/"
            . $project["id"] . "/"
            . $project["default_branch"] . "/README.md";
        $html_path = $cache_dir . "/"
            . $project["owner"] . "/"
            . $project["id"] . "/"
            . $project["default_branch"];
        $html_file = $html_path . "/README.html";
        $context = $project["owner"] . "/" . $project["id"];
    } else {
        echolog("project is only a markdown file", 3);

        $readme_path = $config["raw_base"]
            . $config["core"]["owner"] . "/"
            . $config["core"]["repository"] . "/"
            . $config["core"]["default_branch"] . "/webcontent/markdown/"
            . $project["id"] . ".md";
        $html_path = $cache_dir . "/"
            . $config["core"]["owner"] . "/"
            . $config["core"]["repository"] . "/"
            . $config["core"]["default_branch"];
        $html_file = $html_path . "/" . $project["id"] . ".html";
        $context = $config["core"]["owner"] . "/" . $project["id"];
    }

    $markdown = request_get_file_contents($readme_path);

    if ($markdown === FALSE) {
        throw new RuntimeException("failed to fetch " . $readme_path);
    }

    echolog("fetched file from server", 2);

    // the markdown API rejects empty texts, an empty file is simply an empty page
    if ($markdown === "") {
        echolog("file is empty, nothing to translate", 2);

        $html = "";
    } else {
        echolog("requesting translated markdown from GitHub server via API call", 2);

        $response = request_markdown($config["api_key"], $markdown, "gfm", $context);

        if ($response["status"] != 200) {
            throw new RuntimeException("failed to translate markdown of " . $project["id"] . " (HTTP " . $response["status"] . "): " . $response["result"]);
        }

        echolog("received translated markdown file from server", 2);

        // links are fixed in the rendered html, this way code blocks are skipped automatically
        // because their content is text and not a link element
        $dom = html_load($response["result"]);

        fix_links($dom, $project, $config);
        mirror_images($dom, $cache_dir);
        add_heading_ids($dom);

        $html = html_save($dom);
    }

    if (!is_dir($html_path) and !mkdir($html_path, 0777, true)) {
        throw new RuntimeException("failed to create folder " . $html_path);
    }

    if (file_put_contents($html_file, $html) === FALSE) {
        throw new RuntimeException("failed to write " . $html_file);
    }

    echolog("stored file in cache on server", 2);

    return $html;
}

// the markdown API returns headings without ids, they are added the same way GitHub does it,
// this way links to sections inside the document and the table of contents work
function add_heading_ids($dom) {
    $used = [];

    foreach ((new DOMXPath($dom))->query("//h1|//h2|//h3|//h4|//h5|//h6") as $heading) {
        $slug = strtolower(trim($heading->textContent));
        $slug = preg_replace("/[^\p{L}\p{N}\s_-]/u", "", $slug);
        $slug = preg_replace("/\s/u", "-", $slug);

        if ($slug === "" or $slug === null) {
            $slug = "section";
        }

        // duplicate headings get a number, like on GitHub
        $id = $slug;

        for ($i = 1; array_key_exists($id, $used); $i++) {
            $id = $slug . "-" . $i;
        }

        $used[$id] = true;
        $heading->setAttribute("id", $id);
    }
}

function fix_links($dom, $project, $config) {
    echolog("starting link fixing", 2);

    // collect all links first, GitHub wraps images in a link to the image itself,
    // those links should point to the raw image as well
    $found_links = [];

    foreach ($dom->getElementsByTagName("a") as $element) {
        $is_image = false;

        foreach ($element->getElementsByTagName("img") as $img) {
            if ($img->getAttribute("src") === $element->getAttribute("href")) {
                $is_image = true;

                break;
            }
        }

        array_push($found_links, [$element, "href", $is_image]);
    }

    foreach ($dom->getElementsByTagName("img") as $element) {
        array_push($found_links, [$element, "src", true]);
    }

    echolog("found " . count($found_links) . " links in document, some may need fixing", 2);

    foreach ($found_links as $i => [$element, $attribute, $is_image]) {
        $link = $element->getAttribute($attribute);

        echolog($i . ". " . $link, 3);

        // ignore empty links and anchors inside of the document
        if ($link === "" or str_starts_with($link, "#")) {
            echolog("link is an anchor that doesn't need fixing, continuing", 4);

            continue;
        }

        // ignore links that point to external sources or use a scheme such as mailto:
        if (str_starts_with($link, "//") or preg_match("/^[a-z][a-z0-9+.-]*:/i", $link)) {
            echolog("link is absolute link that doesn't need fixing, continuing", 4);

            continue;
        }

        $new_link = build_link($link, $is_image, $project, $config);

        echolog("link is fixed: " . $new_link, 4);

        $element->setAttribute($attribute, $new_link);
    }
}

function build_link($link, $is_image, $project, $config) {
    // we want to use the raw file for images, the link to the repo for normal links
    if ($is_image) {
        $base = $config["raw_base"];
    } else {
        $base = $config["file_base"];
    }

    if ($project["repo_based"]) {
        return $base
            . $project["owner"] . "/"
            . $project["id"] . "/"
            . ($is_image ? "" : "blob/" )
            . $project["default_branch"] . "/"
            . $link;
    }

    return $base
        . $config["core"]["owner"] . "/"
        . $config["core"]["repository"] . "/"
        . ($is_image ? "" : "blob/")
        . $config["core"]["default_branch"]
        . "/webcontent/assets/"
        . $link;
}

?>
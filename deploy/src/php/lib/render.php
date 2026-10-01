<?php

const PAGE_FOLDER = "pages";

// writes the landing page and a page for every project into the cache
function render_pages($project_list, $project_html, $bio, $config, $cache_dir) {
    $site = $config["site"];

    // the newest projects are shown first, projects without a date at the end
    usort($project_list, function($a, $b) {
        return project_timestamp($b) <=> project_timestamp($a);
    });

    $visible = array_values(array_filter($project_list, function($project) {
        return empty($project["hidden"]);
    }));

    assign_topic_hues(topic_list($visible));

    $common = [
        "site" => $site,
        "core" => $config["core"],
        "github_url" => $config["file_base"] . $config["bio"]["owner"],
        "has_legal" => in_array("legal", array_column($project_list, "id"), true),
        "build_date" => gmdate("Y-m-d")
    ];

    write_page($cache_dir . "/" . PAGE_FOLDER . "/index.html", render_template("layout.php", $common + [
        "title" => $site["title"],
        "og_title" => $site["title"],
        "description" => $site["description"],
        "url" => $site["url"] . "/",
        "image" => null,
        "is_project" => false,
        "content" => render_template("landing.php", $common + [
            "bio" => $bio,
            "projects" => $visible,
            "topics" => topic_list($visible),
            "topic_mix" => topic_mix($visible)
        ])
    ]));

    echolog("rendered landing page", 2);

    foreach ($project_list as $project) {
        $html = $project_html[$project["id"]];

        write_page($cache_dir . "/" . PAGE_FOLDER . "/" . $project["id"] . "/index.html", render_template("layout.php", $common + [
            "title" => $site["title"] . " // " . $project["name"],
            "og_title" => $project["name"],
            "description" => $project["description"],
            "url" => $site["url"] . "/" . $project["id"],
            "image" => $project["preview"] === null ? null : $site["url"] . $project["preview"]["url"],
            "is_project" => true,
            "content" => render_template("project.php", $common + [
                "project" => $project,
                "html" => $html,
                "toc" => table_of_contents($html)
            ])
        ]));

        echolog("rendered page of " . $project["id"], 2);
    }
}

function render_template($template, $variables) {
    extract($variables);

    ob_start();
    include(__DIR__ . "/../templates/" . $template);

    return ob_get_clean();
}

function write_page($file, $html) {
    if (!is_dir(dirname($file)) and !mkdir(dirname($file), 0777, true)) {
        throw new RuntimeException("failed to create folder " . dirname($file));
    }

    if (file_put_contents($file, $html) === FALSE) {
        throw new RuntimeException("failed to write " . $file);
    }
}

// the link to the repository of a project, empty if it has none
function project_repo($project) {
    return $project["repo_based"] ? $project["owner"] . "/" . $project["id"] : $project["source"];
}

// "ATMega 328pb Breakout" -> "AB", "TTT2" -> "T2", shown on projects without a preview image
function project_initials($name) {
    $words = preg_split("/[\s_-]+/u", trim($name), -1, PREG_SPLIT_NO_EMPTY);

    if (count($words) === 0) {
        return "";
    }

    if (count($words) === 1) {
        $chars = preg_split("//u", $words[0], -1, PREG_SPLIT_NO_EMPTY);

        return strtoupper($chars[0] . (count($chars) > 1 ? end($chars) : ""));
    }

    return strtoupper(preg_split("//u", $words[0], -1, PREG_SPLIT_NO_EMPTY)[0] . preg_split("//u", end($words), -1, PREG_SPLIT_NO_EMPTY)[0]);
}

// the number of projects per topic, the most used ones first
function topic_counts($projects) {
    $count = [];

    foreach ($projects as $project) {
        foreach ($project["topics"] as $topic) {
            $count[$topic] = ($count[$topic] ?? 0) + 1;
        }
    }

    uksort($count, function($a, $b) use ($count) {
        return [$count[$b], $a] <=> [$count[$a], $b];
    });

    return $count;
}

function topic_list($projects) {
    return array_keys(topic_counts($projects));
}

// the most used topics for the "what I build" bar
function topic_mix($projects, $limit = 8) {
    return array_slice(topic_counts($projects), 0, $limit, true);
}

// the sections of a project page, taken from its second level headings
function table_of_contents($html) {
    if ($html === "") {
        return [];
    }

    $toc = [];

    foreach ((new DOMXPath(html_load($html)))->query("//h2[@id]") as $heading) {
        array_push($toc, ["id" => $heading->getAttribute("id"), "text" => trim($heading->textContent)]);
    }

    // a single section is not worth a table of contents
    return count($toc) > 1 ? $toc : [];
}

// the website address without the protocol, e.g. ["timgoll", ".de"] for the logo
function site_name($site) {
    $host = parse_url($site["url"], PHP_URL_HOST);

    if (!$host) {
        return [$site["title"], ""];
    }

    $dot = strrpos($host, ".");

    return $dot === false ? [$host, ""] : [substr($host, 0, $dot), substr($host, $dot)];
}

function project_timestamp($project) {
    $timestamp = empty($project["date"]) ? FALSE : strtotime($project["date"]);

    return $timestamp === FALSE ? 0 : $timestamp;
}

// e.g. 2023-11-01, empty if there is no date
function format_date($date) {
    $timestamp = empty($date) ? FALSE : strtotime($date);

    return $timestamp === FALSE ? "" : gmdate("Y-m-d", $timestamp);
}

// every topic gets its own hue, light and dark mode use a fixed lightness so that the text stays readable,
// the topics are ordered by their use and each hue is a golden angle away from the previous one, this way
// the most used topics, which are shown next to each other in the "what I build" bar, are easy to tell apart
function assign_topic_hues($topics) {
    topic_hue(null, $topics);
}

function topic_hue($topic, $ranking = null) {
    static $hues = [];

    if ($ranking !== null) {
        $hues = [];

        foreach (array_values($ranking) as $i => $ranked_topic) {
            $hues[$ranked_topic] = (int) round(fmod(210 + $i * 137.508, 360));
        }

        return null;
    }

    return $hues[$topic] ?? topic_hash_hue($topic);
}

// fallback for topics that weren't ranked, calculated the same way as in the design
function topic_hash_hue($topic) {
    $hash = 0;

    foreach (utf16_code_units($topic) as $code) {
        $hash = to_int32($code + (to_int32($hash << 5) - $hash));
    }

    return abs($hash) % 360;
}

function to_int32($value) {
    $value = $value & 0xFFFFFFFF;

    return $value >= 0x80000000 ? $value - 0x100000000 : $value;
}

// JavaScript strings consist of UTF-16 code units, the hue is calculated the same way as in the design
function utf16_code_units($string) {
    $units = [];
    $chars = preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);

    // invalid UTF-8, fall back to the single bytes
    if ($chars === FALSE) {
        return array_values(unpack("C*", $string));
    }

    foreach ($chars as $char) {
        $bytes = array_values(unpack("C*", $char));

        switch (count($bytes)) {
            case 1: $code = $bytes[0]; break;
            case 2: $code = (($bytes[0] & 0x1F) << 6) | ($bytes[1] & 0x3F); break;
            case 3: $code = (($bytes[0] & 0x0F) << 12) | (($bytes[1] & 0x3F) << 6) | ($bytes[2] & 0x3F); break;
            default: $code = (($bytes[0] & 0x07) << 18) | (($bytes[1] & 0x3F) << 12) | (($bytes[2] & 0x3F) << 6) | ($bytes[3] & 0x3F);
        }

        // characters outside of the basic plane are stored as surrogate pairs
        if ($code > 0xFFFF) {
            $code -= 0x10000;

            array_push($units, 0xD800 + ($code >> 10), 0xDC00 + ($code & 0x3FF));
        } else {
            array_push($units, $code);
        }
    }

    return $units;
}

?>

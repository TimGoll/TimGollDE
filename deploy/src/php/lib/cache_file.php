<?php

function cache_file($project, $config) {
	if ($project["repo_based"]) {
		$readme_path = $config["raw_base"]
			. $project["owner"] . "/"
			. $project["id"] . "/"
			. $project["default_branch"] . "/README.md";
	} else {
		$readme_path = $config["raw_base"]
			. $config["owner"] . "/"
			. $config["repository"] . "/"
			. $config["default_branch"] . "/webcontent/markdown/"
			. $project["id"] . ".md";
	}

	$markdown = file_get_contents($readme_path);

	preg_match_all("/\[(.*?)\]\((.*?)\)/", $markdown, $found_links, PREG_OFFSET_CAPTURE);

	// iterate backwards over text so that the link fixed do not afect the other links
	for ($i = count($found_links[2]) - 1; $i >= 0; $i--) {
		$link = strtolower($found_links[2][$i][0]);
		$start = $found_links[2][$i][1];
		$length = strlen($link);

		// ignore links that point to external sources
		if (str_starts_with($link, "https://") or str_starts_with($link, "http://") or str_starts_with($link, "//")) {
			continue;
		}

		// ignore links that are email links
		if (str_starts_with($link, "mailto:")) {
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
				. $config["owner"] . "/"
				. $config["repository"] . "/"
				. $config["default_branch"] . "/webcontent/assets/"
				. $link;
		}

		$markdown = substr($markdown, 0, $start) . $new_link . substr($markdown, $start + $length);
	}

	// store file as cache
	if ($project["repo_based"]) {
		$html_path = "../cache/"
			. $project["owner"] . "/"
			. $project["id"] . "/"
			. $project["default_branch"];
		$html_file = $html_path . "/README.html";

		$html = request_markdown($config["owner"], $config["api_key"], $markdown, "gfm", $project["owner"] . "/" . $project["id"]);
	} else {
		$html_path = "../cache/"
			. $config["owner"] . "/"
			. $config["repository"] . "/"
			. $config["default_branch"];
		$html_file = $html_path . "/" . $project["id"] . ".html";

		$html = request_markdown($config["owner"], $config["api_key"], $markdown, "gfm", $config["owner"] . "/" . $project["id"]);
	}

	mkdir($html_path, 0777, true);
	file_put_contents($html_file, $html);
}

?>
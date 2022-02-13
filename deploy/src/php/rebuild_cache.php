<?php
    include_once("config.php");
    include_once("lib/api.php");
    include_once("lib/r_rmdir.php");
    include_once("lib/cache_file.php");

    // clear cache
    if (is_dir("../cache")) {
        r_rmdir("../cache");
    }
    mkdir("../cache/");

    $project_path = $config["raw_base"]
        . $config["core"]["owner"] . "/"
        . $config["core"]["repository"] . "/"
        . $config["core"]["default_branch"] . "/webcontent/projects.json";

    // get a list of all projects that should be displayed on the website
    $project_list = json_decode(file_get_contents($project_path), true);

    // write project list to cache as well
    file_put_contents("../cache/projects.json", json_encode($project_list));

    // iterate over all projects, request their markdown files, fix the links and translate to HTML
    foreach ($project_list as $project) {
        cache_file($project, $config);
    }

    // also cache the main bio
    cache_file(array(
        "owner" => $config["bio"]["owner"],
        "id" => $config["bio"]["repository"],
        "default_branch" => $config["bio"]["default_branch"],
        "repo_based" => true
    ), $config);
?>
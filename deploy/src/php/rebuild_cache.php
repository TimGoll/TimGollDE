<pre>

<?php
    include_once("config.php");
    include_once("lib/api.php");
    include_once("lib/r_rmdir.php");
    include_once("lib/echo_log.php");
    include_once("lib/cache_file.php");

    echolog("Started rebuilding the website cache, this may take a while...", 0);
    echo("\n");

    // clear cache
    if (is_dir("../cache")) {
        r_rmdir("../cache");
    }
    mkdir("../cache/");

    echolog("deleted old cache folder", 1);

    $project_path = $config["raw_base"]
        . $config["core"]["owner"] . "/"
        . $config["core"]["repository"] . "/"
        . $config["core"]["default_branch"] . "/webcontent/projects.json";

    // get a list of all projects that should be displayed on the website
    $project_list = json_decode(file_get_contents($project_path), true);

    echolog("fetched projects.json file", 1);

    echolog("updating project data with data from GitHub", 1);

    // we iterate over the whole list to add the date and tags to the projects
    for ($i = 0; $i < count($project_list); $i++) {
        $project = $project_list[$i];

        if (!$project["repo_based"]) {
            continue;
        }

        echolog("repo based project: " . $project["id"], 2);

        // only request data if at least one of the data points is missing
        if (array_key_exists("date", $project) and array_key_exists("topics", $project)) {
            continue;
        }

        $repo_data = json_decode(request_repo_data($config["api_key"], $project["owner"] . "/" . $project["id"]), true);

        // only add date if not set manually
        if (!array_key_exists("date", $project)) {
            $project_list[$i]["date"] = $repo_data["created_at"];

            echolog("added creation date", 3);
        }

        // only add tags if not set manually
        if (!array_key_exists("topics", $project)) {
            $project_list[$i]["topics"] = $repo_data["topics"];

            echolog("added tag list", 3);
        }
    }

    echolog("finished updating project data with data from GitHub", 1);

    // write project list to cache as well
    file_put_contents("../cache/projects.json", json_encode($project_list));

    echolog("stored updated projects file in cache", 1);

    echolog("starting to fetch project contents", 1);

    // iterate over all projects, request their markdown files, fix the links and translate to HTML
    foreach ($project_list as $project) {
        cache_file($project, $config);
    }

    echolog("finished fetching all projects", 1);

    echolog("caching bio page", 1);

    // also cache the main bio
    cache_file(array(
        "owner" => $config["bio"]["owner"],
        "id" => $config["bio"]["repository"],
        "default_branch" => $config["bio"]["default_branch"],
        "repo_based" => true
    ), $config);

    echolog("finished caching bio page", 1);
    echo("\n");

    echolog("Finished caching website", 0);
?>

</pre>
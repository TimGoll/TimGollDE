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
    $project_list = json_decode(request_get_file_contents($project_path), true);

    echolog("fetched projects.json file", 1);

    echolog("updating project data with data from GitHub", 1);

    // we iterate over the whole list to add the date and tags to the projects
    for ($i = 0; $i < count($project_list); $i++) {
        $project = $project_list[$i];
        $repo_name = "";

        if (!$project["repo_based"] and !array_key_exists("source", $project)) {
            continue;
        }

        if ($project["repo_based"]) {
            $repo_name = $project["owner"] . "/" . $project["id"];
        } else {
            $repo_name = $project["source"];
        }

        echolog("repo based project: " . $project["id"], 2);

        // cache amount of commits
        $project_list[$i]["commit_count"] = request_repo_commit_amount($config["api_key"], $repo_name);

        // only request data if at least one of the data points is missing
        if (array_key_exists("date", $project)
            and array_key_exists("topics", $project)
            and array_key_exists("default_branch", $project)
            and array_key_exists("homepage", $project)
            and array_key_exists("description", $project)
        ) {
            continue;
        }

        $repo_data = json_decode(request_repo_data($config["api_key"], $repo_name)["result"], true);

        // only add date if not set manually
        if (!array_key_exists("date", $project)) {
            $project_list[$i]["date"] = $repo_data["created_at"];

            echolog("added creation date", 3);
        }

        // only add topics if not set manually
        if (!array_key_exists("topics", $project)) {
            $project_list[$i]["topics"] = $repo_data["topics"];

            echolog("added tag list", 3);
        }

        // only add default branch if not set manually
        if (!array_key_exists("default_branch", $project)) {
            $project_list[$i]["default_branch"] = $repo_data["default_branch"];

            echolog("added default branch", 3);
        }

        // only add homepage if not set manually
        if (!array_key_exists("homepage", $project)) {
            $project_list[$i]["homepage"] = $repo_data["homepage"];

            echolog("added homepage", 3);
        }

        // only add description if not set manually
        if (!array_key_exists("description", $project)) {
            $project_list[$i]["description"] = $repo_data["description"];

            echolog("added description", 3);
        }
    }

    // sanetize array
    for ($i = 0; $i < count($project_list); $i++) {
        $project = $project_list[$i];

        if (!array_key_exists("commit_count", $project)) {
            $project_list[$i]["commit_count"] = "0";
        }

        if (!array_key_exists("date", $project)) {
            $project_list[$i]["date"] = "";
        }

        if (!array_key_exists("topics", $project)) {
            $project_list[$i]["topics"] = array();
        }

        if (!array_key_exists("default_branch", $project)) {
            $project_list[$i]["default_branch"] = "main";
        }

        if (!array_key_exists("homepage", $project)) {
            $project_list[$i]["homepage"] = "";
        }

        if (!array_key_exists("description", $project)) {
            $project_list[$i]["description"] = "";
        }

        if (!array_key_exists("source", $project)) {
            $project_list[$i]["source"] = "";
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
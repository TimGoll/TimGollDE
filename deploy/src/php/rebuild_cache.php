<?php
    // all paths in this script are relative to its own folder
    chdir(__DIR__);

    include_once("config.php");

    // the cache can always be rebuilt from the command line, over http only with
    // a POST request that contains the secret rebuild token
    if (PHP_SAPI !== "cli") {
        $token = $_POST["token"] ?? "";

        if ($_SERVER["REQUEST_METHOD"] !== "POST"
            or !is_string($token)
            or $config["rebuild_token"] === ""
            or !hash_equals($config["rebuild_token"], $token)
        ) {
            http_response_code(403);
            exit("403 Forbidden");
        }
    }

    // over http the log is buffered so that the status code can still be set if the rebuild fails
    if (PHP_SAPI !== "cli") {
        ob_start();
    }

    echo("<pre>\n\n");

    include_once("lib/api.php");
    include_once("lib/r_rmdir.php");
    include_once("lib/echo_log.php");
    include_once("lib/cache_file.php");
    include_once("lib/html.php");
    include_once("lib/images.php");
    include_once("lib/render.php");
    include_once("lib/stats.php");

    // the new cache is built in a temporary folder and only replaces the old one if
    // everything succeeded, this way the website is never empty or broken
    $cache_dir = "../cache";
    $tmp_dir = "../cache.tmp";
    $old_dir = "../cache.old";

    // any error aborts the rebuild, the temporary folder is removed and the old cache is kept
    set_exception_handler(function($exception) use ($tmp_dir) {
        if (is_dir($tmp_dir)) {
            r_rmdir($tmp_dir);
        }

        echo("\n");
        echolog("ERROR: " . $exception->getMessage(), 0);
        echolog("Rebuilding the cache failed, the old cache was kept", 0);
        echo("\n</pre>");

        if (PHP_SAPI !== "cli") {
            http_response_code(500);
        }

        exit(1);
    });

    // warnings abort the rebuild as well, otherwise they could end up in the generated pages
    set_error_handler(function($severity, $message, $file, $line) {
        // errors that are suppressed with @ are ignored
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message . " in " . basename($file) . " on line " . $line, 0, $severity, $file, $line);
    });

    echolog("Started rebuilding the website cache, this may take a while...", 0);
    echo("\n");

    // remove leftovers of an aborted rebuild
    foreach ([$tmp_dir, $old_dir] as $dir) {
        if (is_dir($dir)) {
            r_rmdir($dir);
        }
    }

    if (!mkdir($tmp_dir)) {
        throw new RuntimeException("failed to create temporary cache folder");
    }

    echolog("created temporary cache folder", 1);

    $project_path = $config["raw_base"]
        . $config["core"]["owner"] . "/"
        . $config["core"]["repository"] . "/"
        . $config["core"]["default_branch"] . "/webcontent/projects.json";

    // get a list of all projects that should be displayed on the website
    $project_file = request_get_file_contents($project_path);
    $project_list = $project_file === FALSE ? null : json_decode($project_file, true);

    if (!is_array($project_list)) {
        throw new RuntimeException("failed to fetch or parse " . $project_path);
    }

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

        $repo_response = request_repo_data($config["api_key"], $repo_name);

        if ($repo_response["status"] != 200) {
            throw new RuntimeException("failed to fetch repository data of " . $repo_name . " (HTTP " . $repo_response["status"] . ")");
        }

        $repo_data = json_decode($repo_response["result"], true);

        $project_list[$i]["pushed_at"] = $repo_data["pushed_at"] ?? "";

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

        // GitHub returns null if a repository has no homepage or description
        if (!isset($project_list[$i]["homepage"])) {
            $project_list[$i]["homepage"] = "";
        }

        if (!isset($project_list[$i]["description"])) {
            $project_list[$i]["description"] = "";
        }

        // the id is used as a folder name and in the url of the project page
        if (!preg_match("/^[A-Za-z0-9][A-Za-z0-9._-]*$/", $project["id"]) or $project["id"] === "src") {
            throw new RuntimeException("invalid project id \"" . $project["id"] . "\", only letters, numbers, '.', '_' and '-' are allowed");
        }

        if (!array_key_exists("source", $project)) {
            $project_list[$i]["source"] = "";
        }

        if (!isset($project_list[$i]["pushed_at"])) {
            $project_list[$i]["pushed_at"] = "";
        }
    }

    echolog("finished updating project data with data from GitHub", 1);

    // write project list to cache as well
    if (file_put_contents($tmp_dir . "/projects.json", json_encode($project_list)) === FALSE) {
        throw new RuntimeException("failed to write projects.json");
    }

    echolog("stored updated projects file in cache", 1);

    echolog("starting to fetch project contents", 1);

    if (count(array_unique(array_column($project_list, "id"))) !== count($project_list)) {
        throw new RuntimeException("project ids have to be unique");
    }

    // iterate over all projects, request their markdown files, fix the links and translate to HTML
    $project_html = [];

    foreach ($project_list as $project) {
        $project_html[$project["id"]] = cache_file($project, $config, $tmp_dir);
    }

    echolog("finished fetching all projects", 1);

    echolog("caching bio page", 1);

    // also cache the main bio
    $bio_html = cache_file(array(
        "owner" => $config["bio"]["owner"],
        "id" => $config["bio"]["repository"],
        "default_branch" => $config["bio"]["default_branch"],
        "repo_based" => true
    ), $config, $tmp_dir);

    echolog("finished caching bio page", 1);

    echolog("mirroring preview images", 1);

    // preview images are optional, projects without one get a placeholder
    for ($i = 0; $i < count($project_list); $i++) {
        $preview = mirror_image($config["raw_base"]
            . $config["core"]["owner"] . "/"
            . $config["core"]["repository"] . "/"
            . $config["core"]["default_branch"] . "/webcontent/assets/"
            . $project_list[$i]["id"] . ".png", $tmp_dir);

        if ($preview === null) {
            echolog("no preview image for " . $project_list[$i]["id"] . ", showing its initials", 2);
        }

        $project_list[$i]["preview"] = $preview;
    }

    echolog("fetching stats from GitHub", 1);

    // the stats are optional, without them the section is left out
    $stats = request_stats($config);

    if ($stats !== null and file_put_contents($tmp_dir . "/stats.json", json_encode($stats)) === FALSE) {
        throw new RuntimeException("failed to write stats.json");
    }

    $avatar = mirror_image($config["file_base"] . $config["bio"]["owner"] . ".png?size=176", $tmp_dir);

    if ($avatar === null) {
        echolog("WARNING: failed to download the avatar, using initials instead", 2);
    }

    echolog("rendering pages", 1);

    render_pages($project_list, $project_html, [
        "html" => $bio_html,
        "stats" => $stats,
        "avatar" => $avatar
    ], $config, $tmp_dir);

    echolog("finished rendering pages", 1);

    // swap in the new cache, rename() can't replace an existing folder on every system,
    // therefore the old cache is moved out of the way first
    // the warnings of rename() are suppressed, a failure is handled here so that the old cache can be restored
    if (is_dir($cache_dir) and !@rename($cache_dir, $old_dir)) {
        throw new RuntimeException("failed to move the old cache out of the way");
    }

    if (!@rename($tmp_dir, $cache_dir)) {
        if (is_dir($old_dir)) {
            @rename($old_dir, $cache_dir);
        }

        throw new RuntimeException("failed to move the new cache into place");
    }

    if (is_dir($old_dir)) {
        r_rmdir($old_dir);
    }

    echolog("replaced the old cache with the new one", 1);
    echo("\n");

    echolog("Finished caching website", 0);
?>

</pre>
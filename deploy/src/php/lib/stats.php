<?php

// collects the GitHub stats shown on the landing page, returns NULL if they can't be fetched,
// the stats are optional and a failure shouldn't break the whole website
function request_stats($config) {
    $login = $config["bio"]["owner"];

    $query = "query(\$login: String!) {
        user(login: \$login) {
            pullRequests { totalCount }
            repositoriesContributedTo(first: 1, includeUserRepositories: true, contributionTypes: [COMMIT, ISSUE, PULL_REQUEST, REPOSITORY]) { totalCount }
        }
    }";

    $response = request_api_call("https://api.github.com/graphql", $config["api_key"], CURLOPT_POST, [
        "query" => $query,
        "variables" => ["login" => $login]
    ]);
    $data = json_decode((string) $response["result"], true);

    if ($response["status"] != 200 or !isset($data["data"]["user"]) or isset($data["errors"])) {
        echolog("WARNING: failed to fetch stats from GitHub (HTTP " . $response["status"] . ")", 2);

        return null;
    }

    $user = $data["data"]["user"];

    // the commit search counts the commits of all public repositories, not only the own ones
    $commits = request_api_call("https://api.github.com/search/commits", $config["api_key"], CURLOPT_HTTPGET, [
        "q" => "author:" . $login,
        "per_page" => 1
    ]);
    $commit_count = json_decode((string) $commits["result"], true)["total_count"] ?? null;

    if ($commits["status"] != 200 or $commit_count === null) {
        echolog("WARNING: failed to fetch commit count from GitHub (HTTP " . $commits["status"] . ")", 2);

        return null;
    }

    return [
        "commits" => $commit_count,
        "pull_requests" => $user["pullRequests"]["totalCount"],
        "contributed_to" => $user["repositoriesContributedTo"]["totalCount"]
    ];
}

// 2468 -> 2.5k
function format_count($count) {
    if ($count < 1000) {
        return (string) $count;
    }

    return rtrim(rtrim(number_format($count / 1000, 1, ".", ""), "0"), ".") . "k";
}

?>

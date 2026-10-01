<?php
    $config = [
        "raw_base" => "https://raw.githubusercontent.com/",
        "file_base" => "https://github.com/",
        "core" => [
            "owner" => "TimGoll",
            "repository" => "TimGollDE",
            "default_branch" => "main"
        ],
        "bio" => [
            "owner" => "TimGoll",
            "repository" => "TimGoll",
            "default_branch" => "main"
        ],
        // everything the website shows, the pages are written by the cache rebuild
        "site" => [
            // public address of the website without a trailing slash, used for link previews
            "url" => "https://timgoll.de",
            "title" => "Tim Goll - Portfolio",
            // shown in search results and link previews of the landing page, optional
            "description" => "",
            "language" => "en",
            "contact" => "contact@timgoll.de",
            "copyright" => [
                "start_year" => 2014,
                "name" => "Tim Goll"
            ],
            "max_width" => "980px"
        ],
        "api_key" => "xxx",
        // secret token to rebuild the cache over http, an empty token disables it
        "rebuild_token" => ""
    ];

    // secrets.php is not tracked by git and overwrites values such as the api key
    if (file_exists(__DIR__ . "/secrets.php")) {
        $config = array_replace_recursive($config, include(__DIR__ . "/secrets.php"));
    }
?>
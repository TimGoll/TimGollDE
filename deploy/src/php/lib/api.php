<?php

// GitHub requires a user agent that identifies the application
const USER_AGENT = "github-portfolio (+https://github.com/TimGoll/github-portfolio)";

function request_get_file_contents($URL) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    curl_setopt($c, CURLOPT_USERAGENT, USER_AGENT);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 60);
    $contents = curl_exec($c);
    $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    // an empty file is valid content, only a failed request returns FALSE
    if ($contents === FALSE or $status != 200)
        return FALSE;
    else
        return $contents;
}

function request_api_call($url, $token, $type, $curl_data = array()) {
    if ($type == CURLOPT_HTTPGET) {
        $url .= "?" . http_build_query($curl_data);
    }

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Accept: application/vnd.github+json",
        "X-GitHub-Api-Version: 2022-11-28",
        "Content-Type: application/json",
        "Authorization: Bearer " . $token
    ));
    curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, $type, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    if ($type == CURLOPT_POST) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_data));
    }

    // NOW GET THE HEADER (source: https://stackoverflow.com/a/41135574)
    $headers = array();

    // this function is called by curl for each header received
    curl_setopt($curl, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers) {
        $len = strlen($header);
        $header = explode(':', $header, 2);

        if (count($header) < 2) // ignore invalid headers
            return $len;

        $headers[strtolower(trim($header[0]))][] = trim($header[1]);

        return $len;
    });

    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    return array(
        "result" => $result,
        "header" => $headers,
        "status" => $status
    );
}

function request_markdown($token, $text, $mode = "markdown", $context = "") {
    return request_api_call(
        "https://api.github.com/markdown",
        $token,
        CURLOPT_POST,
        array(
            "text" => $text,
            "mode" => $mode,
            "context" => $context
        )
    );
}

function request_repo_data($token, $repo) {
    return request_api_call(
        "https://api.github.com/repos/" . $repo,
        $token,
        CURLOPT_HTTPGET
    );
}

function request_repo_commits($token, $repo, $amount) {
    return request_api_call(
        "https://api.github.com/repos/" . $repo . "/commits",
        $token,
        CURLOPT_HTTPGET,
        array(
            "per_page" => $amount
        )
    );
}

function request_repo_commit_amount($token, $repo) {
    $return = request_repo_commits($token, $repo, 1);

    // request failed
    if ($return["status"] != 200) {
        return 0;
    }

    // the link header is only sent if there is more than one page of commits
    $link_header = $return["header"]["link"][0] ?? null;
    $last_page_link = $link_header === null ? null : link_get($link_header, "last");

    // last page unset
    if ($last_page_link === null) {
        return count(json_decode($return["result"], true));
    }

    return link_param_get($last_page_link, "page");
}


/** HELPERFUNCTIONS **/

function link_get($link, $param) {
    $links = explode(", ", $link);

    for ($i = 0; $i < count($links); $i++) {
        $link_data = explode("; ", $links[$i]);

        if (substr($link_data[1], 5, -1) == $param) {
            return substr($link_data[0], 1, -1);
        }
    }
}

function link_param_get($link, $param) {
    $params = array();

    parse_str(parse_url($link)["query"], $params);

    return $params[$param];
}

?>
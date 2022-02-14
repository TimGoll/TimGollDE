<?php

enum Type {
    case POST;
    case GET;
}

function request_api_call($url, $token, $type, $curl_data = array()) {
    $curl = curl_init($url);

    $authorization = "Authorization: Bearer " . $token;
    $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';

    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization));
    curl_setopt($curl, CURLOPT_USERAGENT, $agent);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, $type, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    if ($type == CURLOPT_POST) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_data));
    }

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
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

function request_repo_tags($token, $repo) {
    return request_api_call(
        "https://api.github.com/repos/" . $repo. "/tags",
        $token,
        CURLOPT_HTTPGET
    );
}

?>
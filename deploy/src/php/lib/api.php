<?php

function request_api_post($url, $user, $token, $curl_post_data) {
    $curl = curl_init($url);

    $authorization = "Authorization: " . $user . " " . $token;
    $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';

    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization));
    curl_setopt($curl, CURLOPT_USERAGENT, $agent);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

function request_markdown($user, $token, $text, $mode = "markdown", $context = "") {
    return request_api_post(
        "https://api.github.com/markdown",
        $user,
        $token,
        array(
            "text" => $text,
            "mode" => $mode,
            "context" => $context
        )
    );
}

?>
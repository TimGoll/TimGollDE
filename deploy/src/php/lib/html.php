<?php

// escapes a value for the use in html text and attributes
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// parses an html fragment, the fragment is wrapped in a full document so that DOMDocument reads it as UTF-8
function html_load($html) {
    $dom = new DOMDocument();

    $use_errors = libxml_use_internal_errors(true); // unknown html5 tags would raise warnings
    $dom->loadHTML("<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body>" . $html . "</body></html>");
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    return $dom;
}

// returns the content of the body of a document created with html_load(), not the wrapper document
function html_save($dom) {
    $html = "";

    foreach ($dom->getElementsByTagName("body")->item(0)->childNodes as $node) {
        $html .= $dom->saveHTML($node);
    }

    return $html;
}

?>

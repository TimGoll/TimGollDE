<?php

// images are stored in the cache and served from the website itself, this way the browser
// of the visitor doesn't have to contact GitHub
const IMAGE_FOLDER = "img";
const IMAGE_URL = "/src/cache/" . IMAGE_FOLDER . "/";

// downloads an image into the cache, returns its local url and size or NULL if it can't be downloaded
function mirror_image($url, $cache_dir) {
    // the same image is often used several times, it is only downloaded once
    static $mirrored = [];

    if (array_key_exists($url, $mirrored)) {
        return $mirrored[$url];
    }

    $mirrored[$url] = null;

    $data = request_get_file_contents($url);

    if ($data === FALSE or $data === "") {
        return null;
    }

    $info = image_info($data);

    if ($info === null) {
        return null;
    }

    // the file name is based on the content, identical images from different urls are stored once
    $name = substr(sha1($data), 0, 20) . "." . $info["extension"];
    $folder = $cache_dir . "/" . IMAGE_FOLDER;

    if (!is_dir($folder) and !mkdir($folder, 0777, true)) {
        throw new RuntimeException("failed to create folder " . $folder);
    }

    if (!is_file($folder . "/" . $name) and file_put_contents($folder . "/" . $name, $data) === FALSE) {
        throw new RuntimeException("failed to write image " . $name);
    }

    $mirrored[$url] = [
        "url" => IMAGE_URL . $name,
        "width" => $info["width"],
        "height" => $info["height"],
        "is_svg" => $info["extension"] === "svg"
    ];

    return $mirrored[$url];
}

// returns the file extension and size of an image, the size is NULL if it is unknown, returns NULL if the data is no image
function image_info($data) {
    $size = @getimagesizefromstring($data);

    if ($size !== FALSE) {
        return [
            "extension" => ltrim(image_type_to_extension($size[2], false), "."),
            "width" => $size[0],
            "height" => $size[1]
        ];
    }

    // svg files are text files and are not supported by getimagesize()
    $dom = new DOMDocument();
    $use_errors = libxml_use_internal_errors(true);
    $is_xml = $dom->loadXML($data, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    if (!$is_xml or $dom->documentElement === null or $dom->documentElement->localName !== "svg") {
        return null;
    }

    $svg = $dom->documentElement;
    $width = svg_length($svg->getAttribute("width"));
    $height = svg_length($svg->getAttribute("height"));

    // without a fixed size, the size is defined by the view box
    if ($width === null or $height === null) {
        $view_box = preg_split("/[\s,]+/", trim($svg->getAttribute("viewBox")));

        if (count($view_box) === 4 and $view_box[2] > 0 and $view_box[3] > 0) {
            $width = (int) round($view_box[2]);
            $height = (int) round($view_box[3]);
        }
    }

    return [
        "extension" => "svg",
        "width" => $width,
        "height" => $height
    ];
}

// converts a length like "60" or "60px" into a number, other units are not supported and return NULL
function svg_length($value) {
    if (!preg_match("/^\s*(\d+(?:\.\d+)?)\s*(px)?\s*$/", $value, $match)) {
        return null;
    }

    return (int) round($match[1]);
}

// replaces all remote images in a document with local copies and adds size and lazy loading
function mirror_images($dom, $cache_dir) {
    echolog("starting image mirroring", 2);

    foreach ($dom->getElementsByTagName("img") as $img) {
        $src = $img->getAttribute("src");

        if (!preg_match("/^https?:\/\//i", $src)) {
            continue;
        }

        $image = mirror_image($src, $cache_dir);

        if ($image === null) {
            echolog("WARNING: failed to download image, keeping remote url: " . $src, 3);

            continue;
        }

        echolog("mirrored image: " . $src, 3);

        $img->setAttribute("src", $image["url"]);
        $img->setAttribute("loading", "lazy");

        add_image_size($img, $image["width"], $image["height"]);

        // GitHub wraps images in a link to the image itself, it should point to the local copy as well,
        // except for svg files that could run scripts when they are opened directly
        $parent = $img->parentNode;

        if ($parent->nodeName === "a" and $parent->getAttribute("href") === $src and !$image["is_svg"]) {
            $parent->setAttribute("href", $image["url"]);
        }
    }
}

// adds the missing width and height so that the browser can reserve the space before the image is loaded
function add_image_size($img, $width, $height) {
    if ($width === null or $height === null or $width <= 0 or $height <= 0) {
        return;
    }

    $set_width = svg_length($img->getAttribute("width"));
    $set_height = svg_length($img->getAttribute("height"));

    // percentages and other units can't be converted, both set means the author defined the size
    if (($img->hasAttribute("width") and $set_width === null)
        or ($img->hasAttribute("height") and $set_height === null)
        or ($set_width !== null and $set_height !== null)
    ) {
        return;
    }

    if ($set_width !== null) {
        $img->setAttribute("height", (string) round($set_width * $height / $width));
    } elseif ($set_height !== null) {
        $img->setAttribute("width", (string) round($set_height * $width / $height));
    } else {
        $img->setAttribute("width", (string) $width);
        $img->setAttribute("height", (string) $height);
    }

    // the image is scaled down to the width of the page, the height has to follow
    $style = rtrim(trim($img->getAttribute("style")), ";");
    $img->setAttribute("style", ($style === "" ? "" : $style . "; ") . "height: auto;");
}

?>

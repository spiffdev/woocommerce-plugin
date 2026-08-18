<?php

/**
 * Functions used to craft requests to Spiff.
 */

function spiff_hex_to_base64($hex) {
    $return = "";
    foreach (str_split($hex, 2) as $pair) {
        $return .= chr(hexdec($pair));
    }
    return base64_encode($return);
}

function spiff_request_headers($application_key, $body, $path) {
    $content_type = 'application/json';
    $date = new DateTime("now", new DateTimeZone("GMT"));
    $date_string = $date->format("D, d M Y H:i:s") . " GMT";
    return array(
        'X-Application-Key' => $application_key,
        'Content-Type' => $content_type,
        'Date' => $date_string,
    );
}

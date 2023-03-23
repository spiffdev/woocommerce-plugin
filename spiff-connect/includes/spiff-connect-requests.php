<?php

/**
 * Functions used to craft requests to Spiff.
 */

if (!defined("SPIFF_API_BASE")) {
    define("SPIFF_API_BASE", getenv("SPIFF_API_BASE"));
}

function spiff_hex_to_base64($hex) {
    $return = "";
    foreach (str_split($hex, 2) as $pair) {
        $return .= chr(hexdec($pair));
    }
    return base64_encode($return);
}

function spiff_auth_header($access_key, $secret_key, $method, $body, $content_type, $date_string, $path) {
    $md5 = md5($body, false);
    $string_to_sign = $method . "\n" . $md5 . "\n" . $content_type . "\n" . $date_string . "\n" . $path;
    $signature = spiff_hex_to_base64(hash_hmac("sha1", $string_to_sign, $secret_key));
    return 'SOA '  . $access_key . ':' . $signature;
}

function spiff_request_headers($access_key, $secret_key, $body, $path) {
    $content_type = 'application/json';
    $date = new DateTime("now", new DateTimeZone("GMT"));
    $date_string = $date->format("D, d M Y H:i:s") . " GMT";
    return array(
        'Authorization' => spiff_auth_header($access_key, $secret_key, 'POST', $body, $content_type, $date_string, $path),
        'Content-Type' => $content_type,
        'Date' => $date_string,
    );
}

function spiff_graphql_query($query, $variables) {
    $url = SPIFF_API_BASE . '/graphql';
    $headers = array(
        'Content-Type' => 'application/json',
    );
    $request_body = json_encode(array(
        'query' => $query,
        'variables' => $variables,
    ));
    $response = wp_remote_post($url, array(
        'body' => $request_body,
        'headers' => $headers,
    ));
    $response_body = wp_remote_retrieve_body($response);
    return json_decode($response_body);
}

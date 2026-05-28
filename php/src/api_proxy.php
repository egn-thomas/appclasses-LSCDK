<?php
function api_request($method, $path, $data = null)
{
    $apiUrl = getenv('API_URL') ?: 'http://localhost:3000';
    $url = rtrim($apiUrl, '/') . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Accept: application/json'];
    if ($data !== null) {
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json';
    }

    // Forward incoming cookies to API
    if (!empty($_SERVER['HTTP_COOKIE'])) {
        $headers[] = 'Cookie: ' . $_SERVER['HTTP_COOKIE'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    if ($resp === false) {
        return ['status' => 500, 'body' => curl_error($ch), 'set_cookie' => []];
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $rawHeaders = substr($resp, 0, $headerSize);
    $body = substr($resp, $headerSize);

    // Parse Set-Cookie headers
    $setCookies = [];
    $lines = explode("\r\n", $rawHeaders);
    foreach ($lines as $line) {
        if (stripos($line, 'Set-Cookie:') === 0) {
            $setCookies[] = trim(substr($line, strlen('Set-Cookie:')));
        }
    }

    curl_close($ch);
    return ['status' => $status, 'body' => $body, 'set_cookie' => $setCookies];
}

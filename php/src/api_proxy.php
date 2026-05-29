<?php
function debug_log($label, $data) {
    $line = '[' . date('H:i:s') . '] ' . $label . ': ' . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents('/tmp/debug.log', $line, FILE_APPEND);
}

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

    debug_log('REQUEST', ['method' => $method, 'path' => $path]);
    debug_log('COOKIES_SENT', $_SERVER['HTTP_COOKIE'] ?? 'none');

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);

    if ($resp === false) {
        debug_log('CURL_ERROR', curl_error($ch));
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

    // Les logs APRÈS que les variables sont assignées
    debug_log('RESPONSE_STATUS', $status);
    debug_log('SET_COOKIE_RECEIVED', $setCookies);
    debug_log('BODY', $body);

    curl_close($ch);
    return ['status' => $status, 'body' => $body, 'set_cookie' => $setCookies];
}

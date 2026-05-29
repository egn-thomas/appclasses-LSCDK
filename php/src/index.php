<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_proxy.php';

$page = $_GET['page'] ?? '';
if ($page === '') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $page = trim($path, '/') ?: 'login';
}
$page = $page ?: 'login';
$error = '';
$values = [];
$classes = [];
$stats = [];

function redirect($location)
{
    header('Location: ' . $location);
    exit;
}

function is_authenticated()
{
    $resp = api_request('GET', '/api/auth/me');
    if ($resp['status'] !== 200) {
        return false;
    }
    $body = json_decode($resp['body'], true);
    return is_array($body) && !empty($body['ok']);
}

if ($page === 'logout') {
    $resp = api_request('POST', '/api/auth/logout');
    foreach ($resp['set_cookie'] as $sc) {
        header('Set-Cookie: ' . $sc, false);
    }
    setcookie('connect.sid', '', time() - 3600, '/');
    redirect('/?page=login');
}

$authenticated = is_authenticated();
if ($page === 'home') {
    redirect($authenticated ? '/?page=dashboard' : '/?page=login');
}
if ($page !== 'login' && !$authenticated) {
    redirect('/?page=login');
}
if ($page === 'login' && $authenticated) {
    redirect('/?page=dashboard');
}

debug_log('ALL_COOKIES_PHP', $_COOKIE);
debug_log('HTTP_COOKIE_HEADER', $_SERVER['HTTP_COOKIE'] ?? 'none');

// PROCESS ALL POST REQUESTS AND REDIRECTS BEFORE SENDING HTML
if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values['username'] = $_POST['username'] ?? '';
        $values['password'] = $_POST['password'] ?? '';
        $resp = api_request('POST', '/api/auth/login', [
            'username' => $values['username'],
            'password' => $values['password']
        ]);
        if ($resp['status'] === 200) {
            foreach ($resp['set_cookie'] as $sc) {
                header('Set-Cookie: ' . $sc, false);
                // ✅ FIX: mettre à jour $_COOKIE localement
                if (preg_match('/^connect\.sid=([^;]+)/', $sc, $m)) {
                    $_COOKIE['connect.sid'] = $m[1];
                    $_SERVER['HTTP_COOKIE'] = 'connect.sid=' . $m[1];
                }
            }
            redirect('/?page=dashboard');
        }
        $error = 'Impossible de se connecter. Vérifiez vos identifiants.';
    }
}

// NOW INCLUDE HEAD AND OTHER TEMPLATES
if ($page === 'login') {
    $pageTitle = 'Connexion';
} elseif ($page === 'dashboard') {
    $pageTitle = 'Dashboard';
} else {
    $pageTitle = 'Page introuvable';
}

require __DIR__ . '/includes/head.php';
if ($authenticated) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/nav.php';
}

// RENDER PAGE CONTENT
switch ($page) {
    case 'login':
        $title = 'Connexion';
        require __DIR__ . '/pages/login.php';
        break;

    case 'dashboard':
        $title = 'Dashboard';
        require __DIR__ . '/pages/dashboard.php';
        break;

    default:
        http_response_code(404);
        redirect('/?page=dashboard');
        break;
}

if ($authenticated) {
    require __DIR__ . '/includes/footer.php';
}


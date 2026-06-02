<?php
// Test simple pour vérifier l'appel API aux utilisateurs
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_proxy.php';

// Faire l'appel à l'API
$resp = api_request('GET', '/api/users');

echo "Status: " . $resp['status'] . "\n";
echo "Body: " . $resp['body'] . "\n";
?>
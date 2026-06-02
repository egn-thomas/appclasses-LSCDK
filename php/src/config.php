<?php

define('SITE_NAME', 'AppClasses');

// API_URL pour les appels côté serveur PHP (utilise getenv pour rester flexible)
// Pas besoin de constante ici, api_proxy.php utilise getenv() directement

// API_URL_FRONTEND pour les appels côté client JavaScript
// Utilise un path relatif /api qui doit être proxiée par le reverse proxy
define('API_URL_FRONTEND', '/api');

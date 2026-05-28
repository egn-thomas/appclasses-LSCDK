<?php
require_once 'config.php';

include 'includes/header.php';
include 'includes/nav.php';

$page = $_GET['page'] ?? 'affrontement';

switch ($page) {
    case 'test':
        include 'pages/test.php';
        break;
    case 'affrontement':
        include 'pages/affrontement.php';
        break;
}

include 'includes/footer.php';
<?php
$currentPage = $page ?? 'dashboard';
// Récupérer les infos de l'utilisateur pour vérifier son rôle
$userResponse = api_request('GET', '/api/auth/me');
$userRole = '';
if ($userResponse['status'] === 200) {
    $userData = json_decode($userResponse['body'], true);
    $userRole = $userData['user']['role'] ?? '';
}
?>
<nav class="site-nav">
    <?php if ($currentPage !== 'dashboard'): ?>
        <a class="nav-link" href="/?page=dashboard">Dashboard</a>
    <?php endif; ?>
    <?php if ($userRole === 'Administrateur'): ?>
        <a class="nav-link" href="/?page=users">Utilisateurs</a>
    <?php endif; ?>
    <form class="inline-form" method="POST" action="/?page=logout">
        <button class="btn secondary btn-compact" type="submit">Se déconnecter</button>
    </form>
</nav>
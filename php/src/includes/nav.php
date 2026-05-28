<?php
$currentPage = $page ?? 'dashboard';
?>
<nav class="site-nav">
    <?php if ($currentPage !== 'dashboard'): ?>
        <a class="nav-link" href="/?page=dashboard">Dashboard</a>
    <?php endif; ?>
    <form class="inline-form" method="POST" action="/?page=logout">
        <button class="btn secondary btn-compact" type="submit">Se déconnecter</button>
    </form>
</nav>
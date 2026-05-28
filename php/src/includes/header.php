<?php
$pageTitle = $pageTitle ?? ucfirst($page ?? 'Accueil');
?>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand">
            <a class="brand-link" href="/?page=dashboard">AppClasses</a>
            <p class="brand-tag"><?= htmlspecialchars($pageTitle) ?></p>
        </div>
    </div>
</header>
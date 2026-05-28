<?php
require_once 'config.php';
?>

<nav>
    <div class="nav-container">
        <div class="logo">
            <img src="<?php echo LOGO_URL; ?>" alt="Logo <?php echo SITE_NAME; ?>" class="logo-img">
        </div>
        <div class="site-title"><?php echo CURRENT_PAGE; ?></div>

        <ul class="nav-links">
            <li><a href="?page=leaderboard">Leaderboard</a></li>
            <li><a href="?page=game">Voir une partie</a></li>
            <li><a href="?page=affrontement">Simuler un affrontement</a></li>
        </ul>
    </div>
</nav>
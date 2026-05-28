
<?php
require_once 'config.php';

$labels = ['victoire', 'défaite', 'nulle'];
$stats_winrate = [70, 20, 10];
?>

<div class="player-card">
    <div class="player-card-head">
        <p class="player-card-name">Magnus Carlsen</p>
        <p class="player-card-rank">#1</p>
    </div>
    <div class="player-card-horizontal-bar"></div>
    <div class="player-card-graphique">
        <canvas class="chart-canvas" 
            data-chart-label="Ratio de victoires"
            data-chart-labels='<?= json_encode($labels) ?>'
            data-chart-data='<?= json_encode($stats_winrate) ?>'>
        </canvas>
    </div>
    <div class="player-card-facultes">
        <div class="player-card-faculte-force">
            <p>Force : </p>
            <p class="player-card-faculte-score">00</p>
        </div>
        <div class="player-card-faculte">
            <p>Tactique : </p>
            <p class="player-card-faculte-score">00</p>
        </div>
        <div class="player-card-faculte">
            <p>Jeu positionnel : </p>
            <p class="player-card-faculte-score">00</p>
        </div>
        <div class="player-card-faculte">
            <p>Gestion du temps : </p>
            <p class="player-card-faculte-score">00</p>
        </div>
        <div class="player-card-faculte">
            <p>Ouverture : </p>
            <p class="player-card-faculte-score">00</p>
        </div>
        <div class="player-card-faculte">
            <p>Milieu de jeu : </p>
            <p class="player-card-faculte-score">00</p>
        </div>
        <div class="player-card-faculte">
            <p>Finale : </p>
            <p class="player-card-faculte-score">00</p>
        </div>
    </div>
</div>
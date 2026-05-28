<?php
    $labels = ['victoire', 'défaite', 'nulle'];
    $stats_winrate = [70, 20, 10];
?>

    <main class="container">
        <section class="search-section">
            <p>Choisissez les joueurs qui vont s'affronter</p>
            <form action="" method="GET" class="search-form">
                <div class="input-group">
                    <input type="text" name="player1" placeholder="Joueur 1" value="Jo" class="player-input">
                    <span class="vs-text">VS</span>
                    <input type="text" name="player2" placeholder="Joueur 2" value="Fabrice" class="player-input">
                </div>
                <button type="submit" class="btn-compare">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>
            </form>
        </section>

        <hr class="separator">

        <section class="stats-section">
            <h2>
                <span class="player-name">Fabrice</span> gagne dans
                <span class="highlight">75%</span> des cas
            </h2>

            <div class="main-chart-container card">
                <canvas class="chart-canvas" data-chart-label="Ratio de victoires"
                    data-chart-labels='<?= json_encode($labels) ?>'
                    data-chart-data='<?= json_encode($stats_winrate) ?>'>
                </canvas>
            </div>

            <div class="secondary-charts">
                <div class="card">
                    <canvas class="chart-canvas" data-chart-labels='["Victoire", "Défaite", "Nulle"]'
                        data-chart-data="[10, 5, 2]" data-chart-label="Mes stats" data-chart-type="bar">></canvas>
                    <p class="chart-title">Comparaison des ratios de victoires (3 mois)</p>
                </div>
                <div class="card">
                    <canvas class="chart-canvas" data-chart-labels='["Victoire", "Défaite", "Nulle"]'
                        data-chart-data="[8, 7, 5]" data-chart-label="Ouvertures" data-chart-type="bar"></canvas>
                    <p class="chart-title">Probabilité d'utilisation des ouvertures</p>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="./js/ChartsManager.ts"></script>
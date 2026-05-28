<main class="center-screen container">
    <section class="form-card">
        <h2>Connexion</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/?page=login" class="form-grid">
            <label class="form-group">
                <span>Nom d'utilisateur</span>
                <input class="input-full" type="text" name="username"
                    value="<?= htmlspecialchars($values['username'] ?? '') ?>" required>
            </label>
            <label class="form-group">
                <span>Mot de passe</span>
                <input class="input-full" type="password" name="password" required>
            </label>
            <div class="form-actions">
                <button class="btn" type="submit">Se connecter</button>
            </div>
        </form>
    </section>
</main>
</body>

</html>
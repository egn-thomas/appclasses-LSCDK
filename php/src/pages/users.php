<?php
// Vérifier que l'utilisateur est admin
$userResponse = api_request('GET', '/api/auth/me');
if ($userResponse['status'] !== 200) {
    redirect('/?page=login');
}
$userData = json_decode($userResponse['body'], true);
if ($userData['user']['role'] !== 'Administrateur') {
    redirect('/?page=dashboard');
}

$error = '';
$success = '';
$users = [];

// Récupérer la liste des utilisateurs
$resp = api_request('GET', '/api/users');
debug_log('USERS_API_RESPONSE', ['status' => $resp['status'], 'body' => $resp['body']]);
if ($resp['status'] === 200) {
    $data = json_decode($resp['body'], true);
    $users = $data['users'] ?? [];
} else {
    $error = 'Erreur lors du chargement des utilisateurs (Status: ' . $resp['status'] . ')';
}

// Traiter les formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'Lecteur';

            if (!$username || !$password) {
                $error = 'Le nom d\'utilisateur et le mot de passe sont requis';
            } else {
                $payload = json_encode([
                    'username' => $username,
                    'password' => $password,
                    'role' => $role,
                ]);
                $resp = api_request('POST', '/api/users', $payload);
                if ($resp['status'] === 201) {
                    $success = 'Utilisateur créé avec succès';
                    // Recharger la page
                    redirect('/?page=users&success=1');
                } else {
                    $respData = json_decode($resp['body'], true);
                    $error = $respData['error'] ?? 'Erreur lors de la création';
                }
            }
        } elseif ($_POST['action'] === 'update') {
            $userId = $_POST['user_id'] ?? '';
            $role = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $payload = ['role' => $role];
            if ($password) {
                $payload['password'] = $password;
            }
            
            $resp = api_request('PUT', '/api/users/' . $userId, json_encode($payload));
            if ($resp['status'] === 200) {
                $success = 'Utilisateur mis à jour avec succès';
                redirect('/?page=users&success=1');
            } else {
                $respData = json_decode($resp['body'], true);
                $error = $respData['error'] ?? 'Erreur lors de la mise à jour';
            }
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'ajout d'utilisateur -->
    <section class="users-form">
        <h2>Ajouter un nouvel utilisateur</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="username">Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Rôle:</label>
                <select id="role" name="role">
                    <option value="Lecteur">Lecteur</option>
                    <option value="Editeur">Editeur</option>
                    <option value="Administrateur">Administrateur</option>
                </select>
            </div>
            <button type="submit" class="btn primary">Ajouter l'utilisateur</button>
        </form>
    </section>

    <!-- Tableau des utilisateurs -->
    <section class="users-table">
        <h2>Liste des utilisateurs</h2>
        <?php if (empty($users)): ?>
            <p>Aucun utilisateur trouvé.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom d'utilisateur</th>
                        <th>Rôle</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <?php
                                $createdAt = new DateTime($user['createdAt']);
                                echo $createdAt->format('d/m/Y H:i');
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn secondary btn-compact"
                                    onclick="openEditModal('<?php echo htmlspecialchars($user['_id']); ?>', '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')">
                                    Éditer
                                </button>
                                <form method="POST" action="" style="display: inline;"
                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['_id']); ?>">
                                    <button type="submit" class="btn danger btn-compact">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<!-- Modal d'édition -->
<div id="editModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Éditer l'utilisateur</h2>
            <button type="button" class="btn-close" onclick="closeEditModal()">×</button>
        </div>
        <form id="editForm" method="POST" action="">
            <div style="padding: 20px;">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="editUserId" name="user_id">
                <div class="form-group">
                    <label for="editUsername">Nom d'utilisateur:</label>
                    <input type="text" id="editUsername" name="username" readonly>
                </div>
                <div class="form-group">
                    <label for="editRole">Rôle:</label>
                    <select id="editRole" name="role">
                        <option value="Lecteur">Lecteur</option>
                        <option value="Editeur">Editeur</option>
                        <option value="Administrateur">Administrateur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editPassword">Nouveau mot de passe (laisser vide pour ne pas changer):</label>
                    <input type="password" id="editPassword" name="password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="closeEditModal()">Annuler</button>
                <button type="submit" class="btn primary">Sauvegarder</button>
            </div>
        </form>
    </div>
</div>

<style>
    .page-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .users-form {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .users-form h2 {
        margin-top: 0;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .users-table {
        margin-top: 30px;
    }

    .users-table h2 {
        margin-bottom: 15px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
    }

    .table thead {
        background-color: #f5f5f5;
    }

    .table th,
    .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .table th {
        font-weight: 600;
        color: #333;
    }

    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }

    .btn.primary {
        background-color: #007bff;
        color: white;
    }

    .btn.primary:hover {
        background-color: #0056b3;
    }

    .btn.secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn.secondary:hover {
        background-color: #545b62;
    }

    .btn.danger {
        background-color: #dc3545;
        color: white;
    }

    .btn.danger:hover {
        background-color: #c82333;
    }

    .btn-compact {
        padding: 5px 10px;
        font-size: 12px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.2s;
    }

    .modal.hidden {
        display: none;
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border: 1px solid #888;
        width: 500px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        margin: 0;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
    }

    .modal-footer {
        padding: 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .alert-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    @media (max-width: 768px) {
        .modal-content {
            width: 90%;
        }

        .table {
            font-size: 14px;
        }

        .table th,
        .table td {
            padding: 8px 10px;
        }
    }
</style>

<script>
    function openEditModal(userId, username, role) {
        document.getElementById('editUserId').value = userId;
        document.getElementById('editUsername').value = username;
        document.getElementById('editRole').value = role;
        document.getElementById('editPassword').value = '';
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Fermer le modal en cliquant en dehors
    window.onclick = function (event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    };
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
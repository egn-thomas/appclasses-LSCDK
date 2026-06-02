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
$redirectTo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'Lecteur';

            if (!$username || !$password) {
                $error = 'Le nom d\'utilisateur et le mot de passe sont requis';
            } else {
                $payload = [
                    'username' => $username,
                    'password' => $password,
                    'role' => $role,
                ];
                $resp = api_request('POST', '/api/users', $payload);
                if ($resp['status'] === 201) {
                    $success = 'Utilisateur créé avec succès';
                    $_GET['success'] = '1';
                    // Refresh user list
                    $resp = api_request('GET', '/api/users');
                    if ($resp['status'] === 200) {
                        $data = json_decode($resp['body'], true);
                        $users = $data['users'] ?? [];
                    }
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

            $resp = api_request('PUT', '/api/users/' . $userId, $payload);
            if ($resp['status'] === 200) {
                $success = 'Utilisateur mis à jour avec succès';
                $_GET['success'] = '1';
                // Refresh user list
                $resp = api_request('GET', '/api/users');
                if ($resp['status'] === 200) {
                    $data = json_decode($resp['body'], true);
                    $users = $data['users'] ?? [];
                }
            } else {
                $respData = json_decode($resp['body'], true);
                $error = $respData['error'] ?? 'Erreur lors de la mise à jour';
            }
        } elseif ($_POST['action'] === 'delete') {
            $userId = $_POST['user_id'] ?? '';
            $resp = api_request('DELETE', '/api/users/' . $userId);
            if ($resp['status'] === 200) {
                $success = 'Utilisateur supprimé avec succès';
                $_GET['success'] = '1';
                // Refresh user list
                $resp = api_request('GET', '/api/users');
                if ($resp['status'] === 200) {
                    $data = json_decode($resp['body'], true);
                    $users = $data['users'] ?? [];
                }
            } else {
                $respData = json_decode($resp['body'], true);
                $error = $respData['error'] ?? 'Erreur lors de la suppression';
            }
        }
    }
}
?>

<div class="page-content">
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <script>
            setTimeout(() => {
                location.href = '/?page=users';
            }, 1500);
        </script>
    <?php endif; ?>

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
                    <option value="Editeur">Éditeur</option>
                    <option value="Administrateur">Administrateur</option>
                </select>
            </div>
            <button type="submit" class="btn primary">Ajouter l'utilisateur</button>
        </form>
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
                        <option value="Editeur">Éditeur</option>
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

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
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
        box-sizing: border-box;
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
        padding: 6px 12px;
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
    }

    .modal.hidden {
        display: none;
    }

    .modal:not(.hidden) {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h2 {
        margin: 0;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .btn-close:hover {
        color: #333;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
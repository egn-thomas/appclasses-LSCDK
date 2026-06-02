<?php
// Vérifier que l'utilisateur est admin ou éditeur
$userResponse = api_request('GET', '/api/auth/me');
if ($userResponse['status'] !== 200) {
    redirect('/?page=login');
}
$userData = json_decode($userResponse['body'], true);
if (!in_array($userData['user']['role'], ['Administrateur', 'Editeur'])) {
    redirect('/?page=dashboard');
}

$error = '';
$success = '';
$options = [];

// Récupérer la liste des options
$resp = api_request('GET', '/api/options');
if ($resp['status'] === 200) {
    $data = json_decode($resp['body'], true);
    $options = $data['options'] ?? [];
} else {
    $error = 'Erreur lors du chargement des options (Status: ' . $resp['status'] . ')';
}

// Traiter les formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $name = $_POST['name'] ?? '';

            if (!$name) {
                $error = 'Le nom de l\'option est requis';
            } else {
                $payload = ['name' => $name];
                $resp = api_request('POST', '/api/options', $payload);
                if ($resp['status'] === 201) {
                    $success = 'Option créée avec succès';
                    $_GET['success'] = '1';
                    // Refresh option list
                    $resp = api_request('GET', '/api/options');
                    if ($resp['status'] === 200) {
                        $data = json_decode($resp['body'], true);
                        $options = $data['options'] ?? [];
                    }
                } else {
                    $respData = json_decode($resp['body'], true);
                    $error = $respData['error'] ?? 'Erreur lors de la création';
                }
            }
        } elseif ($_POST['action'] === 'update') {
            $optionId = $_POST['option_id'] ?? '';
            $name = $_POST['name'] ?? '';

            if (!$name) {
                $error = 'Le nom de l\'option est requis';
            } else {
                $payload = ['name' => $name];
                $resp = api_request('PUT', '/api/options/' . $optionId, $payload);
                if ($resp['status'] === 200) {
                    $success = 'Option mise à jour avec succès';
                    $_GET['success'] = '1';
                    // Refresh option list
                    $resp = api_request('GET', '/api/options');
                    if ($resp['status'] === 200) {
                        $data = json_decode($resp['body'], true);
                        $options = $data['options'] ?? [];
                    }
                } else {
                    $respData = json_decode($resp['body'], true);
                    $error = $respData['error'] ?? 'Erreur lors de la mise à jour';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $optionId = $_POST['option_id'] ?? '';
            $resp = api_request('DELETE', '/api/options/' . $optionId);
            if ($resp['status'] === 200) {
                $success = 'Option supprimée avec succès';
                $_GET['success'] = '1';
                // Refresh option list
                $resp = api_request('GET', '/api/options');
                if ($resp['status'] === 200) {
                    $data = json_decode($resp['body'], true);
                    $options = $data['options'] ?? [];
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
                location.href = '/?page=options';
            }, 1500);
        </script>
    <?php endif; ?>

    <!-- Tableau des options -->
    <section class="options-table">
        <h2>Liste des options</h2>
        <?php if (empty($options)): ?>
            <p>Aucune option trouvée.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom de l'option</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options as $option): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($option['name']); ?></td>
                            <td>
                                <?php
                                $createdAt = new DateTime($option['createdAt']);
                                echo $createdAt->format('d/m/Y H:i');
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn secondary btn-compact"
                                    onclick="openEditModal('<?php echo htmlspecialchars($option['_id']); ?>', '<?php echo htmlspecialchars($option['name']); ?>')">
                                    Éditer
                                </button>
                                <form method="POST" action="" style="display: inline;"
                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette option ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="option_id"
                                        value="<?php echo htmlspecialchars($option['_id']); ?>">
                                    <button type="submit" class="btn danger btn-compact">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Formulaire d'ajout d'option -->
    <section class="options-form">
        <h2>Ajouter une nouvelle option</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="name">Nom de l'option:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <button type="submit" class="btn primary">Ajouter l'option</button>
        </form>
    </section>
</div>

<!-- Modal d'édition -->
<div id="editModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Éditer l'option</h2>
            <button type="button" class="btn-close" onclick="closeEditModal()">×</button>
        </div>
        <form id="editForm" method="POST" action="">
            <div style="padding: 20px;">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="editOptionId" name="option_id">
                <div class="form-group">
                    <label for="editName">Nom de l'option:</label>
                    <input type="text" id="editName" name="name" required>
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

    .options-form {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .options-form h2 {
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

    .form-group input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .options-table {
        margin-top: 30px;
    }

    .options-table h2 {
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
    function openEditModal(optionId, name) {
        document.getElementById('editOptionId').value = optionId;
        document.getElementById('editName').value = name;
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
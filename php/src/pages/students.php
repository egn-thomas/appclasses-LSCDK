<?php
// Get user and check auth
$userResponse = api_request('GET', '/api/auth/me');
if ($userResponse['status'] !== 200)
    redirect('/?page=login');
$userData = json_decode($userResponse['body'], true);
$userRole = $userData['user']['role'];
$canEdit = in_array($userRole, ['Administrateur', 'Editeur']);

$error = $success = '';
$students = $classes = $options = [];

// Load data
$resp = api_request('GET', '/api/students');
if ($resp['status'] === 200) {
    $students = json_decode($resp['body'], true)['students'] ?? [];
} else {
    $error = 'Erreur chargement élèves';
}

$resp = api_request('GET', '/api/classes');
if ($resp['status'] === 200) {
    $classes = json_decode($resp['body'], true)['classes'] ?? [];
}

$resp = api_request('GET', '/api/options');
if ($resp['status'] === 200) {
    $options = json_decode($resp['body'], true)['options'] ?? [];
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit && isset($_POST['action'])) {
    if ($_POST['action'] === 'import_csv') {
        $csvData = json_decode($_POST['csvData'], true);

        if (!is_array($csvData) || empty($csvData)) {
            $error = 'Données CSV invalides';
        } else {
            $imported = 0;
            $errors = [];

            foreach ($csvData as $idx => $row) {
                $className = $row['className'] ?? '';
                $firstName = $row['firstName'] ?? '';
                $lastName = $row['lastName'] ?? '';

                if (empty($firstName) || empty($lastName) || empty($className)) {
                    $errors[] = "Ligne " . ($idx + 2) . ": Prénom, nom ou classe manquant";
                    continue;
                }

                $payload = [
                    'className' => $className,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'gender' => $row['gender'] ?? '',
                    'age' => (int) ($row['age'] ?? 0),
                    'dateOfBirth' => $row['dateOfBirth'] ?? '',
                    'formation' => $row['formation'] ?? '',
                    'options' => array_filter($row['options'] ?? []),
                ];

                $resp = api_request('POST', '/api/students', $payload);
                if ($resp['status'] === 201) {
                    $imported++;
                } else {
                    $body = json_decode($resp['body'], true);
                    $errors[] = "Ligne " . ($idx + 2) . ": " . ($body['error'] ?? 'Erreur');
                }
            }

            if ($imported > 0) {
                $success = $imported . ' élève(s) importé(s)';
                if (count($errors) > 0) {
                    $success .= ' (' . count($errors) . ' erreur(s))';
                }
                $_GET['success'] = '1';
                $resp = api_request('GET', '/api/students');
                if ($resp['status'] === 200) {
                    $students = json_decode($resp['body'], true)['students'] ?? [];
                }
            } else {
                $error = 'Aucun élève importé';
                if (!empty($errors)) {
                    $error .= ': ' . $errors[0];
                }
            }
        }
    } elseif ($_POST['action'] === 'create') {
        $payload = [
            'className' => $_POST['className'] ?? '',
            'firstName' => $_POST['firstName'] ?? '',
            'lastName' => $_POST['lastName'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'age' => (int) ($_POST['age'] ?? 0),
            'dateOfBirth' => $_POST['dateOfBirth'] ?? '',
            'formation' => $_POST['formation'] ?? '',
            'options' => $_POST['options'] ?? [],
        ];

        if (empty($payload['className']) || empty($payload['firstName']) || empty($payload['lastName'])) {
            $error = 'Champs requis manquants';
        } else {
            $resp = api_request('POST', '/api/students', $payload);
            if ($resp['status'] === 201) {
                $success = 'Élève créé';
                $_GET['success'] = '1';
                $resp = api_request('GET', '/api/students');
                if ($resp['status'] === 200) {
                    $students = json_decode($resp['body'], true)['students'] ?? [];
                }
            } else {
                $error = json_decode($resp['body'], true)['error'] ?? 'Erreur création';
            }
        }
    } elseif ($_POST['action'] === 'bulk_update') {
        $updates = json_decode($_POST['updates'], true);
        if (is_array($updates)) {
            foreach ($updates as $u) {
                $id = $u['student_id'] ?? '';
                $data = $u['data'] ?? [];
                if ($id && !empty($data)) {
                    $resp = api_request('PUT', '/api/students/' . $id, $data);
                }
            }
            $_GET['success'] = '1';
            $resp = api_request('GET', '/api/students');
            if ($resp['status'] === 200) {
                $students = json_decode($resp['body'], true)['students'] ?? [];
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['student_id'] ?? '';
        if ($id) {
            $resp = api_request('DELETE', '/api/students/' . $id);
            if ($resp['status'] === 200) {
                $_GET['success'] = '1';
                $resp = api_request('GET', '/api/students');
                if ($resp['status'] === 200) {
                    $students = json_decode($resp['body'], true)['students'] ?? [];
                }
            }
        }
    }
}
?>

<div class="page-content">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <script>setTimeout(() => location.href = '/?page=students', 1500);</script><?php endif; ?>

    <section class="students-table">
        <div class="section-header">
            <h2>Liste des élèves</h2>
            <div class="header-btns">
                <?php if ($canEdit): ?><button class="btn primary btn-compact" onclick="openImport()">➕
                        Importer</button><?php endif; ?>
                <?php if ($canEdit): ?><button class="btn secondary btn-compact" onclick="toggleVis()">⚙️
                        Colonnes</button><?php endif; ?>
            </div>
        </div>

        <div id="colPanel" class="col-panel hidden">
            <h3>Colonnes</h3>
            <label><input type="checkbox" class="col-tog" data-col="c1" checked> Classe</label>
            <label><input type="checkbox" class="col-tog" data-col="c2" checked> Prénom</label>
            <label><input type="checkbox" class="col-tog" data-col="c3" checked> Nom</label>
            <label><input type="checkbox" class="col-tog" data-col="c4" checked> Genre</label>
            <label><input type="checkbox" class="col-tog" data-col="c5" checked> Âge</label>
            <label><input type="checkbox" class="col-tog" data-col="c6" checked> Date naissance</label>
            <label><input type="checkbox" class="col-tog" data-col="c7" checked> Formation</label>
            <?php foreach ($options as $o): ?><label><input type="checkbox" class="col-tog"
                        data-col="opt-<?php echo htmlspecialchars($o['_id']); ?>" checked>
                    <?php echo htmlspecialchars($o['name']); ?></label><?php endforeach; ?>
        </div>

        <table class="stud-table">
            <thead>
                <tr>
                    <th class="c1">Classe</th>
                    <th class="c2">Prénom</th>
                    <th class="c3">Nom</th>
                    <th class="c4">Genre</th>
                    <th class="c5">Âge</th>
                    <th class="c6">Date naissance</th>
                    <th class="c7">Formation</th>
                    <?php foreach ($options as $o): ?>
                        <th class="opt-<?php echo htmlspecialchars($o['_id']); ?>">
                            <?php echo htmlspecialchars($o['name']); ?>
                        </th><?php endforeach; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr class="st-row" data-id="<?php echo htmlspecialchars($s['_id']); ?>">
                        <td class="c1">
                            <?php if ($canEdit): ?>
                                <select class="st-inp" name="className">
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo $s['className'] === $c['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" class="st-inp" name="className"
                                    value="<?php echo htmlspecialchars($s['className']); ?>" readonly>
                            <?php endif; ?>
                        </td>
                        <td class="c2"><input type="text" class="st-inp" name="firstName"
                                value="<?php echo htmlspecialchars($s['firstName']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                        <td class="c3"><input type="text" class="st-inp" name="lastName"
                                value="<?php echo htmlspecialchars($s['lastName']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                        <td class="c4">
                            <?php if ($canEdit): ?>
                                <select class="st-inp" name="gender">
                                    <option value="" <?php echo $s['gender'] === '' ? 'selected' : ''; ?>>--</option>
                                    <option value="M" <?php echo $s['gender'] === 'M' ? 'selected' : ''; ?>>M</option>
                                    <option value="F" <?php echo $s['gender'] === 'F' ? 'selected' : ''; ?>>F</option>
                                    <option value="Autre" <?php echo $s['gender'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            <?php else: ?>
                                <input type="text" class="st-inp" name="gender"
                                    value="<?php echo htmlspecialchars($s['gender']); ?>" readonly>
                            <?php endif; ?>
                        </td>
                        <td class="c5"><input type="number" class="st-inp" name="age" value="<?php echo $s['age']; ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                        <td class="c6"><input type="date" class="st-inp" name="dateOfBirth"
                                value="<?php echo htmlspecialchars($s['dateOfBirth']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                        <td class="c7"><input type="text" class="st-inp" name="formation"
                                value="<?php echo htmlspecialchars($s['formation']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                        <?php foreach ($options as $o):
                            $hasOpt = in_array($o['_id'], $s['options'] ?? []);
                            ?>
                            <td class="opt-<?php echo htmlspecialchars($o['_id']); ?>"><input type="checkbox" class="st-opt"
                                    data-oid="<?php echo htmlspecialchars($o['_id']); ?>" <?php echo $hasOpt ? 'checked' : ''; ?>                                   <?php echo !$canEdit ? 'disabled' : ''; ?>></td>
                        <?php endforeach; ?>
                        <td><?php if ($canEdit): ?><button class="btn danger btn-compact"
                                    onclick="delStud('<?php echo htmlspecialchars($s['_id']); ?>')">✕</button><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($canEdit): ?><button id="savBtn" class="btn primary"
                style="position: fixed; bottom: 20px; right: 20px; display: none; z-index: 100;" onclick="save()">💾
                Sauvegarder</button><?php endif; ?>
    </section>

    <?php if ($canEdit): ?>
        <section class="add-form">
            <h2>Ajouter élève</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div><label>Classe:</label><select name="className" required><?php foreach ($classes as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['name']); ?>">
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option><?php endforeach; ?>
                        </select></div>
                    <div><label>Prénom:</label><input type="text" name="firstName" required></div>
                    <div><label>Nom:</label><input type="text" name="lastName" required></div>
                    <div><label>Genre:</label><select name="gender" required>
                            <option value="M">M</option>
                            <option value="F">F</option>
                            <option value="Autre">Autre</option>
                        </select></div>
                    <div><label>Âge:</label><input type="number" name="age" required></div>
                    <div><label>Date naissance:</label><input type="date" name="dateOfBirth" required></div>
                    <div><label>Formation:</label><input type="text" name="formation" required></div>
                </div>
                <div><label>Options:</label>
                    <div class="chk-grp"><?php foreach ($options as $o): ?><label><input type="checkbox" name="options[]"
                                    value="<?php echo htmlspecialchars($o['_id']); ?>">
                                <?php echo htmlspecialchars($o['name']); ?></label><?php endforeach; ?></div>
                </div>
                <button type="submit" class="btn primary" style="margin-top: 20px;">Ajouter</button>
            </form>
        </section>
    <?php endif; ?>

    <!-- Import Modal -->
    <div id="importModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="importStep">Étape 1: Fichier CSV</h2>
                <button type="button" onclick="closeImport()" class="close-btn">✕</button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Upload -->
                <div id="step1" class="import-step">
                    <p>Sélectionnez un fichier Excel (.xlsx, .xls) ou CSV. Le système détectera automatiquement les
                        colonnes.</p>
                    <input type="file" id="csvFile" accept=".csv,.xlsx,.xls" onchange="loadFile()">
                    <div id="uploadStatus" style="margin-top: 10px; font-size: 12px;"></div>
                </div>

                <!-- Step 2: Mapping -->
                <div id="step2" class="import-step hidden">
                    <p>Faites correspondre les colonnes du CSV avec les champs:</p>
                    <div id="mappingUI" style="margin: 15px 0;"></div>
                    <button type="button" class="btn primary" onclick="validateMapping()">Continuer</button>
                </div>

                <!-- Step 3: Validation -->
                <div id="step3" class="import-step hidden">
                    <div id="validationResults" style="margin-bottom: 15px;"></div>
                    <div id="conflictPanel" class="hidden">
                        <h3>Conflits détectés</h3>
                        <div id="conflictList" style="max-height: 300px; overflow-y: auto;"></div>
                        <button type="button" class="btn primary" onclick="resolveConflicts()"
                            style="margin-top: 15px;">Continuer</button>
                    </div>
                </div>

                <!-- Step 4: Duplicates -->
                <div id="step4" class="import-step hidden">
                    <h3>Doublons</h3>
                    <div id="duplicateList" style="max-height: 300px; overflow-y: auto;"></div>
                    <button type="button" class="btn primary" onclick="showPreview()"
                        style="margin-top: 15px;">Aperçu</button>
                </div>

                <!-- Step 5: Preview -->
                <div id="step5" class="import-step hidden">
                    <h3>Aperçu avant import</h3>
                    <div id="previewTable"
                        style="max-height: 400px; overflow: auto; border: 1px solid #ddd; margin: 15px 0;"></div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn secondary" onclick="backToStep(4)">Retour</button>
                        <button type="button" class="btn primary" onclick="importCSV()">Importer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    .page-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-weight: bold
    }

    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb
    }

    .students-table {
        margin-bottom: 40px
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px
    }

    .section-header h2 {
        margin: 0
    }

    .header-btns {
        display: flex;
        gap: 10px
    }

    .btn-compact {
        padding: 8px 12px;
        font-size: 13px
    }

    .col-panel {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 20px;
        background: #f9f9f9;
        border-radius: 4px
    }

    .col-panel.hidden {
        display: none
    }

    .col-panel h3 {
        margin-top: 0
    }

    .col-panel label {
        display: block;
        margin-bottom: 8px;
        font-size: 12px
    }

    .stud-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px
    }

    .stud-table th,
    .stud-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        font-size: 12px
    }

    .stud-table th {
        background: #f5f5f5;
        font-weight: bold
    }

    .st-inp,
    .st-opt {
        width: 100%;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 11px
    }

    .st-inp:is(select) {
        cursor: pointer;
        background-color: white;
    }

    .st-inp.mod {
        background-color: #fff3cd
    }

    .add-form {
        border: 2px solid #007bff;
        padding: 20px;
        border-radius: 4px;
        background: #f0f7ff
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px
    }

    .form-grid div {
        display: flex;
        flex-direction: column
    }

    .form-grid label,
    .add-form label {
        font-weight: bold;
        margin-bottom: 5px;
        font-size: 13px
    }

    .form-grid input,
    .form-grid select,
    .add-form input,
    .add-form select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 12px
    }

    .chk-grp {
        display: flex;
        flex-wrap: wrap;
        gap: 15px
    }

    .chk-grp label {
        display: flex;
        align-items: center;
        margin: 0;
        font-weight: normal
    }

    .chk-grp input {
        margin-right: 5px
    }

    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s
    }

    .btn:hover {
        opacity: 0.9
    }

    .btn.primary {
        background-color: #007bff;
        color: white
    }

    .btn.secondary {
        background-color: #6c757d;
        color: white
    }

    .btn.danger {
        background-color: #dc3545;
        color: white
    }

    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999
    }

    .modal.hidden {
        display: none
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee
    }

    .modal-header h2 {
        margin: 0;
        font-size: 18px
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px
    }

    .modal-body {
        padding: 20px
    }

    .import-step {
        display: block
    }

    .import-step.hidden {
        display: none
    }

    .mapping-row {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 15px;
        margin-bottom: 10px;
        align-items: center;
        font-size: 12px
    }

    .mapping-row div {
        font-weight: 500
    }

    .mapping-row select {
        padding: 6px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 12px
    }

    .conflict-item {
        border: 1px solid #ffc107;
        padding: 10px;
        margin-bottom: 10px;
        background: #fffbea;
        border-radius: 4px;
        font-size: 12px
    }

    .conflict-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 8px
    }

    .conflict-actions select {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 11px
    }

    .duplicate-item {
        border: 1px solid #17a2b8;
        padding: 10px;
        margin-bottom: 10px;
        background: #f0f7ff;
        border-radius: 4px;
        font-size: 12px
    }

    .duplicate-item select {
        margin-left: 10px;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 11px
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px
    }

    .preview-table th,
    .preview-table td {
        border: 1px solid #ddd;
        padding: 6px;
        text-align: left
    }

    .preview-table th {
        background: #f5f5f5;
        font-weight: bold
    }

    .c1.hidden,
    .c2.hidden,
    .c3.hidden,
    .c4.hidden,
    .c5.hidden,
    .c6.hidden,
    .c7.hidden {
        display: none
    }

    [class^="opt-"].hidden {
        display: none
    }
</style>

<script>
    // Configuration API
    // En développement local: utilise http://localhost:3000
    // En production: utilise /api (proxié par le reverse proxy)
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const API_URL = isLocalhost ? 'http://localhost:3000' : '/api';

    function toggleVis() { document.getElementById('colPanel').classList.toggle('hidden') }
    document.querySelectorAll('.col-tog').forEach(t => { t.addEventListener('change', function () { document.querySelectorAll('.' + this.dataset.col).forEach(e => e.style.display = this.checked ? '' : 'none') }) });
    document.querySelectorAll('.st-inp, .st-opt').forEach(i => { i.dataset.orig = i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value; i.addEventListener('change', chk) });
    function chk() { let h = false; document.querySelectorAll('.st-inp, .st-opt').forEach(i => { let c = i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value, o = i.dataset.orig; if (c !== o) { h = true; i.classList.add('mod') } else { i.classList.remove('mod') } }); const b = document.getElementById('savBtn'); if (b) b.classList.toggle('hidden', !h) }
    function save() { const u = []; document.querySelectorAll('.st-row').forEach(r => { const d = r.dataset.id, x = {}; let c = false; r.querySelectorAll('.st-inp').forEach(i => { if (i.value !== i.dataset.orig) { x[i.name] = i.value; c = true } }); const o = []; r.querySelectorAll('.st-opt').forEach(cb => { if (cb.checked !== (cb.dataset.orig === '1')) { if (cb.checked) o.push(cb.dataset.oid); c = true } }); if (o.length) x.options = o; if (c) u.push({ student_id: d, data: x }) }); if (u.length === 0) { alert('Aucune modification'); return } const f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<input type="hidden" name="action" value="bulk_update"><input type="hidden" name="updates" value="' + JSON.stringify(u).replace(/"/g, '&quot;') + '">'; document.body.appendChild(f); f.submit() }
    function delStud(id) { if (!confirm('Confirmer la suppression ?')) return; const f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="' + id + '">'; document.body.appendChild(f); f.submit() }

    // CSV/Excel Import
    let csvData = [], columnMap = {}, validatedData = [], resolvedData = [], classOpts = [], optionOpts = [], currentConflicts = [];

    function openImport() {
        csvData = []; columnMap = {}; validatedData = []; resolvedData = [];
        ['step1', 'step2', 'step3', 'step4', 'step5'].forEach(s => document.getElementById(s).classList.add('hidden'));
        document.getElementById('step1').classList.remove('hidden');
        document.getElementById('importModal').classList.remove('hidden');
        document.getElementById('importStep').textContent = 'Étape 1: Fichier Excel/CSV';
        document.getElementById('csvFile').value = '';
        document.getElementById('uploadStatus').innerHTML = '';
    }

    function closeImport() { document.getElementById('importModal').classList.add('hidden') }

    function loadFile() {
        const file = document.getElementById('csvFile').files[0];
        if (!file) return;

        const statusEl = document.getElementById('uploadStatus');
        statusEl.innerHTML = '<span style="color:#666;">📤 Envoi du fichier...</span>';

        const formData = new FormData();
        formData.append('file', file);

        fetch(API_URL + '/upload/upload', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
            .then(r => {
                if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Erreur'); });
                return r.json();
            })
            .then(result => {
                console.log('Server response:', result);
                if (!result.ok || !result.data) {
                    throw new Error(result.error || 'Format invalide');
                }
                csvData = result.data;
                statusEl.innerHTML = '<span style="color:green;">✓ ' + result.count + ' élève(s) détecté(s)</span>';

                console.log('Detected class:', result.detectedClass, 'bool:', !!result.detectedClass);

                // Si la classe n'est pas détectée, demander à l'utilisateur
                if (!result.detectedClass) {
                    console.log('Showing class selection');
                    showClassSelectionStep();
                } else {
                    console.log('Class auto-detected, showing validation');
                    validateMapping();
                }
            })
            .catch(err => {
                statusEl.innerHTML = '<span style="color:red;">✗ ' + err.message + '</span>';
                console.error('Upload error:', err);
            });
    }

    function showClassSelectionStep() {
        // Charger les classes si nécessaire
        if (classOpts.length === 0) {
            fetch(API_URL + '/classes', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    classOpts = (data.classes || []).map(c => ({ name: c.name, id: c._id }));
                    showClassSelectionUI();
                })
                .catch(err => alert('Erreur chargement classes: ' + err.message));
        } else {
            showClassSelectionUI();
        }
    }

    function showClassSelectionUI() {
        const ui = document.getElementById('mappingUI');
        ui.innerHTML = '<p>Aucune classe détectée automatiquement. Veuillez sélectionner la classe:</p>';
        const sel = document.createElement('select');
        sel.innerHTML = '<option value="">-- Sélectionner une classe --</option>';
        classOpts.forEach(c => {
            sel.innerHTML += '<option value="' + c.name + '">' + c.name + '</option>';
        });
        sel.id = 'classSelect';
        ui.appendChild(sel);

        const btn = document.createElement('button');
        btn.className = 'btn primary';
        btn.textContent = 'Continuer';
        btn.onclick = function () {
            const selectedClass = document.getElementById('classSelect').value;
            if (!selectedClass) {
                alert('Veuillez sélectionner une classe');
                return;
            }
            // Appliquer la classe à tous les étudiants
            csvData.forEach(row => { row.className = selectedClass; });
            validateMapping();
        };
        ui.appendChild(btn);
        showStep(2);
    }

    function showStep(n) {
        ['step1', 'step2', 'step3', 'step4', 'step5'].forEach((s, i) => document.getElementById(s).classList.toggle('hidden', i + 1 !== n));
        const titles = ['', 'Fichier', 'Classe', 'Validation', 'Doublons', 'Aperçu'];
        document.getElementById('importStep').textContent = 'Étape ' + n + ': ' + titles[n];
    }

    function validateMapping() {
        // Les données sont déjà mappées par le serveur
        console.log('validateMapping called, csvData count:', csvData.length);

        // Valider que les données ont les champs requis
        try {
            csvData.forEach((row, idx) => {
                if (!row.firstName && !row.lastName) {
                    throw new Error('Ligne ' + (idx + 1) + ': Prénom ou nom manquant');
                }
                if (!row.className) {
                    throw new Error('Ligne ' + (idx + 1) + ': Classe manquante');
                }
            });
        } catch (err) {
            alert('Erreur de validation: ' + err.message);
            console.error('Validation error:', err);
            return;
        }

        validatedData = csvData;

        // Charger les classes et options
        Promise.all([
            fetch(API_URL + '/classes', { credentials: 'include' }),
            fetch(API_URL + '/options', { credentials: 'include' })
        ]).then(resps => {
            if (!resps[0].ok || !resps[1].ok) throw new Error('Erreur chargement');
            return Promise.all(resps.map(r => r.json()));
        }).then(([classData, optionData]) => {
            classOpts = (classData.classes || []).map(c => ({ name: c.name, id: c._id }));
            optionOpts = (optionData.options || []).map(o => ({ name: o.name, id: o._id }));
            checkConflicts();
        }).catch(err => {
            alert('Erreur chargement données: ' + err.message);
            console.error('Load error:', err);
        });
    }

    async function checkConflicts() {
        const conflicts = [];
        const conflictMap = {};

        validatedData.forEach((row, idx) => {
            if (!classOpts.find(c => c.name === row.className) && row.className) {
                const key = 'class-' + row.className;
                if (!conflictMap[key]) {
                    conflictMap[key] = { type: 'class', value: row.className, indices: [], resolved: false };
                    conflicts.push(conflictMap[key]);
                }
                conflictMap[key].indices.push(idx);
            }
            row.options.forEach(opt => {
                if (!optionOpts.find(o => o.name === opt)) {
                    const key = 'option-' + opt;
                    if (!conflictMap[key]) {
                        conflictMap[key] = { type: 'option', value: opt, indices: [], resolved: false };
                        conflicts.push(conflictMap[key]);
                    }
                    conflictMap[key].indices.push(idx);
                }
            });
        });

        document.getElementById('validationResults').innerHTML = '<strong>✓ ' + validatedData.length + ' élève(s) détecté(s)</strong>';

        if (conflicts.length === 0) {
            document.getElementById('conflictPanel').classList.add('hidden');
            checkDuplicates();
        } else {
            currentConflicts = conflicts;
            document.getElementById('conflictPanel').classList.remove('hidden');
            const ul = document.getElementById('conflictList');
            ul.innerHTML = '';
            
            conflicts.forEach((c, i) => {
                const item = document.createElement('div');
                item.className = 'conflict-item';
                item.dataset.conflictIdx = i;
                const type = c.type === 'class' ? 'Classe' : 'Option';
                item.innerHTML = '<strong>' + type + ':</strong> <code>' + c.value + '</code> (ligne ' + (c.indices[0] + 2) + (c.indices.length > 1 ? ' et ' + (c.indices.length - 1) + ' autre(s)' : '') + ')';
                
                const actions = document.createElement('div');
                actions.className = 'conflict-actions';
                
                const sel = document.createElement('select');
                sel.dataset.conflictIdx = i;
                sel.innerHTML = '<option value="">-- Créer \"' + c.value + '\" --</option>';
                (c.type === 'class' ? classOpts : optionOpts).forEach(x => { 
                    sel.innerHTML += '<option value="' + x.name + '">' + x.name + '</option>' 
                });
                
                sel.onchange = function () {
                    if (this.value) {
                        // Mapper les instances du conflit vers l'option existante
                        c.indices.forEach(idx => {
                            if (c.type === 'class') {
                                validatedData[idx].className = this.value;
                            } else {
                                const optIdx = validatedData[idx].options.indexOf(c.value);
                                if (optIdx !== -1) {
                                    validatedData[idx].options[optIdx] = this.value;
                                }
                            }
                        });
                        c.resolved = true;
                        item.innerHTML = '<strong>' + type + ':</strong> <code>' + c.value + '</code> → <code>' + this.value + '</code> <span style="color: green; margin-left: 10px;">✓ Mappé</span>';
                        item.removeChild(actions);
                    }
                };
                
                actions.appendChild(sel);
                item.appendChild(actions);
                ul.appendChild(item);
            });
            
            showStep(3);
        }
    }

    function createClassIfNotExists(className) {
        console.log('Attempting to create class:', className);
        return fetch(API_URL + '/classes', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: className })
        })
        .then(r => {
            console.log('Class creation response status:', r.status);
            if (!r.ok) {
                return r.text().then(text => {
                    console.log('Error response text:', text);
                    try {
                        const e = JSON.parse(text);
                        if (e.error && e.error.includes('already exists')) {
                            console.log('Class already exists:', className);
                            return { ok: true };
                        }
                        throw new Error(e.error || 'Erreur création classe');
                    } catch (parseErr) {
                        throw new Error('Erreur création classe (réponse invalide)');
                    }
                });
            }
            return r.json().then(data => {
                console.log('Class created successfully:', data);
                return { ok: true };
            });
        })
        .catch(err => {
            console.error('Class creation failed:', err);
            throw err;
        });
    }

    function createOptionIfNotExists(optionName) {
        console.log('Attempting to create option:', optionName);
        return fetch(API_URL + '/options', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: optionName })
        })
        .then(r => {
            console.log('Option creation response status:', r.status);
            if (!r.ok) {
                return r.text().then(text => {
                    console.log('Error response text:', text);
                    try {
                        const e = JSON.parse(text);
                        if (e.error && e.error.includes('already exists')) {
                            console.log('Option already exists:', optionName);
                            return { ok: true };
                        }
                        throw new Error(e.error || 'Erreur création option');
                    } catch (parseErr) {
                        throw new Error('Erreur création option (réponse invalide)');
                    }
                });
            }
            return r.json().then(data => {
                console.log('Option created successfully:', data);
                return { ok: true };
            });
        })
        .catch(err => {
            console.error('Option creation failed:', err);
            throw err;
        });
    }

    function resolveConflicts() {
        // Créer les entrées qui n'ont pas été mappées
        const toCreate = currentConflicts.filter(c => !c.resolved);
        
        if (toCreate.length === 0) {
            checkDuplicates();
            return;
        }
        
        console.log('Creating', toCreate.length, 'items');
        const createPromises = toCreate.map(item => {
            console.log('Creating', item.type, ':', item.value);
            return item.type === 'class' 
                ? createClassIfNotExists(item.value)
                : createOptionIfNotExists(item.value);
        });
        
        Promise.all(createPromises).then(results => {
            console.log('All creations done:', results);
            // Recharger les classes et options
            return Promise.all([
                fetch(API_URL + '/classes', { credentials: 'include' }),
                fetch(API_URL + '/options', { credentials: 'include' })
            ]);
        }).then(resps => {
            if (!resps[0].ok || !resps[1].ok) {
                console.error('Response not ok', resps);
                throw new Error('Erreur chargement');
            }
            return Promise.all(resps.map(r => r.json()));
        }).then(([classData, optionData]) => {
            console.log('Loaded classes:', classData.classes);
            console.log('Loaded options:', optionData.options);
            classOpts = (classData.classes || []).map(c => ({ name: c.name, id: c._id }));
            optionOpts = (optionData.options || []).map(o => ({ name: o.name, id: o._id }));
            checkDuplicates();
        }).catch(err => {
            alert('Erreur lors de la création: ' + err.message);
            console.error('Create error:', err);
        });
    }

    function checkDuplicates() {
        const dupes = [];
        validatedData.forEach((r, i) => {
            validatedData.forEach((r2, i2) => {
                if (i < i2 && r.firstName.toLowerCase() === r2.firstName.toLowerCase() && r.lastName.toLowerCase() === r2.lastName.toLowerCase()) {
                    dupes.push({ i, i2, name: r.firstName + ' ' + r.lastName });
                }
            });
        });
        if (dupes.length === 0) {
            showPreview();
            return;
        }
        const ul = document.getElementById('duplicateList');
        ul.innerHTML = '';
        dupes.forEach(d => {
            const item = document.createElement('div');
            item.className = 'duplicate-item';
            item.innerHTML = '<strong>Doublon:</strong> ' + d.name + ' (lignes ' + (d.i + 2) + ' et ' + (d.i2 + 2) + ')';
            const sel = document.createElement('select');
            sel.innerHTML = '<option value="both">Garder les deux</option><option value="' + d.i + '">Garder ligne ' + (d.i + 2) + '</option><option value="' + d.i2 + '">Garder ligne ' + (d.i2 + 2) + '</option>';
            sel.onchange = function () {
                validatedData[d.i]._skip = false;
                validatedData[d.i2]._skip = false;
                if (this.value !== 'both') {
                    validatedData[this.value === d.i ? d.i2 : d.i]._skip = true;
                }
            };
            item.appendChild(sel);
            ul.appendChild(item);
        });
        showStep(4);
    }

    function showPreview() {
        const data = validatedData.filter(r => !r._skip);
        const table = document.createElement('table');
        table.className = 'preview-table';
        table.innerHTML = '<thead><tr><th>Prénom</th><th>Nom</th><th>Classe</th><th>Genre</th><th>Âge</th></tr></thead><tbody>' + data.map(r => '<tr><td>' + r.firstName + '</td><td>' + r.lastName + '</td><td>' + r.className + '</td><td>' + r.gender + '</td><td>' + r.age + '</td></tr>').join('') + '</tbody>';
        document.getElementById('previewTable').innerHTML = '';
        document.getElementById('previewTable').appendChild(table);
        resolvedData = data.map(r => { delete r._skip; return r });
        showStep(5);
    }

    function backToStep(n) { showStep(n) }

    function importCSV() {
        // Convertir les noms d'options en IDs
        const dataWithIds = resolvedData.map(row => ({
            ...row,
            options: row.options.map(optName => {
                const opt = optionOpts.find(o => o.name === optName);
                return opt ? opt.id : optName;
            })
        }));
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="import_csv"><input type="hidden" name="csvData" value="' + JSON.stringify(dataWithIds).replace(/"/g, '&quot;') + '">';
        document.body.appendChild(form);
        form.submit();
    }
</script>
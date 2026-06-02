<?php
$userResponse = api_request('GET', '/api/auth/me');
if ($userResponse['status'] !== 200)
    redirect('/?page=login');
$userData = json_decode($userResponse['body'], true);
$userRole = $userData['user']['role'];
$canEdit = in_array($userRole, ['Administrateur', 'Editeur']);

$error = $success = '';
$students = $classes = $options = [];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit && isset($_POST['action'])) {
    if ($_POST['action'] === 'import_csv') {
        $csvData = json_decode($_POST['csvData'], true);
        $resolved = json_decode($_POST['resolved'], true) ?? [];

        $imported = 0;
        $errors = [];

        foreach ($csvData as $idx => $row) {
            $resolved_row = $resolved[$idx] ?? $row;

            $payload = [
                'className' => $resolved_row['className'] ?? '',
                'firstName' => $resolved_row['firstName'] ?? '',
                'lastName' => $resolved_row['lastName'] ?? '',
                'gender' => $resolved_row['gender'] ?? '',
                'age' => (int) ($resolved_row['age'] ?? 0),
                'dateOfBirth' => $resolved_row['dateOfBirth'] ?? '',
                'formation' => $resolved_row['formation'] ?? '',
                'options' => $resolved_row['options'] ?? [],
            ];

            if (!empty($payload['className']) && !empty($payload['firstName']) && !empty($payload['lastName'])) {
                $resp = api_request('POST', '/api/students', $payload);
                if ($resp['status'] === 201) {
                    $imported++;
                } else {
                    $errors[] = "Ligne " . ($idx + 1) . ": " . (json_decode($resp['body'], true)['error'] ?? 'Erreur');
                }
            }
        }

        if ($imported > 0) {
            $success = $imported . ' élève(s) importé(s)' . (count($errors) > 0 ? ', ' . count($errors) . ' erreur(s)' : '');
            $_GET['success'] = '1';
            $resp = api_request('GET', '/api/students');
            if ($resp['status'] === 200) {
                $students = json_decode($resp['body'], true)['students'] ?? [];
            }
        } elseif (!empty($errors)) {
            $error = implode('; ', array_slice($errors, 0, 3));
        } else {
            $error = 'Aucun élève valide à importer';
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
    } elseif ($_POST['action'] === 'delete') {
        $resp = api_request('DELETE', '/api/students/' . $_POST['student_id']);
        if ($resp['status'] === 200) {
            $success = 'Élève supprimé';
            $_GET['success'] = '1';
            $resp = api_request('GET', '/api/students');
            if ($resp['status'] === 200) {
                $students = json_decode($resp['body'], true)['students'] ?? [];
            }
        } else {
            $error = 'Erreur suppression';
        }
    } elseif ($_POST['action'] === 'bulk_update') {
        $updates = json_decode($_POST['updates'], true) ?? [];
        $count = 0;
        foreach ($updates as $u) {
            $resp = api_request('PUT', '/api/students/' . $u['student_id'], $u['data']);
            if ($resp['status'] === 200)
                $count++;
        }
        $success = $count . ' mise(s) à jour';
        $_GET['success'] = '1';
        $resp = api_request('GET', '/api/students');
        if ($resp['status'] === 200) {
            $students = json_decode($resp['body'], true)['students'] ?? [];
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
                <?php if ($canEdit): ?><button type="button" class="btn primary btn-compact" onclick="openImport()">➕
                        Importer</button><?php endif; ?>
                <?php if ($canEdit): ?><button type="button" class="btn secondary btn-compact"
                        onclick="toggleVis()">Filtrer</button><?php endif; ?>
            </div>
        </div>

        <div id="colPanel" class="col-panel hidden">
            <h3>Colonnes</h3>
            <label><input type="checkbox" class="col-tog" data-col="c1" checked> Classe</label>
            <label><input type="checkbox" class="col-tog" data-col="c2" checked> Nom</label>
            <label><input type="checkbox" class="col-tog" data-col="c3" checked> Prénom</label>
            <label><input type="checkbox" class="col-tog" data-col="c4" checked> Age</label>
            <label><input type="checkbox" class="col-tog" data-col="c5" checked> Date</label>
            <label><input type="checkbox" class="col-tog" data-col="c6" checked> Sexe</label>
            <label><input type="checkbox" class="col-tog" data-col="c7" checked> Formation</label>
            <?php foreach ($options as $o): ?><label><input type="checkbox" class="col-tog"
                        data-col="c<?php echo md5($o['_id']); ?>" checked>
                    <?php echo htmlspecialchars($o['name']); ?></label><?php endforeach; ?>
        </div>

        <?php if (empty($students)): ?>
            <p>Aucun élève.</p>
        <?php else: ?>
            <table class="students-table-data">
                <thead>
                    <tr>
                        <th class="c1">Classe</th>
                        <th class="c2">Nom</th>
                        <th class="c3">Prénom</th>
                        <th class="c4">Age</th>
                        <th class="c5">Date</th>
                        <th class="c6">Sexe</th>
                        <th class="c7">Formation</th>
                        <?php foreach ($options as $o): ?>
                            <th class="c<?php echo md5($o['_id']); ?>">
                                <?php echo htmlspecialchars(substr($o['name'], 0, 12)); ?>
                            </th><?php endforeach; ?>
                        <?php if ($canEdit): ?>
                            <th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr class="st-row" data-id="<?php echo $s['_id']; ?>">
                            <td class="c1"><select class="st-inp" name="className"
                                    data-orig="<?php echo htmlspecialchars($s['className']); ?>">
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo $s['className'] === $c['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option><?php endforeach; ?>
                                </select></td>
                            <td class="c2"><input type="text" class="st-inp" name="lastName"
                                    value="<?php echo htmlspecialchars($s['lastName']); ?>"
                                    data-orig="<?php echo htmlspecialchars($s['lastName']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                            <td class="c3"><input type="text" class="st-inp" name="firstName"
                                    value="<?php echo htmlspecialchars($s['firstName']); ?>"
                                    data-orig="<?php echo htmlspecialchars($s['firstName']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                            <td class="c4"><input type="number" class="st-inp" name="age"
                                    value="<?php echo htmlspecialchars($s['age']); ?>"
                                    data-orig="<?php echo htmlspecialchars($s['age']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                            <td class="c5"><input type="date" class="st-inp" name="dateOfBirth"
                                    value="<?php echo date('Y-m-d', strtotime($s['dateOfBirth'])); ?>"
                                    data-orig="<?php echo date('Y-m-d', strtotime($s['dateOfBirth'])); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                            <td class="c6"><select class="st-inp" name="gender"
                                    data-orig="<?php echo htmlspecialchars($s['gender']); ?>" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                    <option value="M" <?php echo $s['gender'] === 'M' ? 'selected' : ''; ?>>M</option>
                                    <option value="F" <?php echo $s['gender'] === 'F' ? 'selected' : ''; ?>>F</option>
                                    <option value="Autre" <?php echo $s['gender'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                </select></td>
                            <td class="c7"><input type="text" class="st-inp" name="formation"
                                    value="<?php echo htmlspecialchars($s['formation']); ?>"
                                    data-orig="<?php echo htmlspecialchars($s['formation']); ?>" <?php echo !$canEdit ? 'readonly' : ''; ?>></td>
                            <?php foreach ($options as $o): ?>
                                <td class="c<?php echo md5($o['_id']); ?>"><input type="checkbox" class="st-opt"
                                        data-oid="<?php echo $o['_id']; ?>"
                                        data-orig="<?php echo in_array($o['_id'], $s['options']) ? '1' : '0'; ?>" <?php echo in_array($o['_id'], $s['options']) ? 'checked' : ''; ?>             <?php echo !$canEdit ? 'disabled' : ''; ?>></td><?php endforeach; ?>
                            <?php if ($canEdit): ?>
                                <td><button type="button" class="btn danger btn-compact"
                                        onclick="delStud('<?php echo $s['_id']; ?>')">Suppr</button></td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($canEdit): ?>
                <div id="savBtn" class="save-btn hidden"><button type="button" class="btn primary"
                        onclick="save()">Enregistrer</button></div><?php endif; ?>
        <?php endif; ?>
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
                    <div><label>Sexe:</label><select name="gender" required>
                            <option value="M">M</option>
                            <option value="F">F</option>
                            <option value="Autre">Autre</option>
                        </select></div>
                    <div><label>Age:</label><input type="number" name="age" required></div>
                    <div><label>Date:</label><input type="date" name="dateOfBirth" required></div>
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
        <div class="modal-content" style="max-width: 90%; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2 id="importStep">Étape 1: Fichier CSV</h2>
                <button type="button" onclick="closeImport()" class="close-btn">✕</button>
            </div>
            <div class="modal-body">
                <!-- Step 1: File Upload -->
                <div id="step1" class="import-step">
                    <p>Sélectionnez un fichier CSV (colonnes séparées par des virgules).</p>
                    <input type="file" id="csvFile" accept=".csv" onchange="loadCSV()">
                    <div id="uploadStatus" style="margin-top: 10px;"></div>
                </div>

                <!-- Step 2: Column Mapping -->
                <div id="step2" class="import-step hidden">
                    <p>Faites correspondre les colonnes du CSV avec les champs de la base de données.</p>
                    <div id="mappingUI"></div>
                    <button type="button" class="btn primary" onclick="validateMapping()"
                        style="margin-top: 15px;">Suivant</button>
                </div>

                <!-- Step 3: Validation & Conflicts -->
                <div id="step3" class="import-step hidden">
                    <p>Vérification des données...</p>
                    <div id="validationResults"></div>
                    <div id="conflictPanel" class="hidden" style="margin-top: 15px;">
                        <h3>Résolution des conflits</h3>
                        <div id="conflictList"></div>
                        <button type="button" class="btn primary" onclick="resolveConflicts()"
                            style="margin-top: 15px;">Continuer</button>
                    </div>
                </div>

                <!-- Step 4: Duplicate Detection -->
                <div id="step4" class="import-step hidden">
                    <p>Détection des doublons...</p>
                    <div id="duplicateList"></div>
                    <button type="button" class="btn primary" onclick="showPreview()"
                        style="margin-top: 15px;">Aperçu</button>
                </div>

                <!-- Step 5: Preview -->
                <div id="step5" class="import-step hidden">
                    <p>Vérifiez les données avant importation.</p>
                    <div id="previewTable"
                        style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; margin: 15px 0;"></div>
                    <div style="text-align: right; gap: 10px;">
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
        border-radius: 4px
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

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px
    }

    .col-panel {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 20px;
        max-height: 200px;
        overflow-y: auto;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px
    }

    .col-panel h3 {
        grid-column: 1/-1;
        margin: 0 0 10px 0
    }

    .col-panel label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer
    }

    .col-panel.hidden {
        display: none
    }

    .students-table-data {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border-radius: 4px
    }

    .students-table-data thead {
        background-color: #f5f5f5;
        font-weight: 600
    }

    .students-table-data th,
    .students-table-data td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        min-width: 60px
    }

    .students-table-data tbody tr:hover {
        background-color: #f9f9f9
    }

    .st-inp {
        width: 100%;
        padding: 6px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 14px;
        box-sizing: border-box
    }

    .st-inp:focus {
        outline: none;
        border-color: #007bff
    }

    .st-inp[readonly] {
        background-color: #f5f5f5;
        cursor: not-allowed
    }

    .st-inp.mod {
        background-color: #fff3cd;
        border-color: #ffc107
    }

    .st-opt {
        cursor: pointer
    }

    .save-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background-color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        z-index: 100
    }

    .save-btn.hidden {
        display: none
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px
    }

    .form-grid div {
        display: flex;
        flex-direction: column
    }

    .form-grid label {
        margin-bottom: 5px;
        font-weight: 500
    }

    .form-grid input,
    .form-grid select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px
    }

    .chk-grp {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 10px
    }

    .add-form {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px
    }

    .add-form h2 {
        margin-top: 0;
        margin-bottom: 20px
    }

    .c1,
    .c2,
    .c3,
    .c4,
    .c5,
    .c6,
    .c7 {
        display: table-cell
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
        width: 100%;
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
        margin: 0
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
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
        align-items: end
    }

    .conflict-item {
        border: 1px solid #ffc107;
        padding: 10px;
        margin-bottom: 10px;
        background: #fffbea;
        border-radius: 4px
    }

    .conflict-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        flex-wrap: wrap
    }

    .duplicate-item {
        border: 1px solid #17a2b8;
        padding: 10px;
        margin-bottom: 10px;
        background: #f0f7ff;
        border-radius: 4px
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px
    }

    .preview-table th,
    .preview-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left
    }

    .preview-table th {
        background: #f5f5f5;
        font-weight: bold
    }

    .header-btns {
        display: flex;
        gap: 10px
    }

    .btn-compact {
        padding: 8px 12px;
        font-size: 14px
    }
</style>

<script>
    function toggleVis() { document.getElementById('colPanel').classList.toggle('hidden') }
    document.querySelectorAll('.col-tog').forEach(t => { t.addEventListener('change', function () { document.querySelectorAll('.' + this.dataset.col).forEach(e => e.style.display = this.checked ? '' : 'none') }) });
    document.querySelectorAll('.st-inp, .st-opt').forEach(i => { i.dataset.orig = i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value; i.addEventListener('change', chk) });
    function chk() { let h = false; document.querySelectorAll('.st-inp, .st-opt').forEach(i => { let c = i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value, o = i.dataset.orig; if (c !== o) { h = true; i.classList.add('mod') } else { i.classList.remove('mod') } }); const b = document.getElementById('savBtn'); if (b) b.classList.toggle('hidden', !h) }
    function save() { const u = []; document.querySelectorAll('.st-row').forEach(r => { const d = r.dataset.id, x = {}; let c = false; r.querySelectorAll('.st-inp').forEach(i => { if (i.value !== i.dataset.orig) { x[i.name] = i.value; c = true } }); const o = []; r.querySelectorAll('.st-opt').forEach(cb => { let v = cb.checked ? '1' : '0'; if (v !== cb.dataset.orig) { if (cb.checked) o.push(cb.dataset.oid); c = true } }); if (o.length) x.options = o; if (c) u.push({ student_id: d, data: x }) }); if (u.length === 0) { alert('Aucune modification'); return } const f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<input type="hidden" name="action" value="bulk_update"><input type="hidden" name="updates" value="' + JSON.stringify(u).replace(/"/g, '&quot;') + '">'; document.body.appendChild(f); f.submit() }
    function delStud(id) { if (!confirm('Confirmer ?')) return; const f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="' + id + '">'; document.body.appendChild(f); f.submit() }

    // Import CSV Functions
    let csvData = [], columnMap = {}, validatedData = [], resolvedData = [], classOptions = [], optionOptions = [];

    function openImport() {
        csvData = []; columnMap = {}; validatedData = []; resolvedData = [];
        document.getElementById('importModal').classList.remove('hidden');
        document.getElementById('step1').classList.remove('hidden');
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('step3').classList.add('hidden');
        document.getElementById('step4').classList.add('hidden');
        document.getElementById('step5').classList.add('hidden');
        document.getElementById('importStep').textContent = 'Étape 1: Fichier CSV';
    }

    function closeImport() { document.getElementById('importModal').classList.add('hidden'); }

    function loadCSV() {
        const file = document.getElementById('csvFile').files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const text = e.target.result;
                const lines = text.split('\n').filter(l => l.trim());
                if (lines.length < 1) throw new Error('Fichier vide');

                const headers = lines[0].split(',').map(h => h.trim());
                csvData = [];

                for (let i = 1; i < lines.length; i++) {
                    const values = lines[i].split(',').map(v => v.trim());
                    const row = {};
                    headers.forEach((h, idx) => { row[h] = values[idx] || ''; });
                    csvData.push(row);
                }

                document.getElementById('uploadStatus').innerHTML = '<span style="color:green;">✓ ' + csvData.length + ' ligne(s) chargée(s)</span>';
                showStep(2, headers);
            } catch (err) {
                document.getElementById('uploadStatus').innerHTML = '<span style="color:red;">✗ Erreur: ' + err.message + '</span>';
            }
        };
        reader.readAsText(file);
    }

    function showStep(n, headers = null) {
        for (let i = 1; i <= 5; i++) {
            document.getElementById('step' + i).classList.toggle('hidden', i !== n);
        }
        document.getElementById('importStep').textContent = ['', 'Étape 1: Fichier', 'Étape 2: Colonnes', 'Étape 3: Validation', 'Étape 4: Doublons', 'Étape 5: Aperçu'][n];

        if (n === 2 && headers) {
            const ui = document.getElementById('mappingUI');
            ui.innerHTML = '';
            const fields = ['firstName', 'lastName', 'className', 'gender', 'age', 'dateOfBirth', 'formation', 'options'];
            headers.forEach(h => {
                const row = document.createElement('div');
                row.className = 'mapping-row';
                const sel = document.createElement('select');
                sel.innerHTML = '<option value="">-- Non utilisé --</option>' + fields.map(f => '<option value="' + f + '"' + (autoDetectField(h) === f ? ' selected' : '') + '>' + f + '</option>').join('');
                const storage = document.createElement('div');
                storage.textContent = h;
                row.appendChild(storage);
                row.appendChild(sel);
                ui.appendChild(row);
                columnMap[h] = '';
                sel.addEventListener('change', function () { columnMap[h] = this.value; });
            });
        }
    }

    function autoDetectField(header) {
        const h = header.toLowerCase();
        if (h.includes('nom') && h.includes('prénom')) return 'fullName';
        if (h.includes('prénom') || h.includes('first')) return 'firstName';
        if (h.includes('nom') || h.includes('last')) return 'lastName';
        if (h.includes('classe') || h.includes('class')) return 'className';
        if (h.includes('genre') || h.includes('gender') || h.includes('sexe')) return 'gender';
        if (h.includes('age')) return 'age';
        if (h.includes('date')) return 'dateOfBirth';
        if (h.includes('formation')) return 'formation';
        if (h.includes('option')) return 'options';
        return '';
    }

    function backToStep(n) { showStep(n); }

    function validateMapping() {
        const fields = Object.values(columnMap).filter(f => f);
        if (!fields.includes('firstName') || !fields.includes('lastName') || !fields.includes('className')) {
            alert('Champs requis: Prénom, Nom, Classe');
            return;
        }

        validatedData = csvData.map(row => {
            const out = { firstName: '', lastName: '', className: '', gender: '', age: 0, dateOfBirth: '', formation: '', options: [], _errors: [] };
            const reverseMap = {};
            for (const [k, v] of Object.entries(columnMap)) reverseMap[v] = k;

            for (const field of Object.keys(out)) {
                if (field === '_errors') continue;
                const col = reverseMap[field];
                if (!col) continue;
                const val = row[col] || '';

                if (field === 'fullName' || (field === 'firstName' && reverseMap['firstName'] === null && val.includes(' '))) {
                    const parts = val.split(' ');
                    out.firstName = parts.pop() || '';
                    out.lastName = parts.join(' ') || '';
                } else if (field === 'firstName') {
                    out.firstName = val;
                } else if (field === 'lastName') {
                    out.lastName = val;
                } else if (field === 'age') {
                    out.age = parseInt(val) || 0;
                } else if (field === 'options') {
                    out.options = val ? val.split(';').map(o => o.trim()).filter(o => o) : [];
                } else {
                    out[field] = val;
                }
            }

            if (!out.firstName) out._errors.push('Prénom manquant');
            if (!out.lastName) out._errors.push('Nom manquant');
            if (!out.className) out._errors.push('Classe manquante');

            return out;
        });

        // Fetch classes and options for validation
        fetch('/api_proxy.php?method=GET&endpoint=/api/classes').then(r => r.json()).then(d => {
            classOptions = d.classes || [];
            return fetch('/api_proxy.php?method=GET&endpoint=/api/options');
        }).then(r => r.json()).then(d => {
            optionOptions = d.options || [];
            checkConflicts();
        }).catch(err => console.error(err));
    }

    function checkConflicts() {
        const conflicts = [];
        validatedData.forEach((row, idx) => {
            const classExists = classOptions.some(c => c.name === row.className);
            if (!classExists && row.className) {
                conflicts.push({ idx, type: 'class', value: row.className });
            }
            row.options.forEach(opt => {
                const optExists = optionOptions.some(o => o.name === opt);
                if (!optExists) {
                    conflicts.push({ idx, type: 'option', value: opt, row_opt: opt });
                }
            });
        });

        document.getElementById('validationResults').innerHTML = '<span>' + validatedData.length + ' ligne(s) à importer</span>';

        if (conflicts.length > 0) {
            document.getElementById('conflictPanel').classList.remove('hidden');
            const ul = document.getElementById('conflictList');
            ul.innerHTML = '';
            conflicts.forEach(c => {
                const item = document.createElement('div');
                item.className = 'conflict-item';
                const lbl = c.type === 'class' ? 'Classe non trouvée' : 'Option non trouvée';
                item.innerHTML = '<strong>' + lbl + ':</strong> "' + c.value + '" (ligne ' + (c.idx + 2) + ')';
                const sel = document.createElement('select');
                sel.innerHTML = '<option value="">-- Créer --</option>';
                if (c.type === 'class') {
                    classOptions.forEach(cl => {
                        sel.innerHTML += '<option value="' + cl.name + '">' + cl.name + '</option>';
                    });
                } else {
                    optionOptions.forEach(o => {
                        sel.innerHTML += '<option value="' + o.name + '">' + o.name + '</option>';
                    });
                }
                const actions = document.createElement('div');
                actions.className = 'conflict-actions';
                actions.innerHTML = '<label>Remplacer par: </label>';
                actions.appendChild(sel);
                item.appendChild(actions);

                sel.addEventListener('change', function () {
                    conflicts.forEach((co, cidx) => {
                        if (co === c) {
                            if (this.value) {
                                c.resolution = this.value;
                            } else {
                                delete c.resolution;
                            }
                        }
                    });
                });

                ul.appendChild(item);
            });
        } else {
            document.getElementById('conflictPanel').classList.add('hidden');
            showStep(4);
        }

        showStep(3);
    }

    function resolveConflicts() {
        validatedData.forEach(row => {
            // Resolve class
            const classResolution = document.getElementById('conflictList').querySelector('select[value]')?.value;
            // Simple resolution placeholder - in production this would be more sophisticated
        });
        checkDuplicates();
    }

    function checkDuplicates() {
        const dupes = [];
        validatedData.forEach((row, idx) => {
            validatedData.forEach((row2, idx2) => {
                if (idx < idx2 && row.firstName === row2.firstName && row.lastName === row2.lastName) {
                    dupes.push({ idx, idx2, name: row.firstName + ' ' + row.lastName });
                }
            });
        });

        if (dupes.length > 0) {
            const ul = document.getElementById('duplicateList');
            ul.innerHTML = '';
            dupes.forEach(d => {
                const item = document.createElement('div');
                item.className = 'duplicate-item';
                item.innerHTML = '<strong>Doublon détecté:</strong> ' + d.name + ' (lignes ' + (d.idx + 2) + ' et ' + (d.idx2 + 2) + ')';
                const sel = document.createElement('select');
                sel.innerHTML = '<option value="keep-both">Garder les deux</option><option value="keep-1">Garder ligne ' + (d.idx + 2) + '</option><option value="keep-2">Garder ligne ' + (d.idx2 + 2) + '</option>';
                sel.addEventListener('change', function () {
                    if (this.value === 'keep-1') {
                        validatedData[d.idx2]._skip = true;
                    } else if (this.value === 'keep-2') {
                        validatedData[d.idx]._skip = true;
                    }
                });
                item.appendChild(sel);
                ul.appendChild(item);
            });
        }

        showStep(4);
    }

    function showPreview() {
        const preview = validatedData.filter(r => !r._skip).map(r => ({ ...r }));
        const table = document.createElement('table');
        table.className = 'preview-table';
        table.innerHTML = '<thead><tr><th>Prénom</th><th>Nom</th><th>Classe</th><th>Genre</th><th>Âge</th></tr></thead><tbody>' + preview.map(r => '<tr><td>' + r.firstName + '</td><td>' + r.lastName + '</td><td>' + r.className + '</td><td>' + r.gender + '</td><td>' + r.age + '</td></tr>').join('') + '</tbody>';
        document.getElementById('previewTable').innerHTML = '';
        document.getElementById('previewTable').appendChild(table);
        resolvedData = preview;
        showStep(5);
    }

    function importCSV() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="import_csv"><input type="hidden" name="csvData" value="' + JSON.stringify(resolvedData).replace(/"/g, '&quot;') + '"><input type="hidden" name="resolved" value="' + JSON.stringify({}).replace(/"/g, '&quot;') + '">';
        document.body.appendChild(form);
        form.submit();
    }
</script>
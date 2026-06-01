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
    if ($_POST['action'] === 'create') {
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
            <?php if ($canEdit): ?><button type="button" class="btn secondary btn-compact" onclick="toggleVis()">Filtrer
                    les colonnes</button><?php endif; ?>
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
                <button type="submit" class="btn primary" style="margin-top: 20px;" >Ajouter</button>
            </form>
        </section>
    <?php endif; ?>
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
</style>

<script>
    function toggleVis() { document.getElementById('colPanel').classList.toggle('hidden') }
    document.querySelectorAll('.col-tog').forEach(t => { t.addEventListener('change', function () { document.querySelectorAll('.' + this.dataset.col).forEach(e => e.style.display = this.checked ? '' : 'none') }) });
    document.querySelectorAll('.st-inp, .st-opt').forEach(i => { i.dataset.orig = i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value; i.addEventListener('change', chk) });
    function chk() { let h = false; document.querySelectorAll('.st-inp, .st-opt').forEach(i => { let c = i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value, o = i.dataset.orig; if (c !== o) { h = true; i.classList.add('mod') } else { i.classList.remove('mod') } }); const b = document.getElementById('savBtn'); if (b) b.classList.toggle('hidden', !h) }
    function save() { const u = []; document.querySelectorAll('.st-row').forEach(r => { const d = r.dataset.id, x = {}; let c = false; r.querySelectorAll('.st-inp').forEach(i => { if (i.value !== i.dataset.orig) { x[i.name] = i.value; c = true } }); const o = []; r.querySelectorAll('.st-opt').forEach(cb => { let v = cb.checked ? '1' : '0'; if (v !== cb.dataset.orig) { if (cb.checked) o.push(cb.dataset.oid); c = true } }); if (o.length) x.options = o; if (c) u.push({ student_id: d, data: x }) }); if (u.length === 0) { alert('Aucune modification'); return } const f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<input type="hidden" name="action" value="bulk_update"><input type="hidden" name="updates" value="' + JSON.stringify(u).replace(/"/g, '&quot;') + '">'; document.body.appendChild(f); f.submit() }
    function delStud(id) { if (!confirm('Confirmer ?')) return; const f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="' + id + '">'; document.body.appendChild(f); f.submit() }
</script>
<?php
/* ── admin/manage_pathways.php ── Pathway Recommendation Settings ── */
$activeNav = 'pathways';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$qPerGroup = $settings['pathway_questions_per_group'] ?? 3;
?>

<div class="content-container">
    <div class="header-flex">
        <div>
            <h1>Pathway Recommendation Management</h1>
            <p class="subtitle">Configure interest survey questions and randomization settings for Grade 10 transitions.</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">+ Add New Question</button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php
                if ($success === 'saved') echo 'Question saved successfully!';
                elseif ($success === 'deleted') echo 'Question removed.';
                elseif ($success === 'settings_updated') echo 'Settings updated.';
                elseif ($success === 'survey_launched') echo '🚀 Survey launched and announcements sent to the selected grade!';
            ?>
        </div>
    <?php endif; ?>

    <!-- Settings Card -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px">
        <div class="card">
            <div class="card-header">
                <h3>⚙️ Survey Configuration</h3>
            </div>
            <div class="card-body">
                <form action="?url=admin/updatePathwaySettings" method="POST" class="settings-form">
                    <div class="form-group">
                        <label>Random Questions Per Group:</label>
                        <div style="display:flex; gap:10px; align-items:center">
                            <input type="number" name="questions_per_group" value="<?= (int)$qPerGroup ?>" min="1" max="20" class="form-control" style="width:80px">
                            <button type="submit" class="btn btn-outline" style="white-space:nowrap">Update</button>
                        </div>
                        <p class="muted" style="margin-top:8px; font-size:.75rem">Default: 3 per pathway. Total = 3 groups × count.</p>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" style="border: 1px solid #c7d2fe; background: #f5f3ff">
            <div class="card-header" style="background: rgba(199, 210, 254, 0.2)">
                <h3 style="color:#4338ca">🚀 Launch Survey for Grade</h3>
            </div>
            <div class="card-body">
                <form action="?url=admin/launchPathwaySurvey" method="POST">
                    <div class="form-group">
                        <label>Target Grade / Class</label>
                        <div style="display:flex; gap:10px">
                            <select name="grade" class="form-control" required style="flex:1">
                                <option value="">Select a grade...</option>
                                <?php foreach ($grades as $g): ?>
                                    <option value="<?= htmlspecialchars($g['label']) ?>"><?= htmlspecialchars($g['label']) ?> (<?= $g['value'] ?> Students)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('This will archive current interest profiles for this grade and notify students to take the survey again. Continue?')">Launch</button>
                        </div>
                        <p class="muted" style="margin-top:8px; font-size:.75rem">Students will receive an announcement and the survey will be unlocked for them.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Questions Table -->
    <div class="card">
        <div class="card-header">
            <h3>📋 Question Pool</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Target Pathway</th>
                        <th>Question Text</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:40px">No questions in the pool.</td></tr>
                    <?php else: ?>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td>#<?= $q['id'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $q['target_pathway'] ?>">
                                        <?= strtoupper(str_replace('_', ' ', $q['target_pathway'])) ?>
                                    </span>
                                </td>
                                <td style="font-weight:500"><?= htmlspecialchars($q['question']) ?></td>
                                <td style="text-align:right">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline" onclick='openEditModal(<?= json_encode($q) ?>)'>Edit</button>
                                        <form action="?url=admin/removePathwayQuestion" method="POST" style="display:inline" onsubmit="return confirm('Delete this question?')">
                                            <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="pwModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Pathway Question</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form action="?url=admin/savePathwayQuestion" method="POST">
            <input type="hidden" name="id" id="q_id" value="">
            <div class="form-group">
                <label>Target Pathway Group</label>
                <select name="target_pathway" id="q_pathway" class="form-control" required>
                    <option value="stem">STEM (Science, Tech, Engineering & Math)</option>
                    <option value="social_sciences">Social Sciences / Humanities</option>
                    <option value="arts_sports">Arts & Sports Science</option>
                </select>
            </div>
            <div class="form-group">
                <label>Question Statement</label>
                <textarea name="question" id="q_text" class="form-control" rows="4" placeholder="e.g. I enjoy solving complex mathematical problems..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Question</button>
            </div>
        </form>
    </div>
</div>

<style>
.mb-24 { margin-bottom: 24px; }
.form-group-inline { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; font-weight: 500; }
.settings-form .btn-outline { padding: 8px 24px; }
.badge-stem { background: #e0e7ff; color: #4338ca; }
.badge-social_sciences { background: #fef3c7; color: #92400e; }
.badge-arts_sports { background: #fce7f3; color: #9d174d; }
.data-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; }
.btn-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.btn-danger:hover { background: #fecaca; }

/* Modal Styles mirroring existing admin theme if possible */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.modal-content { background: #fff; margin: 10% auto; padding: 0; width: 500px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; animation: slideDown 0.3s ease; }
@keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.modal-header h2 { font-size: 1.25rem; margin: 0; }
.close { cursor: pointer; font-size: 1.5rem; color: #94a3b8; }
form { padding: 24px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: #475569; }
.form-control { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = "Add Pathway Question";
    document.getElementById('q_id').value = "";
    document.getElementById('q_text').value = "";
    document.getElementById('q_pathway').value = "stem";
    document.getElementById('pwModal').style.display = 'block';
}
function openEditModal(q) {
    document.getElementById('modalTitle').innerText = "Edit Pathway Question";
    document.getElementById('q_id').value = q.id;
    document.getElementById('q_text').value = q.question;
    document.getElementById('q_pathway').value = q.target_pathway;
    document.getElementById('pwModal').style.display = 'block';
}
function closeModal() {
    document.getElementById('pwModal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('pwModal')) closeModal();
}
</script>

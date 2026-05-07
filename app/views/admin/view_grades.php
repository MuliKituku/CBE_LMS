<?php
$activeNav = 'grades';
require_once BASE_PATH . '/app/views/admin/_sidebar.php';
?>

<div class="action-bar" style="margin-bottom: 24px;">
    <div class="search-box" style="flex: 1;">
        <input type="text" id="gradeSearch" placeholder="Search by student, reg no, or assessment..." onkeyup="filterGrades()" style="width: 100%;">
    </div>
</div>

<div class="table-container">
    <table class="data-table" id="gradesTable">
        <thead>
            <tr>
                <th>Student</th>
                <th>Assessment</th>
                <th>Teacher</th>
                <th>Score</th>
                <th>Status</th>
                <th>Graded By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grades as $g): 
                $statusColor = $g['status'] === 'graded' ? '#10b981' : ($g['status'] === 'submitted' ? '#3b82f6' : '#94a3b8');
            ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($g['student_name']) ?></div>
                        <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($g['student_reg_no']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 500;"><?= htmlspecialchars($g['assessment_title']) ?></div>
                        <div style="font-size: 0.75rem; color: #94a3b8;"><?= htmlspecialchars($g['subject']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($g['teacher_name']) ?></td>
                    <td>
                        <span style="font-size: 1.1rem; font-weight: 700; color: #1e293b;"><?= $g['score'] ?? '-' ?>%</span>
                    </td>
                    <td>
                        <span class="badge" style="background: <?= $statusColor ?>20; color: <?= $statusColor ?>; border: 1px solid <?= $statusColor ?>40;">
                            <?= ucfirst($g['status']) ?>
                        </span>
                    </td>
                    <td style="font-size: 0.85rem; color: #64748b;">
                        <?= htmlspecialchars($g['graded_by_name'] ?: 'System/Teacher') ?>
                    </td>
                    <td>
                        <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;" 
                                onclick="openOverrideModal(<?= $g['student_id'] ?>, <?= $g['assessment_id'] ?>, '<?= htmlspecialchars($g['student_name']) ?>', '<?= htmlspecialchars($g['assessment_title']) ?>', <?= $g['score'] ?? 0 ?>)">
                            Override
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Override Modal -->
<div id="overrideModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Override Grade</h3>
            <span class="close" onclick="toggleModal('overrideModal')">&times;</span>
        </div>
        <div style="margin: 16px 0; padding: 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: 0.85rem; color: #92400e;">
            <strong>Student:</strong> <span id="ov_student"></span><br>
            <strong>Assessment:</strong> <span id="ov_assessment"></span>
        </div>
        <form action="/CBE_LMS/public/index.php?url=admin/overrideGrade" method="POST">
            <input type="hidden" name="student_id" id="ov_student_id">
            <input type="hidden" name="assessment_id" id="ov_assessment_id">
            <div class="form-group">
                <label>New Score (%)</label>
                <input type="number" name="new_score" id="ov_score" min="0" max="100" required 
                       style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1.2rem; font-weight: 600;">
            </div>
            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('overrideModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: #f59e0b;">Save Override</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterGrades() {
    const input = document.getElementById('gradeSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#gradesTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(input) ? '' : 'none';
    });
}

function openOverrideModal(sId, aId, sName, aTitle, currentScore) {
    document.getElementById('ov_student_id').value = sId;
    document.getElementById('ov_assessment_id').value = aId;
    document.getElementById('ov_student').textContent = sName;
    document.getElementById('ov_assessment').textContent = aTitle;
    document.getElementById('ov_score').value = currentScore;
    toggleModal('overrideModal');
}

function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>

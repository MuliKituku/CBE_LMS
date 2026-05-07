<?php
/* ── teacher/grading.php ───────────────────────────────────
   Variables: $profile, $pending
   ─────────────────────────────────────────────────────────── */
$activeNav = 'grading';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$success = $_GET['success'] ?? '';
?>

<?php if ($success === 'graded'): ?>
<div class="alert alert-success">✔️ Grades saved and CBE competencies updated!</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h2 style="color:var(--text);margin:0">Pending Grading</h2>
    <div style="display:flex;gap:10px">
        <span class="badge badge-warning" style="font-size:0.9rem;padding:6px 12px"><?= count($pending) ?> Assessments</span>
        <span class="badge badge-info" style="font-size:0.9rem;padding:6px 12px;background:#0ea5e9"><?= count($pendingActivities) ?> Lesson Activities</span>
    </div>
</div>

<!-- Tabs Navigation -->
<div style="display:flex;gap:20px;border-bottom:1px solid #e2e8f0;margin-bottom:24px">
    <button onclick="showTab('assessments')" id="tab-assessments" class="grading-tab active" style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;color:var(--primary);border-bottom:3px solid var(--primary)">Assessments (Quizzes)</button>
    <button onclick="showTab('activities')" id="tab-activities" class="grading-tab" style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;color:#64748b">Lesson Activities</button>
</div>

<!-- Assessments Tab -->
<div id="content-assessments" class="tab-content">
    <div class="card">
        <div class="table-wrapper">
            <?php if (empty($pending)): ?>
            <div style="padding:40px;text-align:center;color:var(--muted)">
                <div style="font-size:2.5rem;margin-bottom:10px">🎉</div>
                No pending assessments.
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Assessment</th>
                        <th>Submitted On</th>
                        <th style="width:200px;text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $p): ?>
                    <tr>
                        <td><div style="font-weight:600;color:var(--text)"><?= htmlspecialchars($p['student_name']) ?></div></td>
                        <td>
                            <div style="font-size:0.95rem"><?= htmlspecialchars($p['assessment_title']) ?></div>
                        </td>
                        <td><span style="font-size:0.85rem;color:#64748b"><?= date('d M Y, h:i A', strtotime($p['submitted_at'])) ?></span></td>
                        <td style="text-align:right">
                            <a href="/CBE_LMS/public/index.php?url=teacher/reviewSubmission&student_id=<?= $p['student_id'] ?>&assessment_id=<?= $p['assessment_id'] ?>"
                               class="btn btn-primary btn-sm">🔍 Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Activities Tab -->
<div id="content-activities" class="tab-content" style="display:none">
    <div class="card">
        <div class="table-wrapper">
            <?php if (empty($pendingActivities)): ?>
            <div style="padding:40px;text-align:center;color:var(--muted)">
                <div style="font-size:2.5rem;margin-bottom:10px">📂</div>
                No pending lesson activities.
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Lesson Strand</th>
                        <th>Activity Title</th>
                        <th>Submitted On</th>
                        <th style="width:200px;text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingActivities as $a): ?>
                    <tr>
                        <td><div style="font-weight:600;color:var(--text)"><?= htmlspecialchars($a['student_name']) ?></div></td>
                        <td><div style="font-size:0.95rem"><?= htmlspecialchars($a['lesson_strand']) ?></div></td>
                        <td><div style="font-size:0.95rem"><?= htmlspecialchars($a['activity_title']) ?></div></td>
                        <td><span style="font-size:0.85rem;color:#64748b"><?= date('d M Y, h:i A', strtotime($a['submitted_at'])) ?></span></td>
                        <td style="text-align:right">
                            <a href="/CBE_LMS/public/index.php?url=teacher/reviewLessonSubmission&student_id=<?= $a['student_id'] ?>&lesson_id=<?= $a['lesson_id'] ?>"
                               class="btn btn-primary btn-sm" style="background:#0ea5e9">🔍 Review & Grade</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById('content-' + tab).style.display = 'block';
    
    document.querySelectorAll('.grading-tab').forEach(el => {
        el.style.color = '#64748b';
        el.style.borderBottom = 'none';
    });
    
    const active = document.getElementById('tab-' + tab);
    active.style.color = tab === 'assessments' ? 'var(--primary)' : '#0ea5e9';
    active.style.borderBottom = '3px solid ' + (tab === 'assessments' ? 'var(--primary)' : '#0ea5e9');
}
</script>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>

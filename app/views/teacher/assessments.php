<?php
/* ── teacher/assessments.php ───────────────────────────────
   Variables: $profile, $assessments, $lessons
   ─────────────────────────────────────────────────────────── */
$activeNav = 'assessments';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>

<!-- Alerts -->
<?php if ($success === 'created'): ?>
<div class="alert alert-success">✔️ Assessment created successfully!</div>
<?php elseif ($success === 'deleted'): ?>
<div class="alert alert-success">✔️ Assessment deleted.</div>
<?php elseif ($error === 'missing_fields'): ?>
<div class="alert alert-error">❌ Title, Opening Time, and Closing Time are strictly required.</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h2 style="color:var(--text);margin:0">Quizzes & Assessments</h2>
    <button class="btn btn-primary" onclick="document.getElementById('createModal').style.display='flex'" style="background:var(--primary);border:none">
        + Create Assessment
    </button>
</div>

<!-- CREATE MODAL overlay -->
<div id="createModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px">
    <div class="card" style="width:100%;max-width:550px;max-height:90vh;overflow-y:auto;padding:32px;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,0.2)">
        <h2 style="margin-top:0">New Assessment</h2>
        <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/createAssessment">
            <div style="margin-bottom:16px">
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Assessment Title <span style="color:#ef4444">*</span></label>
                <input type="text" name="title" class="form-control" required placeholder="e.g. End of Term Geometry Exam">
            </div>
            
            <div style="margin-bottom:16px">
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Link to Lesson (Optional)</label>
                <select name="lesson_id" id="lesson_select" class="form-control" onchange="updateLinkedFields(this)">
                    <option value="">-- Standalone Assessment --</option>
                    <?php foreach ($lessons as $l): ?>
                        <option value="<?= $l['id'] ?>" data-grade="<?= $l['class_grade'] ?>" data-subject="<?= $l['subject'] ?>">
                            <?= htmlspecialchars($l['strand']) ?> (Grade <?= $l['class_grade'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:0.8rem;color:var(--muted);margin-top:6px;display:none" id="lesson_hint">
                    Competencies will be inherited from the linked lesson.
                </div>
            </div>

            <!-- STANDALONE COMPETENCIES -->
            <div id="standalone_competencies" style="margin-bottom:16px">
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Select Target CBC Competencies <span style="font-size:0.75rem;font-weight:normal;color:var(--muted)">(Standalone only)</span></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;max-height:150px;overflow-y:auto">
                    <?php foreach ($coreCompetencesList ?? [] as $comp): ?>
                    <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer">
                        <input type="checkbox" name="core_competences[]" value="<?= $comp['id'] ?>">
                        <?= htmlspecialchars($comp['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:16px">
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Grade <span style="color:#ef4444">*</span></label>
                    <input type="number" name="class_grade" id="field_grade" class="form-control" required placeholder="e.g. 7">
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Subject <span style="color:#ef4444">*</span></label>
                    <input type="text" name="subject" id="field_subject" class="form-control" required placeholder="e.g. Math">
                </div>
            </div>

            <div style="margin-bottom:16px">
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Instructions</label>
                <textarea name="instructions" class="form-control" rows="3" placeholder="Read carefully before starting..."></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:16px">
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Duration (Mins)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="60" min="1">
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Max Attempts</label>
                    <input type="number" name="max_attempts" class="form-control" value="1" min="1">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:16px">
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Opening Time <span style="color:#ef4444">*</span></label>
                    <input type="datetime-local" name="available_from" class="form-control" required>
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Closing Time <span style="color:#ef4444">*</span></label>
                    <input type="datetime-local" name="available_until" class="form-control" required>
                </div>
            </div>

            <div style="margin-bottom:24px">
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Due Date (Display Only)</label>
                <input type="datetime-local" name="due_date" class="form-control">
            </div>

            <div style="display:flex;gap:12px;justify-content:flex-end">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('createModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:var(--primary);border:none">Create Task</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateLinkedFields(select) {
    const option = select.options[select.selectedIndex];
    const lessonHint = document.getElementById('lesson_hint');
    const saDiv = document.getElementById('standalone_competencies');

    if (option.value) { // Linked to lesson
        document.getElementById('field_grade').value = option.getAttribute('data-grade');
        document.getElementById('field_subject').value = option.getAttribute('data-subject');
        if (lessonHint) lessonHint.style.display = 'block';
        if (saDiv) {
            saDiv.style.opacity = '0.5';
            saDiv.style.pointerEvents = 'none';
            saDiv.querySelectorAll('input').forEach(i => i.checked = false);
        }
    } else { // Standalone
        if (lessonHint) lessonHint.style.display = 'none';
        if (saDiv) {
            saDiv.style.opacity = '1';
            saDiv.style.pointerEvents = 'auto';
        }
    }
}
</script>

<div class="card">
    <div class="table-wrapper">
        <?php if (empty($assessments)): ?>
        <div style="padding:40px;text-align:center;color:var(--muted)">
            No quizzes or assignments created yet.
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title & Linked Lesson</th>
                    <th>Teacher / Instructions</th>
                    <th style="width:250px">Assessment Window</th>
                    <th style="width:100px;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assessments as $a): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--text)"><?= htmlspecialchars($a['title']) ?></div>
                        <div style="font-size:0.8rem;color:var(--primary);margin-top:4px;font-weight:600">
                            <?= $a['lesson_id'] ? '🔗 ' . htmlspecialchars($a['lesson_strand']) : 'Standalone' ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;color:var(--muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                            <?= htmlspecialchars($a['instructions']) ?: 'No instructions provided.' ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;margin-bottom:4px">
                            <span style="color:var(--muted)">Opens:</span> 
                            <span style="font-weight:600"><?= date('M d, H:i', strtotime($a['available_from'])) ?></span>
                        </div>
                        <div style="font-size:0.85rem;margin-bottom:4px">
                            <span style="color:var(--muted)">Closes:</span> 
                            <span style="font-weight:600;color:#ef4444"><?= date('M d, H:i', strtotime($a['available_until'])) ?></span>
                        </div>
                        <div style="font-size:0.75rem;margin-top:8px;background:#f1f5f9;display:inline-block;padding:2px 8px;border-radius:4px;color:#475569">
                            ⏱ <?= (int)$a['duration_minutes'] ?> mins | 🔄 <?= (int)$a['max_attempts'] ?> attempts
                        </div>
                    </td>
                    <td style="text-align:right">
                        <a href="/CBE_LMS/public/index.php?url=teacher/buildAssessment/<?= $a['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--primary);border-color:var(--primary);margin-right:5px">Build Quiz</a>
                        <a href="/CBE_LMS/public/index.php?url=teacher/editAssessment/<?= $a['id'] ?>" class="btn btn-outline btn-sm" style="color:#f59e0b;border-color:#f59e0b;margin-right:5px">Edit</a>
                        <a href="/CBE_LMS/public/index.php?url=teacher/deleteAssessment/<?= $a['id'] ?>" class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#ef4444" onclick="return confirm('Are you sure you want to delete this assessment?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>

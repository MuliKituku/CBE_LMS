<?php
/* ── teacher/edit_assessment.php ───────────────────────────────
   Variables: $profile, $assessment, $lessons, $coreCompetencesList
   ─────────────────────────────────────────────────────────── */
$activeNav = 'assessments';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$error = $_GET['error'] ?? '';
?>

<?php if ($error === 'missing_fields'): ?>
<div class="alert alert-error">❌ Title, Opening Time, and Closing Time are strictly required.</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:16px">
        <a href="/CBE_LMS/public/index.php?url=teacher/assessments" class="btn btn-outline" style="padding:6px 12px;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center">←</a>
        <h2 style="color:var(--text);margin:0">Edit Assessment</h2>
    </div>
</div>

<div class="card" style="max-width:800px;padding:32px">
    <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/updateAssessment">
        <input type="hidden" name="id" value="<?= $assessment['id'] ?>">

        <div style="margin-bottom:16px">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Assessment Title <span style="color:#ef4444">*</span></label>
            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($assessment['title']) ?>">
        </div>
        
        <div style="margin-bottom:16px">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Link to Lesson (Optional)</label>
            <select name="lesson_id" id="lesson_select" class="form-control" onchange="updateLinkedFields(this)">
                <option value="">-- Standalone Assessment --</option>
                <?php foreach ($lessons as $l): ?>
                    <option value="<?= $l['id'] ?>" data-grade="<?= $l['class_grade'] ?>" data-subject="<?= $l['subject'] ?>" <?= $assessment['lesson_id']==$l['id']?'selected':'' ?>>
                        <?= htmlspecialchars($l['strand']) ?> (Grade <?= $l['class_grade'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div style="font-size:0.8rem;color:var(--muted);margin-top:6px;<?= $assessment['lesson_id']?'display:block':'display:none' ?>" id="lesson_hint">
                Competencies will be inherited from the linked lesson.
            </div>
        </div>

        <!-- STANDALONE COMPETENCIES -->
        <div id="standalone_competencies" style="margin-bottom:16px; <?= $assessment['lesson_id']?'opacity:0.5;pointer-events:none':'' ?>">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Select Target CBC Competencies <span style="font-size:0.75rem;font-weight:normal;color:var(--muted)">(Standalone only)</span></label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;max-height:150px;overflow-y:auto">
                <?php $myComps = $assessment['core_competences'] ?? []; ?>
                <?php foreach ($coreCompetencesList ?? [] as $comp): ?>
                <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer">
                    <input type="checkbox" name="core_competences[]" value="<?= $comp['id'] ?>" <?= in_array($comp['id'],$myComps)?'checked':'' ?>>
                    <?= htmlspecialchars($comp['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:16px">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Grade <span style="color:#ef4444">*</span></label>
                <input type="text" name="class_grade" id="field_grade" class="form-control" required value="<?= htmlspecialchars(str_replace('Grade ','',$assessment['class_grade'])) ?>">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Subject <span style="color:#ef4444">*</span></label>
                <input type="text" name="subject" id="field_subject" class="form-control" required value="<?= htmlspecialchars($assessment['subject']) ?>">
            </div>
        </div>

        <div style="margin-bottom:16px">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Instructions</label>
            <textarea name="instructions" class="form-control" rows="3"><?= htmlspecialchars($assessment['instructions']) ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:16px">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Duration (Mins)</label>
                <input type="number" name="duration_minutes" class="form-control" value="<?= (int)$assessment['duration_minutes'] ?>" min="1">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Max Attempts</label>
                <input type="number" name="max_attempts" class="form-control" value="<?= (int)$assessment['max_attempts'] ?>" min="1">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:16px">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Opening Time <span style="color:#ef4444">*</span></label>
                <?php 
                    $afv = '';
                    if (!empty($assessment['available_from'])) {
                        $afv = date('Y-m-d\TH:i', strtotime($assessment['available_from']));
                    }
                ?>
                <input type="datetime-local" name="available_from" class="form-control" required value="<?= $afv ?>">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Closing Time <span style="color:#ef4444">*</span></label>
                <?php 
                    $auv = '';
                    if (!empty($assessment['available_until'])) {
                        $auv = date('Y-m-d\TH:i', strtotime($assessment['available_until']));
                    }
                ?>
                <input type="datetime-local" name="available_until" class="form-control" required value="<?= $auv ?>">
            </div>
        </div>

        <div style="margin-bottom:24px">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:0.9rem">Due Date (Display Only)</label>
            <?php 
                $dv = '';
                if ($assessment['due_date']) {
                    $dv = date('Y-m-d\TH:i', strtotime($assessment['due_date']));
                }
            ?>
            <input type="datetime-local" name="due_date" class="form-control" value="<?= $dv ?>">
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end">
            <button type="submit" class="btn btn-primary" style="background:var(--primary);border:none;width:100%">Save Assessment Details</button>
        </div>
    </form>
</div>

<script>
function updateLinkedFields(select) {
    const option = select.options[select.selectedIndex];
    const lessonHint = document.getElementById('lesson_hint');
    const saDiv = document.getElementById('standalone_competencies');

    if (option.value) { // Linked to lesson
        document.getElementById('field_grade').value = option.getAttribute('data-grade').replace('Grade ','');
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

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>

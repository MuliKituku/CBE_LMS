<?php
/* ── teacher/assessment_builder.php ───────────────────────────────
   Variables: $profile, $assessment, $interactions
   ─────────────────────────────────────────────────────────── */
$activeNav = 'assessments';
require BASE_PATH . '/app/views/teacher/_sidebar.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:16px">
        <a href="/CBE_LMS/public/index.php?url=teacher/assessments" class="btn btn-outline" style="padding:6px 12px;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center">←</a>
        <div>
            <h2 style="color:var(--text);margin:0">Build Assessment Interactions</h2>
            <div style="font-size:0.85rem;color:var(--muted);margin-top:4px"><?= htmlspecialchars($assessment['title']) ?></div>
        </div>
    </div>
    <button class="btn btn-outline" onclick="addInteractionRow()">+ Add Question</button>
</div>

<form method="POST" action="/CBE_LMS/public/index.php?url=teacher/saveAssessmentInteractions">
    <input type="hidden" name="id" value="<?= $assessment['id'] ?>">

    <div id="interactions_container" style="display:flex;flex-direction:column;gap:20px">
        <?php if (empty($interactions)): ?>
            <!-- empty state row will be inserted here via JS -->
        <?php else: ?>
            <?php foreach($interactions as $idx => $itr): ?>
                <?php 
                    $opts = [];
                    if (!empty($itr['options'])) { $opts = json_decode($itr['options'], true); }
                ?>
                <div class="card interaction-row" style="padding:20px;position:relative">
                    <button type="button" onclick="this.closest('.interaction-row').remove()" style="position:absolute;top:10px;right:10px;background:none;border:none;color:#ef4444;font-size:1.2rem;cursor:pointer">&times;</button>
                    
                    <div style="display:flex;gap:12px;margin-bottom:15px;align-items:flex-start">
                        <div style="flex:1">
                            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Type <span style="font-size:0.75rem;color:var(--primary)">(Auto-graded if MCQ)</span></label>
                            <select name="interaction_type[]" class="form-control i-type-select" onchange="toggleInteractionOptions(this)">
                                <option value="mcq" <?= $itr['interaction_type']==='mcq'?'selected':'' ?>>Multiple Choice</option>
                                <option value="fill_blank" <?= $itr['interaction_type']==='fill_blank'?'selected':'' ?>>Fill in the Blank</option>
                                <option value="text_submission" <?= $itr['interaction_type']==='text_submission'?'selected':'' ?>>Short Answer / Essay</option>
                                <option value="file_upload" <?= $itr['interaction_type']==='file_upload'?'selected':'' ?>>File Upload Submission</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom:15px">
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Question Prompt</label>
                        <textarea name="interaction_question[]" class="form-control" rows="2" required><?= htmlspecialchars($itr['question']) ?></textarea>
                    </div>

                    <div class="i-options-container" style="background:#f8fafc;padding:15px;border-radius:8px;margin-bottom:15px;<?= $itr['interaction_type']==='mcq'?'display:block':'display:none' ?>">
                        <label style="display:block;margin-bottom:10px;font-weight:600;font-size:0.85rem">MCQ Choices</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                            <input type="text" name="interaction_option_a[]" class="form-control" placeholder="Option A" value="<?= htmlspecialchars($opts[0]['text'] ?? '') ?>">
                            <input type="text" name="interaction_option_b[]" class="form-control" placeholder="Option B" value="<?= htmlspecialchars($opts[1]['text'] ?? '') ?>">
                            <input type="text" name="interaction_option_c[]" class="form-control" placeholder="Option C" value="<?= htmlspecialchars($opts[2]['text'] ?? '') ?>">
                            <input type="text" name="interaction_option_d[]" class="form-control" placeholder="Option D" value="<?= htmlspecialchars($opts[3]['text'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
                        <div class="i-correct-container" style="<?= $itr['interaction_type']==='mcq'?'display:block':'display:none' ?>">
                            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Correct Answer (Auto-grade Key)</label>
                            <input type="text" name="interaction_correct[]" class="form-control" placeholder="e.g. Option A's exact text" value="<?= htmlspecialchars($itr['correct_answer']) ?>">
                            <div style="font-size:0.75rem;color:var(--muted);margin-top:2px">Must exactly match the text of correct option.</div>
                        </div>
                        <div class="i-manual-container" style="<?= $itr['interaction_type']==='text_submission'?'display:block':'display:none' ?>">
                            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Correct Answer <span style="font-size:0.7rem;color:var(--muted)">(Ignored: manual grade)</span></label>
                            <input type="text" name="interaction_correct[]" class="form-control" readonly disabled>
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Points</label>
                            <input type="number" name="interaction_marks[]" class="form-control" value="<?= (int)$itr['marks'] ?>" required>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div style="margin-top:24px;text-align:right">
        <button type="submit" class="btn btn-primary" style="background:#10b981;border:none;padding:10px 30px;font-size:1.1rem">Save All Questions</button>
    </div>
</form>

<template id="row_template">
    <div class="card interaction-row" style="padding:20px;position:relative;animation: fadeIn 0.3s">
        <button type="button" onclick="this.closest('.interaction-row').remove()" style="position:absolute;top:10px;right:10px;background:none;border:none;color:#ef4444;font-size:1.2rem;cursor:pointer">&times;</button>
        
        <div style="display:flex;gap:12px;margin-bottom:15px;align-items:flex-start">
            <div style="flex:1">
                <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Type <span style="font-size:0.75rem;color:var(--primary)">(Auto-graded if MCQ)</span></label>
                <select name="interaction_type[]" class="form-control i-type-select" onchange="toggleInteractionOptions(this)">
                    <option value="mcq">Multiple Choice</option>
                    <option value="fill_blank">Fill in the Blank</option>
                    <option value="text_submission">Short Answer / Essay</option>
                    <option value="file_upload">File Upload Submission</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom:15px">
            <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Question Prompt</label>
            <textarea name="interaction_question[]" class="form-control" rows="2" required></textarea>
        </div>

        <div class="i-options-container" style="background:#f8fafc;padding:15px;border-radius:8px;margin-bottom:15px;">
            <label style="display:block;margin-bottom:10px;font-weight:600;font-size:0.85rem">MCQ Choices</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                <input type="text" name="interaction_option_a[]" class="form-control" placeholder="Option A">
                <input type="text" name="interaction_option_b[]" class="form-control" placeholder="Option B">
                <input type="text" name="interaction_option_c[]" class="form-control" placeholder="Option C">
                <input type="text" name="interaction_option_d[]" class="form-control" placeholder="Option D">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
            <div class="i-correct-container">
                <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Correct Answer (Auto-grade Key)</label>
                <input type="text" name="interaction_correct[]" class="form-control input-correct" placeholder="e.g. Option A's exact text">
                <div style="font-size:0.75rem;color:var(--muted);margin-top:2px">Must exactly match the text of correct option.</div>
            </div>
            <div class="i-manual-container" style="display:none">
                <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Correct Answer <span style="font-size:0.7rem;color:var(--muted)">(Ignored: manual grade)</span></label>
                <input type="text" name="interaction_correct[]" class="form-control input-manual" readonly disabled>
            </div>
            <div>
                <label style="display:block;margin-bottom:5px;font-weight:600;font-size:0.85rem">Points</label>
                <input type="number" name="interaction_marks[]" class="form-control" value="1" required>
            </div>
        </div>
    </div>
</template>

<script>
function addInteractionRow() {
    const tpl = document.getElementById('row_template');
    const container = document.getElementById('interactions_container');
    const clone = tpl.content.cloneNode(true);
    container.appendChild(clone);
}

function toggleInteractionOptions(select) {
    const type = select.value;
    const row = select.closest('.interaction-row');
    const optContainer = row.querySelector('.i-options-container');
    const corrContainer = row.querySelector('.i-correct-container');
    const manContainer = row.querySelector('.i-manual-container');
    
    // Disable inputs that are hidden so they don't break POST arrays
    const inputCorr = corrContainer.querySelector('.input-correct') || row.querySelector('input[name="interaction_correct[]"]:not(.input-manual)');
    const inputMan = manContainer.querySelector('.input-manual') || row.querySelector('.input-manual');

    if (type === 'mcq') {
        optContainer.style.display = 'block';
        corrContainer.style.display = 'block';
        manContainer.style.display = 'none';
        
        if (inputCorr) {
            inputCorr.disabled = false;
            inputCorr.placeholder = "e.g. Option A's exact text";
        }
        if (inputMan) inputMan.disabled = true;
    } else if (type === 'fill_blank') {
        optContainer.style.display = 'none';
        corrContainer.style.display = 'block';
        manContainer.style.display = 'none';
        
        if (inputCorr) {
            inputCorr.disabled = false;
            inputCorr.placeholder = "The exact word to be filled";
        }
        if (inputMan) inputMan.disabled = true;
    } else {
        // text_submission or file_upload (manual grading)
        optContainer.style.display = 'none';
        corrContainer.style.display = 'none';
        manContainer.style.display = 'block';
        
        if (inputCorr) inputCorr.disabled = true;
        if (inputMan) {
            inputMan.disabled = false;
            inputMan.placeholder = (type === 'file_upload') ? "Instructions for file upload" : "Ignored: manual grade";
        }
    }
}

<?php if (empty($interactions)): ?>
// Initialize with one empty row
addInteractionRow();
<?php endif; ?>
</script>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>

<?php
/* ── student/take_assessment.php ────────────────────────────
   Variables: $profile, $assessment (with 'questions' from assessment_interactions)
   ─────────────────────────────────────────────────────────── */
$activeNav = 'assessments';
require BASE_PATH . '/app/views/student/_sidebar.php';

$questions = $assessment['questions'] ?? [];
$qCount    = count($questions);
$mins      = (int)($assessment['duration_minutes'] ?? 60);
?>

<div style="margin-bottom:16px">
    <a href="/CBE_LMS/public/index.php?url=student/assessments" class="btn btn-outline btn-sm">
        ← Back to Assessments
    </a>
</div>

<!-- Assessment header -->
<div class="card" style="margin-bottom:20px;background:linear-gradient(135deg,#0d9488,#0891b2);color:#fff;border:none">
    <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <div style="flex:1">
            <h2 style="color:#fff;font-size:1.1rem;margin-bottom:6px"><?= htmlspecialchars($assessment['title']) ?></h2>
            <p style="color:rgba(255,255,255,.85);font-size:.85rem;margin:0">
                📖 <?= htmlspecialchars($assessment['subject']) ?>
                &nbsp;|&nbsp; 🏫 <?= htmlspecialchars($assessment['class_grade']) ?>
                &nbsp;|&nbsp; ❓ <?= $qCount ?> Questions
            </p>
        </div>
        <!-- Countdown Timer -->
        <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:12px 20px;text-align:center">
            <div style="font-size:.7rem;opacity:.8;margin-bottom:2px">TIME REMAINING</div>
            <div id="quizTimer" style="font-size:1.6rem;font-weight:800;letter-spacing:.05em">
                <?= str_pad($mins,2,'0',STR_PAD_LEFT) ?>:00
            </div>
        </div>
    </div>
</div>

<?php if ($assessment['instructions']): ?>
<div class="alert alert-info" style="margin-bottom:16px">
    📋 <strong>Instructions:</strong> <?= htmlspecialchars($assessment['instructions']) ?>
</div>
<?php endif; ?>

<!-- Quiz Form -->
<form method="POST" action="/CBE_LMS/public/index.php?url=student/submitAssessment" id="quizForm" enctype="multipart/form-data">
    <input type="hidden" name="assessment_id" value="<?= (int)$assessment['id'] ?>">

    <?php if (empty($questions)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">❓</div>
            <h3>No Questions Yet</h3>
            <p>The teacher hasn't added questions to this assessment yet. Check back later.</p>
        </div>
    </div>
    <?php else: ?>

    <?php foreach ($questions as $i => $q):
        $qNum    = $i + 1;
        $type    = $q['type'] ?? 'mcq';
        $opts    = !empty($q['options']) ? json_decode($q['options'], true) : [];
        $isManual = in_array($type, ['text_submission', 'fill_blank', 'file_upload']);
    ?>
    <div class="card" style="margin-bottom:16px" id="question-<?= $qNum ?>">
        <div class="card-body">
            <!-- Question number + text -->
            <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px">
                <div style="width:32px;height:32px;border-radius:50%;
                            background:<?= $isManual ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'linear-gradient(135deg,#0d9488,#0891b2)' ?>;
                            color:#fff;display:flex;align-items:center;justify-content:center;
                            font-weight:700;font-size:.8rem;flex-shrink:0">
                    <?= $qNum ?>
                </div>
                <div style="flex:1">
                    <p style="font-size:.95rem;font-weight:600;margin:0 0 4px;line-height:1.5">
                        <?= htmlspecialchars($q['question']) ?>
                    </p>
                    <?php if ($isManual): ?>
                    <span style="font-size:.75rem;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:20px;font-weight:600">
                        ✏️ Teacher will mark this
                    </span>
                    <?php else: ?>
                    <span style="font-size:.75rem;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:20px;font-weight:600">
                        ✅ Auto-graded · <?= (int)$q['marks'] ?> mark<?= $q['marks']!=1?'s':'' ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($q['hint'])): ?>
                    <div style="font-size:.78rem;color:#64748b;margin-top:4px;font-style:italic">💡 Hint: <?= htmlspecialchars($q['hint']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Answer area -->
            <?php if ($type === 'mcq' && !empty($opts)): ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <?php foreach ($opts as $opt):
                    if (empty($opt['text'])) continue;
                    $oId = $opt['id'] ?? 'a';
                    $oLabel = strtoupper($oId);
                ?>
                <label class="option-label" id="opt-<?= $q['id'] ?>-<?= $oId ?>"
                       style="display:flex;align-items:center;gap:10px;padding:12px 14px;
                              border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;
                              background:#f8fafc;transition:.15s;user-select:none">
                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= htmlspecialchars($opt['text']) ?>"
                           style="display:none"
                           onchange="selectOption(<?= $q['id'] ?>, '<?= $oId ?>', this)">
                    <div class="opt-circle" style="width:28px;height:28px;border-radius:50%;background:#e2e8f0;
                                display:flex;align-items:center;justify-content:center;
                                font-weight:700;font-size:.78rem;flex-shrink:0;color:#64748b;transition:.15s">
                        <?= $oLabel ?>
                    </div>
                    <span style="font-size:.88rem;color:#374151"><?= htmlspecialchars($opt['text']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <?php elseif ($type === 'true_false'): ?>
            <div style="display:flex;gap:12px">
                <?php foreach (['True', 'False'] as $tf): ?>
                <label id="opt-<?= $q['id'] ?>-<?= strtolower($tf) ?>"
                       style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;
                              border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;background:#f8fafc;transition:.15s">
                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $tf ?>"
                           style="display:none"
                           onchange="selectOption(<?= $q['id'] ?>, '<?= strtolower($tf) ?>', this)">
                    <span style="font-weight:700;font-size:.95rem"><?= $tf === 'True' ? '✅ True' : '❌ False' ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <?php elseif ($type === 'fill_blank'): ?>
            <input type="text" name="answers[<?= $q['id'] ?>]"
                   class="form-control"
                   placeholder="Type the correct word here..."
                   oninput="countAnswered()"
                   style="padding:12px;font-size:.95rem">

            <?php elseif ($type === 'file_upload'): ?>
            <div style="background:#f1f5f9;padding:20px;border-radius:10px;border:2px dashed #cbd5e1;text-align:center">
                <input type="file" name="files[<?= $q['id'] ?>]" id="file-<?= $q['id'] ?>"
                       onchange="countAnswered()" style="font-size:.85rem">
                <div style="font-size:.7rem;color:var(--muted);margin-top:8px">Supported: PDF, Images, Word (Max 10MB)</div>
            </div>

            <?php else: /* text_submission */ ?>
            <textarea name="answers[<?= $q['id'] ?>]"
                      class="form-control" rows="4"
                      placeholder="Write your detailed answer..."
                      oninput="countAnswered()"
                      style="resize:vertical;font-size:.9rem"></textarea>
            <?php endif; ?>

        </div>
    </div>
    <?php endforeach; ?>

    <!-- Progress + Submit -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="flex:1">
                <div style="font-size:.8rem;font-weight:600;margin-bottom:6px;color:var(--muted)">
                    <span id="answeredCount">0</span>/<?= $qCount ?> questions answered
                </div>
                <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                    <div id="progressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#0d9488,#0891b2);
                                border-radius:4px;transition:width .3s"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                Submit Assessment ✅
            </button>
        </div>
    </div>

    <?php endif; ?>
</form>

</div></div>
<script>
const totalQ  = <?= $qCount ?>;
const answered = new Set();

function selectOption(qId, key, input) {
    // Remove highlight from all options in this question
    document.querySelectorAll('[id^="opt-' + qId + '-"]').forEach(lbl => {
        lbl.style.border      = '2px solid #e2e8f0';
        lbl.style.background  = '#f8fafc';
        const c = lbl.querySelector('.opt-circle');
        if (c) { c.style.background = '#e2e8f0'; c.style.color = '#64748b'; }
    });
    // Highlight selected
    const el = document.getElementById('opt-' + qId + '-' + key);
    if (el) {
        el.style.border      = '2px solid #0d9488';
        el.style.background  = '#f0fdfa';
        const c = el.querySelector('.opt-circle');
        if (c) { c.style.background = '#0d9488'; c.style.color = '#fff'; }
    }
    answered.add(qId);
    updateProgress();
}

function countAnswered() {
    // Clear tracked to recount
    answered.clear();

    // 1. Radio/MCQ
    document.querySelectorAll('input[type=radio]:checked').forEach(el => {
        const qId = el.name.replace('answers[','').replace(']','');
        answered.add(parseInt(qId));
    });

    // 2. Text/Textarea
    document.querySelectorAll('input[type=text][name^="answers"], textarea[name^="answers"]').forEach(el => {
        const qId = el.name.replace('answers[','').replace(']','');
        if (el.value.trim()) answered.add(parseInt(qId));
    });

    // 3. Files
    document.querySelectorAll('input[type=file][name^="files"]').forEach(el => {
        const qId = el.name.replace('files[','').replace(']','');
        if (el.files && el.files.length > 0) answered.add(parseInt(qId));
    });

    updateProgress();
}

function updateProgress() {
    const pct = Math.round(answered.size / totalQ * 100);
    document.getElementById('answeredCount').textContent = answered.size;
    document.getElementById('progressBar').style.width   = pct + '%';
}

// Countdown timer
let secs = <?= $mins * 60 ?>;
const timerEl = document.getElementById('quizTimer');
const timerInterval = setInterval(() => {
    secs--;
    if (secs <= 0) {
        clearInterval(timerInterval);
        document.getElementById('quizForm').submit();
        return;
    }
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    if (timerEl) {
        timerEl.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        timerEl.style.color = secs <= 60 ? '#fde68a' : '#fff';
    }
}, 1000);

// Confirm before submit
document.getElementById('quizForm').addEventListener('submit', function(e) {
    if (answered.size < totalQ) {
        if (!confirm('You have ' + (totalQ - answered.size) + ' unanswered question(s). Submit anyway?')) {
            e.preventDefault();
        }
    }
});
</script>
</body></html>
